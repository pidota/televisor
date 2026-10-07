<?php

namespace App\Services;

use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Enums\PlaylistStatus;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaylistService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, description?: ?string, status?: string}  $data
     */
    public function create(array $data, User $user): Playlist
    {
        $playlist = Playlist::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? PlaylistStatus::Draft,
            'revision' => 1,
            'created_by' => $user->id,
        ]);

        $this->audit->log($user, 'playlist.created', $playlist);

        return $playlist;
    }

    /**
     * @param  array{name?: string, description?: ?string, status?: string}  $data
     */
    public function update(Playlist $playlist, array $data, User $user): Playlist
    {
        $playlist->fill([
            'name' => $data['name'] ?? $playlist->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $playlist->description,
            'status' => $data['status'] ?? $playlist->status,
        ]);
        $playlist->save();

        $this->audit->log($user, 'playlist.updated', $playlist, $data);

        return $playlist;
    }

    public function delete(Playlist $playlist, User $user): void
    {
        if ($playlist->assignments()->exists() || $playlist->schedules()->exists()) {
            throw ValidationException::withMessages([
                'playlist' => 'No se puede eliminar: la playlist está asignada o programada.',
            ]);
        }

        $this->audit->log($user, 'playlist.deleted', $playlist, ['name' => $playlist->name]);
        $playlist->delete();
    }

    public function addItem(Playlist $playlist, MediaAsset $media, User $user, ?int $durationSeconds = null): PlaylistItem
    {
        if ($media->status !== MediaAssetStatus::Ready) {
            throw ValidationException::withMessages([
                'media_asset_id' => 'Solo se puede agregar contenido en estado listo.',
            ]);
        }

        if ($playlist->items()->where('media_asset_id', $media->id)->exists()) {
            throw ValidationException::withMessages([
                'media_asset_id' => 'Este contenido ya está en la playlist.',
            ]);
        }

        if ($media->type === MediaAssetType::Image) {
            $duration = $durationSeconds ?? 10;
            if ($duration < 1 || $duration > 3600) {
                throw ValidationException::withMessages([
                    'duration_seconds' => 'La duración debe estar entre 1 y 3600 segundos.',
                ]);
            }
        } else {
            $duration = null;
        }

        return DB::transaction(function () use ($playlist, $media, $user, $duration) {
            $nextOrder = (int) $playlist->items()->max('sort_order') + 1;

            $item = PlaylistItem::query()->create([
                'playlist_id' => $playlist->id,
                'media_asset_id' => $media->id,
                'sort_order' => $nextOrder,
                'duration_seconds' => $duration,
            ]);

            $this->bumpRevision($playlist);

            $this->audit->log($user, 'playlist.item_added', $playlist, [
                'media_asset_id' => $media->id,
                'item_id' => $item->id,
            ]);

            return $item;
        });
    }

    public function updateItemDuration(PlaylistItem $item, ?int $durationSeconds, User $user): PlaylistItem
    {
        $media = $item->mediaAsset;

        if ($media->type === MediaAssetType::Video) {
            throw ValidationException::withMessages([
                'duration_seconds' => 'La duración de los videos se toma del archivo.',
            ]);
        }

        if ($durationSeconds === null || $durationSeconds < 1 || $durationSeconds > 3600) {
            throw ValidationException::withMessages([
                'duration_seconds' => 'Indique una duración entre 1 y 3600 segundos.',
            ]);
        }

        $item->update(['duration_seconds' => $durationSeconds]);
        $this->bumpRevision($item->playlist);
        $this->audit->log($user, 'playlist.item_updated', $item->playlist, ['item_id' => $item->id]);

        return $item->fresh();
    }

    public function removeItem(PlaylistItem $item, User $user): void
    {
        DB::transaction(function () use ($item, $user) {
            $playlist = $item->playlist;
            $item->delete();
            $this->renumberItems($playlist);
            $this->bumpRevision($playlist);
            $this->audit->log($user, 'playlist.item_removed', $playlist, ['item_id' => $item->id]);
        });
    }

    /**
     * @param  list<int>  $orderedItemIds
     */
    public function reorderItems(Playlist $playlist, array $orderedItemIds, User $user): void
    {
        $existingIds = $playlist->items()->pluck('id')->sort()->values()->all();
        $sortedInput = collect($orderedItemIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        if ($existingIds !== $sortedInput) {
            throw ValidationException::withMessages([
                'order' => 'La lista de elementos no coincide con la playlist.',
            ]);
        }

        DB::transaction(function () use ($playlist, $orderedItemIds, $user) {
            foreach ($orderedItemIds as $index => $itemId) {
                PlaylistItem::query()
                    ->where('playlist_id', $playlist->id)
                    ->where('id', $itemId)
                    ->update(['sort_order' => $index + 1]);
            }

            $this->bumpRevision($playlist);
            $this->audit->log($user, 'playlist.reordered', $playlist);
        });
    }

    private function renumberItems(Playlist $playlist): void
    {
        $playlist->items()->orderBy('sort_order')->get()->each(function (PlaylistItem $item, int $index) {
            $item->update(['sort_order' => $index + 1]);
        });
    }

    private function bumpRevision(Playlist $playlist): void
    {
        $playlist->increment('revision');
    }
}
