<?php

namespace App\Enums;

enum UrgentMessageStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
