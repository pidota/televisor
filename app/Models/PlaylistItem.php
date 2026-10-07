<?php

namespace App\Models;

use App\Enums\MediaAssetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model
{
    protected $fillable = [
        'playlist_id',
        'media_asset_id',
        'sort_order',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function effectiveDurationSeconds(): int
    {
        $media = $this->mediaAsset;

        if ($media === null) {
            return 0;
        }

        if ($media->type === MediaAssetType::Video) {
            return $this->duration_seconds
                ?? $media->duration_seconds
                ?? 0;
        }

        return $this->duration_seconds ?? 10;
    }
}

