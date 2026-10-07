<?php

namespace App\Enums;

enum PlaylistStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
