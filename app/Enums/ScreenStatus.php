<?php

namespace App\Enums;

enum ScreenStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Disabled = 'disabled';
    case Revoked = 'revoked';
}
