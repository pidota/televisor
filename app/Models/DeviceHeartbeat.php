<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHeartbeat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'screen_id',
        'status',
        'playlist_id',
        'media_asset_id',
        'manifest_version',
        'storage_free_bytes',
        'app_version',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'manifest_version' => 'integer',
            'storage_free_bytes' => 'integer',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
}
