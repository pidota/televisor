<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ScreenGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function screens(): BelongsToMany
    {
        return $this->belongsToMany(Screen::class, 'screen_group_screen');
    }

    public function playlistAssignments(): MorphMany
    {
        return $this->morphMany(PlaylistAssignment::class, 'assignable');
    }
}
