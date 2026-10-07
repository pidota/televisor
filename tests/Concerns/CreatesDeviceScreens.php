<?php

namespace Tests\Concerns;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Services\DeviceTokenService;
use Illuminate\Support\Str;

trait CreatesDeviceScreens
{
    /**
     * @return array{0: Screen, 1: string}
     */
    protected function createActiveScreenWithToken(array $overrides = []): array
    {
        $tokens = app(DeviceTokenService::class);
        $plain = $tokens->generatePlainToken();

        $screen = Screen::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'name' => 'Pantalla test',
            'location' => 'Test',
            'status' => ScreenStatus::Active,
            'device_token_hash' => $tokens->hashToken($plain),
            'manifest_version' => 1,
        ], $overrides));

        return [$screen, $plain];
    }
}
