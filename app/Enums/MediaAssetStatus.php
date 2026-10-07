<?php

namespace App\Enums;

enum MediaAssetStatus: string
{
    case Uploading = 'uploading';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Archived = 'archived';
}
