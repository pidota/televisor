<?php

namespace App\Services;

use App\Enums\AssigneeType;
use App\Models\PlaylistAssignment;
use App\Models\Screen;
use App\Models\ScreenGroup;
use Illuminate\Support\Collection;

class ScreenManifestService
{
    /**
     * @param  Collection<int, int>|array<int, int>  $screenIds
     */
    public function bumpForScreenIds(Collection|array $screenIds): void
    {
        $ids = collect($screenIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        Screen::query()
            ->whereIn('id', $ids)
            ->each(fn (Screen $screen) => $screen->increment('manifest_version'));
    }

    public function bumpForAssignable(AssigneeType $type, int $assignableId): void
    {
        if ($type === AssigneeType::Screen) {
            $this->bumpForScreenIds([$assignableId]);

            return;
        }

        $group = ScreenGroup::query()->with('screens:id')->find($assignableId);
        if ($group !== null) {
            $this->bumpForScreenIds($group->screens->pluck('id'));
        }
    }

    public function bumpForPlaylistAssignmentChanges(PlaylistAssignment $assignment): void
    {
        $this->bumpForAssignable($assignment->assignable_type, (int) $assignment->assignable_id);
    }
}
