<?php

namespace App\Models;

use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'extension',
        'size_bytes',
        'duration_seconds',
        'width',
        'height',
        'checksum_sha256',
        'status',
        'valid_from',
        'valid_until',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaAssetType::class,
            'status' => MediaAssetStatus::class,
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }
}
