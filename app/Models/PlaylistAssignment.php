<?php

namespace App\Models;

use App\Enums\AssigneeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlaylistAssignment extends Model
{
    protected $fillable = [
        'playlist_id',
        'assignable_type',
        'assignable_id',
        'is_default',
        'priority',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'assignable_type' => AssigneeType::class,
            'is_default' => 'boolean',
            'priority' => 'integer',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * Resuelve pantalla o grupo según assignable_type.
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'assignable_type', 'assignable_id');
    }
}
