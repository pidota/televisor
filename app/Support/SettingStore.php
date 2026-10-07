<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingStore
{
    public function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember(
            "settings.{$key}",
            now()->addMinutes(10),
            fn () => Setting::query()->where('key', $key)->value('value')
        ) ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value !== null && $value !== '' ? (int) $value : $default;
    }

    public function forget(string $key): void
    {
        Cache::forget("settings.{$key}");
    }
}
