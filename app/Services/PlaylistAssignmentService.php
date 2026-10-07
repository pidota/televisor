<?php

namespace App\Services;

use App\Enums\AssigneeType;
use App\Models\Playlist;
use App\Models\PlaylistAssignment;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlaylistAssignmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ScreenManifestService $manifests,
    ) {}

    /**
     * @return array{screen_ids: list<int>, group_ids: list<int>}
     */
    public function assignedTargetIds(Playlist $playlist): array
    {
        $assignments = $playlist->assignments()->get(['assignable_type', 'assignable_id']);

        return [
            'screen_ids' => $assignments
                ->where('assignable_type', AssigneeType::Screen)
                ->pluck('assignable_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'group_ids' => $assignments
                ->where('assignable_type', AssigneeType::ScreenGroup)
                ->pluck('assignable_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<int>  $screenIds
     * @param  list<int>  $groupIds
     */
    public function syncForPlaylist(Playlist $playlist, array $screenIds, array $groupIds, User $user): void
    {
        $screenIds = collect($screenIds)->map(fn ($id) => (int) $id)->unique()->values();
        $groupIds = collect($groupIds)->map(fn ($id) => (int) $id)->unique()->values();

        $validScreenIds = Screen::query()->whereIn('id', $screenIds)->pluck('id');
        $validGroupIds = ScreenGroup::query()->whereIn('id', $groupIds)->pluck('id');

        DB::transaction(function () use ($playlist, $validScreenIds, $validGroupIds, $user) {
            $desired = collect();

            foreach ($validScreenIds as $screenId) {
                $desired->push([
                    'type' => AssigneeType::Screen,
                    'id' => (int) $screenId,
                ]);
            }

            foreach ($validGroupIds as $groupId) {
                $desired->push([
                    'type' => AssigneeType::ScreenGroup,
                    'id' => (int) $groupId,
                ]);
            }

            $existing = $playlist->assignments()->get();

            foreach ($existing as $assignment) {
                $stillWanted = $desired->contains(fn ($t) => $t['type'] === $assignment->assignable_type
                    && $t['id'] === (int) $assignment->assignable_id);

                if (! $stillWanted) {
                    $this->manifests->bumpForAssignable($assignment->assignable_type, (int) $assignment->assignable_id);
                    $assignment->delete();
                }
            }

            foreach ($desired as $target) {
                $this->assignAsDefault($playlist, $target['type'], $target['id']);
            }

            $this->audit->log($user, 'playlist.assignments_synced', $playlist, [
                'screen_ids' => $validScreenIds->values()->all(),
                'group_ids' => $validGroupIds->values()->all(),
            ]);
        });
    }

    private function assignAsDefault(Playlist $playlist, AssigneeType $type, int $assignableId): void
    {
        PlaylistAssignment::query()
            ->where('assignable_type', $type->value)
            ->where('assignable_id', $assignableId)
            ->where('is_default', true)
            ->where('playlist_id', '!=', $playlist->id)
            ->update(['is_default' => false]);

        $assignment = PlaylistAssignment::query()->updateOrCreate(
            [
                'playlist_id' => $playlist->id,
                'assignable_type' => $type->value,
                'assignable_id' => $assignableId,
            ],
            [
                'is_default' => true,
                'priority' => 0,
            ]
        );

        $this->manifests->bumpForAssignable($type, $assignableId);

        if ($assignment->wasRecentlyCreated || $assignment->wasChanged()) {
            // bump already called
        }
    }

    public function defaultPlaylistForScreen(Screen $screen): ?Playlist
    {
        $assignment = $screen->playlistAssignments()
            ->where('is_default', true)
            ->with('playlist')
            ->first();

        if ($assignment?->playlist) {
            return $assignment->playlist;
        }

        $groupIds = $screen->groups()->pluck('screen_groups.id');

        if ($groupIds->isEmpty()) {
            return null;
        }

        $groupAssignment = PlaylistAssignment::query()
            ->where('assignable_type', AssigneeType::ScreenGroup->value)
            ->whereIn('assignable_id', $groupIds)
            ->where('is_default', true)
            ->with('playlist')
            ->orderByDesc('priority')
            ->first();

        return $groupAssignment?->playlist;
    }
}
