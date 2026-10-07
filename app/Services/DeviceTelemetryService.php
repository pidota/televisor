<?php

namespace App\Services;

use App\Models\DeviceHeartbeat;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Screen;
use Illuminate\Support\Facades\DB;

class DeviceTelemetryService
{
    /**
     * @param  array{
     *   status?: string,
     *   playlist_id?: ?int,
     *   content_id?: ?int,
     *   media_asset_id?: ?int,
     *   manifest_version?: ?int,
     *   storage_free?: ?int,
     *   storage_total?: ?int,
     *   app_version?: ?string,
     *   device_model?: ?string,
     *   android_version?: ?string,
     *   resolution?: ?string,
     *   payload?: ?array
     * }  $data
     */
    public function recordHeartbeat(Screen $screen, array $data, ?string $ip): DeviceHeartbeat
    {
        $mediaId = $data['media_asset_id'] ?? $data['content_id'] ?? null;
        $playlistId = $data['playlist_id'] ?? null;

        return DB::transaction(function () use ($screen, $data, $ip, $mediaId, $playlistId) {
            $screen->fill([
                'last_seen_at' => now(),
                'last_ip' => $ip,
                'storage_free_bytes' => $data['storage_free'] ?? $screen->storage_free_bytes,
                'storage_total_bytes' => $data['storage_total'] ?? $screen->storage_total_bytes,
                'app_version' => $data['app_version'] ?? $screen->app_version,
                'device_model' => $data['device_model'] ?? $screen->device_model,
                'android_version' => $data['android_version'] ?? $screen->android_version,
                'resolution' => $data['resolution'] ?? $screen->resolution,
                'current_playlist_id' => $this->validPlaylistId($playlistId),
                'current_media_asset_id' => $this->validMediaId($mediaId),
            ]);
            $screen->save();

            return DeviceHeartbeat::query()->create([
                'screen_id' => $screen->id,
                'status' => $data['status'] ?? 'online',
                'playlist_id' => $this->validPlaylistId($playlistId),
                'media_asset_id' => $this->validMediaId($mediaId),
                'manifest_version' => $data['manifest_version'] ?? $screen->manifest_version,
                'storage_free_bytes' => $data['storage_free'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'payload' => $data['payload'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{playlist_id?: ?int, content_id?: ?int, media_asset_id?: ?int, status?: ?string}  $data
     */
    public function recordPlayback(Screen $screen, array $data): void
    {
        $mediaId = $data['media_asset_id'] ?? $data['content_id'] ?? null;

        $screen->update([
            'last_seen_at' => now(),
            'current_playlist_id' => $this->validPlaylistId($data['playlist_id'] ?? null),
            'current_media_asset_id' => $this->validMediaId($mediaId),
        ]);
    }

    private function validPlaylistId(?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        return Playlist::query()->whereKey($id)->exists() ? $id : null;
    }

    private function validMediaId(?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        return MediaAsset::query()->whereKey($id)->exists() ? $id : null;
    }
}
