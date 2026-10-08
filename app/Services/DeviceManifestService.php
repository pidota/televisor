<?php

namespace App\Services;

use App\Enums\MediaAssetStatus;
use App\Models\MediaAsset;
use App\Models\PlaylistItem;
use App\Models\Screen;
use Illuminate\Support\Facades\URL;

class DeviceManifestService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $manifestCache = [];

    public function __construct(
        private readonly ScreenContentResolver $contentResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Screen $screen): array
    {
        $cacheKey = $screen->id.':'.$screen->manifest_version;

        if (isset($this->manifestCache[$cacheKey])) {
            return $this->manifestCache[$cacheKey];
        }

        $content = $this->contentResolver->describe($screen);

        if ($content['source'] === 'live') {
            $overlay = $content['urgent_message'] instanceof \App\Models\UrgentMessage
                ? $this->mapUrgentMessage($content['urgent_message'])
                : null;

            return $this->manifestCache[$cacheKey] = [
                'version' => $screen->manifest_version,
                'generated_at' => now()->toIso8601String(),
                'source' => 'live',
                'live' => ['url' => $content['live_url']],
                'urgent_message' => $overlay,
                'playlist' => null,
                'items' => [],
            ];
        }

        if ($content['source'] === 'urgent' && $content['urgent_message'] instanceof \App\Models\UrgentMessage) {
            $urgent = $content['urgent_message'];

            return $this->manifestCache[$cacheKey] = [
                'version' => $screen->manifest_version,
                'generated_at' => now()->toIso8601String(),
                'source' => 'urgent',
                'urgent_message' => $this->mapUrgentMessage($urgent),
                'playlist' => null,
                'items' => [],
            ];
        }

        $playlist = $content['playlist'];

        if ($playlist === null) {
            $overlay = $content['urgent_message'] instanceof \App\Models\UrgentMessage
                ? $this->mapUrgentMessage($content['urgent_message'])
                : null;

            return $this->manifestCache[$cacheKey] = [
                'version' => $screen->manifest_version,
                'generated_at' => now()->toIso8601String(),
                'source' => $content['source'],
                'urgent_message' => $overlay,
                'playlist' => null,
                'items' => [],
            ];
        }

        $playlist->load(['items' => fn ($q) => $q->orderBy('sort_order'), 'items.mediaAsset']);

        $items = $playlist->items
            ->filter(fn (PlaylistItem $item) => $item->mediaAsset !== null
                && $item->mediaAsset->status === MediaAssetStatus::Ready)
            ->values()
            ->map(fn (PlaylistItem $item) => $this->mapItem($item))
            ->all();

        $overlay = $content['urgent_message'] instanceof \App\Models\UrgentMessage
            ? $this->mapUrgentMessage($content['urgent_message'])
            : null;

        $manifest = [
            'version' => $screen->manifest_version,
            'generated_at' => now()->toIso8601String(),
            'source' => $content['source'],
            'urgent_message' => $overlay,
            'playlist' => [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'revision' => $playlist->revision,
            ],
            'items' => $items,
        ];

        return $this->manifestCache[$cacheKey] = $manifest;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUrgentMessage(\App\Models\UrgentMessage $urgent): array
    {
        return [
            'id' => $urgent->id,
            'title' => $urgent->title,
            'body' => $urgent->body,
            'layout' => $urgent->layout->value,
            'starts_at' => $urgent->starts_at->toIso8601String(),
            'ends_at' => $urgent->ends_at->toIso8601String(),
            'priority' => $urgent->priority,
        ];
    }

    public function forgetCacheForScreen(Screen $screen): void
    {
        $prefix = $screen->id.':';
        foreach (array_keys($this->manifestCache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->manifestCache[$key]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(PlaylistItem $item): array
    {
        $media = $item->mediaAsset;

        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'item_id' => $item->id,
            'type' => $media->type->value,
            'name' => $media->name,
            'url' => URL::route('device.media.download', ['mediaAsset' => $media->uuid]),
            'checksum' => $media->checksum_sha256,
            'size' => $media->size_bytes,
            'duration' => $item->effectiveDurationSeconds(),
            'mime_type' => $media->mime_type,
        ];
    }

    public function screenCanDownload(Screen $screen, MediaAsset $media): bool
    {
        if ($media->status !== MediaAssetStatus::Ready) {
            return false;
        }

        $manifest = $this->build($screen);

        foreach ($manifest['items'] as $item) {
            if (($item['id'] ?? null) === $media->id || ($item['uuid'] ?? null) === $media->uuid) {
                return true;
            }
        }

        return false;
    }
}
