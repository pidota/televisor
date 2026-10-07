<?php

namespace App\Models;

use App\Enums\PlaylistStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Playlist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'revision',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlaylistStatus::class,
            'revision' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PlaylistAssignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
