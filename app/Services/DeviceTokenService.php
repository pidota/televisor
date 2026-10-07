<?php

namespace App\Services;

use Illuminate\Support\Str;

class DeviceTokenService
{
    public function generatePlainToken(): string
    {
        return Str::random(64);
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
