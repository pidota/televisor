<?php

namespace App\Models;

use App\Enums\ScreenStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Screen extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'location',
        'status',
        'device_token_hash',
        'token_last_rotated_at',
        'last_seen_at',
        'last_ip',
        'device_model',
        'android_version',
        'app_version',
        'resolution',
        'storage_total_bytes',
        'storage_free_bytes',
        'manifest_version',
        'current_playlist_id',
        'current_media_asset_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScreenStatus::class,
            'token_last_rotated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'storage_total_bytes' => 'integer',
            'storage_free_bytes' => 'integer',
            'manifest_version' => 'integer',
        ];
    }

    public function pairingCodes(): HasMany
    {
        return $this->hasMany(PairingCode::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ScreenGroup::class, 'screen_group_screen');
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(DeviceHeartbeat::class);
    }

    public function latestHeartbeat(): HasOne
    {
        return $this->hasOne(DeviceHeartbeat::class)->latestOfMany('created_at');
    }

    public function currentPlaylist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, 'current_playlist_id');
    }

    public function currentMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'current_media_asset_id');
    }

    public function urgentMessageTargets(): HasMany
    {
        return $this->hasMany(UrgentMessageTarget::class);
    }

    public function playlistAssignments(): MorphMany
    {
        return $this->morphMany(PlaylistAssignment::class, 'assignable');
    }
}
