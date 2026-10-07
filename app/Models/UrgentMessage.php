<?php

namespace App\Models;

use App\Enums\UrgentMessageLayout;
use App\Enums\UrgentMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UrgentMessage extends Model
{
    protected $fillable = [
        'title',
        'body',
        'layout',
        'starts_at',
        'ends_at',
        'priority',
        'status',
        'applies_to_all_screens',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'layout' => UrgentMessageLayout::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
            'status' => UrgentMessageStatus::class,
            'applies_to_all_screens' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(UrgentMessageTarget::class);
    }
}
