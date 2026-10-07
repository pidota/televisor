<?php

namespace App\Http\Controllers;

use App\Enums\ScreenStatus;
use App\Http\Requests\SyncPlaylistAssignmentsRequest;
use App\Models\Playlist;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Services\PlaylistAssignmentService;
use Illuminate\View\View;

class PlaylistAssignmentController extends Controller
{
    public function __construct(
        private readonly PlaylistAssignmentService $assignments,
    ) {}

    public function edit(Playlist $playlist): View
    {
        $this->authorize('update', $playlist);

        $assigned = $this->assignments->assignedTargetIds($playlist);

        return view('playlists.assign', [
            'playlist' => $playlist,
            'screens' => Screen::query()
                ->where('status', ScreenStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
            'groups' => ScreenGroup::query()->orderBy('name')->get(['id', 'name']),
            'assignedScreenIds' => $assigned['screen_ids'],
            'assignedGroupIds' => $assigned['group_ids'],
        ]);
    }

    public function update(SyncPlaylistAssignmentsRequest $request, Playlist $playlist)
    {
        $this->assignments->syncForPlaylist(
            $playlist,
            $request->input('screens', []),
            $request->input('groups', []),
            $request->user()
        );

        return redirect()
            ->route('playlists.assign.edit', $playlist)
            ->with('success', 'Asignación guardada. Las pantallas actualizarán su manifiesto en la próxima sincronización.');
    }
}
