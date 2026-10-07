<?php

namespace App\Services;

use App\Models\ScreenGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScreenGroupService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ScreenManifestService $manifests,
    ) {}

    /**
     * @param  array{name: string, description?: ?string}  $data
     */
    public function create(array $data, User $user): ScreenGroup
    {
        $group = ScreenGroup::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->audit->log($user, 'screen_group.created', $group);

        return $group;
    }

    /**
     * @param  array{name?: string, description?: ?string}  $data
     */
    public function update(ScreenGroup $group, array $data, User $user): ScreenGroup
    {
        $group->fill([
            'name' => $data['name'] ?? $group->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $group->description,
        ]);
        $group->save();

        $this->audit->log($user, 'screen_group.updated', $group);

        return $group;
    }

    /**
     * @param  list<int>  $screenIds
     */
    public function syncScreens(ScreenGroup $group, array $screenIds, User $user): void
    {
        $screenIds = collect($screenIds)->map(fn ($id) => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($group, $screenIds, $user) {
            $before = $group->screens()->pluck('screens.id');
            $group->screens()->sync($screenIds);
            $after = collect($screenIds);

            $this->manifests->bumpForScreenIds($before->merge($after)->unique());

            $this->audit->log($user, 'screen_group.screens_synced', $group, [
                'screen_ids' => $screenIds,
            ]);
        });
    }

    public function delete(ScreenGroup $group, User $user): void
    {
        if ($group->playlistAssignments()->exists()) {
            throw ValidationException::withMessages([
                'group' => 'No se puede eliminar: el grupo tiene playlists asignadas.',
            ]);
        }

        DB::transaction(function () use ($group, $user) {
            $screenIds = $group->screens()->pluck('screens.id');
            $this->manifests->bumpForScreenIds($screenIds);

            $this->audit->log($user, 'screen_group.deleted', $group);
            $group->screens()->detach();
            $group->delete();
        });
    }
}
