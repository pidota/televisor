<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrgentMessageTarget extends Model
{
    protected $fillable = [
        'urgent_message_id',
        'screen_id',
    ];

    public function urgentMessage(): BelongsTo
    {
        return $this->belongsTo(UrgentMessage::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }
}
