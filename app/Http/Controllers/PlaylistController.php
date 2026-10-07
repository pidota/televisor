<?php

namespace App\Http\Controllers;

use App\Enums\MediaAssetStatus;
use App\Enums\PlaylistStatus;
use App\Http\Requests\Playlist\ReorderPlaylistItemsRequest;
use App\Http\Requests\Playlist\StorePlaylistItemRequest;
use App\Http\Requests\Playlist\StorePlaylistRequest;
use App\Http\Requests\Playlist\UpdatePlaylistItemRequest;
use App\Http\Requests\Playlist\UpdatePlaylistRequest;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Services\PlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlists,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Playlist::class);

        $query = Playlist::query()->withCount('items')->orderByDesc('updated_at');

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $status = $request->string('status')->toString();
        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('playlists.index', [
            'playlists' => $query->paginate(15)->withQueryString(),
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Playlist::class);

        return view('playlists.create');
    }

    public function store(StorePlaylistRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['status']) && is_string($data['status'])) {
            $data['status'] = PlaylistStatus::from($data['status']);
        }

        $playlist = $this->playlists->create($data, $request->user());

        return redirect()
            ->route('playlists.show', $playlist)
            ->with('success', 'Playlist creada. Agregue contenidos y ordénela.');
    }

    public function show(Playlist $playlist): View
    {
        $this->authorize('view', $playlist);

        $playlist->load([
            'items.mediaAsset',
            'creator:id,name',
        ]);

        $availableMedia = MediaAsset::query()
            ->where('status', MediaAssetStatus::Ready)
            ->whereNotIn('id', $playlist->items->pluck('media_asset_id'))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'extension', 'duration_seconds']);

        $totalDuration = $playlist->items->sum(fn (PlaylistItem $item) => $item->effectiveDurationSeconds());

        return view('playlists.show', compact('playlist', 'availableMedia', 'totalDuration'));
    }

    public function edit(Playlist $playlist): View
    {
        $this->authorize('update', $playlist);

        return view('playlists.edit', compact('playlist'));
    }

    public function update(UpdatePlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $this->playlists->update($playlist, $request->validated(), $request->user());

        return redirect()
            ->route('playlists.show', $playlist)
            ->with('success', 'Playlist actualizada.');
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        $this->authorize('delete', $playlist);

        try {
            $this->playlists->delete($playlist, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('playlist'));
        }

        return redirect()
            ->route('playlists.index')
            ->with('success', 'Playlist eliminada.');
    }

    public function storeItem(StorePlaylistItemRequest $request, Playlist $playlist): RedirectResponse
    {
        $media = MediaAsset::query()->findOrFail($request->integer('media_asset_id'));

        try {
            $this->playlists->addItem(
                $playlist,
                $media,
                $request->user(),
                $request->integer('duration_seconds') ?: null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Contenido agregado a la playlist.');
    }

    public function updateItem(UpdatePlaylistItemRequest $request, Playlist $playlist, PlaylistItem $item): RedirectResponse
    {
        if ((int) $item->playlist_id !== (int) $playlist->id) {
            abort(404);
        }

        try {
            $this->playlists->updateItemDuration($item, $request->integer('duration_seconds'), $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Duración actualizada.');
    }

    public function destroyItem(Playlist $playlist, PlaylistItem $item): RedirectResponse
    {
        $this->authorize('update', $playlist);

        if ((int) $item->playlist_id !== (int) $playlist->id) {
            abort(404);
        }

        $this->playlists->removeItem($item, auth()->user());

        return back()->with('success', 'Elemento eliminado de la playlist.');
    }

    public function reorder(ReorderPlaylistItemsRequest $request, Playlist $playlist): JsonResponse
    {
        try {
            $this->playlists->reorderItems($playlist, $request->input('order', []), $request->user());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }

        return response()->json([
            'message' => 'Orden guardado.',
            'revision' => $playlist->fresh()->revision,
        ]);
    }
}
