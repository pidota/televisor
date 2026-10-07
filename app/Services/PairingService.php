<?php

namespace App\Services;

use App\Enums\ScreenStatus;
use App\Models\PairingCode;
use App\Models\Screen;
use App\Models\User;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PairingService
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly DeviceTokenService $tokens,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{code: string, expires_at: string, screen_uuid: string}
     */
    public function requestDevicePairing(string $screenUuid): array
    {
        if (! Str::isUuid($screenUuid)) {
            throw new RuntimeException('UUID de pantalla inválido.');
        }

        return DB::transaction(function () use ($screenUuid) {
            $this->invalidateOpenCodesForUuid($screenUuid);

            $screen = Screen::query()->firstOrCreate(
                ['uuid' => $screenUuid],
                ['status' => ScreenStatus::Pending]
            );

            if ($screen->status === ScreenStatus::Revoked) {
                throw new RuntimeException('Esta pantalla fue revocada.');
            }

            $ttlMinutes = $this->settings->getInt('pairing.code_ttl_minutes', 15);
            $expiresAt = now()->addMinutes($ttlMinutes);
            $code = $this->generateUniqueCode();

            PairingCode::query()->create([
                'code' => $code,
                'screen_uuid' => $screenUuid,
                'expires_at' => $expiresAt,
                'screen_id' => $screen->id,
            ]);

            return [
                'code' => $code,
                'expires_at' => $expiresAt->toIso8601String(),
                'screen_uuid' => $screenUuid,
            ];
        });
    }

    public function findAvailablePairingCode(string $code): ?PairingCode
    {
        $pairing = PairingCode::query()->where('code', $code)->first();

        if ($pairing === null || $pairing->isUsed() || $pairing->isExpired()) {
            return null;
        }

        return $pairing;
    }

    /**
     * @param  array{name: string, location?: string|null, description?: string|null}  $data
     */
    public function completeAdminPairing(PairingCode $pairing, array $data, User $user): Screen
    {
        if ($pairing->isUsed() || $pairing->isExpired()) {
            throw new RuntimeException('El código ya no es válido.');
        }

        return DB::transaction(function () use ($pairing, $data, $user) {
            $plainToken = $this->tokens->generatePlainToken();

            $screen = Screen::query()->where('uuid', $pairing->screen_uuid)->firstOrFail();

            $screen->fill([
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => ScreenStatus::Active,
                'device_token_hash' => $this->tokens->hashToken($plainToken),
                'token_last_rotated_at' => now(),
            ]);
            $screen->save();

            $pairing->update([
                'used_at' => now(),
                'screen_id' => $screen->id,
            ]);

            Cache::put(
                $this->tokenCacheKey($screen->uuid),
                $plainToken,
                now()->addMinutes(30)
            );

            $this->audit->log($user, 'screen.paired', $screen, [
                'pairing_code_id' => $pairing->id,
            ]);

            return $screen->fresh();
        });
    }

    /**
     * @return array{status: string, device_token?: string}
     */
    public function pollDeviceActivation(string $screenUuid): array
    {
        if (! Str::isUuid($screenUuid)) {
            return ['status' => 'invalid'];
        }

        $screen = Screen::query()->where('uuid', $screenUuid)->first();

        if ($screen === null) {
            return ['status' => 'waiting'];
        }

        if ($screen->status === ScreenStatus::Pending) {
            return ['status' => 'pending'];
        }

        if ($screen->status !== ScreenStatus::Active) {
            return ['status' => 'inactive'];
        }

        $cacheKey = $this->tokenCacheKey($screenUuid);
        $plainToken = Cache::get($cacheKey);

        if (is_string($plainToken) && $plainToken !== '') {
            Cache::forget($cacheKey);

            return [
                'status' => 'activated',
                'device_token' => $plainToken,
            ];
        }

        return ['status' => 'already_activated'];
    }

    public function revokeScreen(Screen $screen, User $user): void
    {
        DB::transaction(function () use ($screen, $user) {
            $screen->update([
                'status' => ScreenStatus::Revoked,
                'device_token_hash' => null,
            ]);

            Cache::forget($this->tokenCacheKey($screen->uuid));

            $this->audit->log($user, 'screen.revoked', $screen);
        });
    }

    private function invalidateOpenCodesForUuid(string $screenUuid): void
    {
        PairingCode::query()
            ->where('screen_uuid', $screenUuid)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }

    private function generateUniqueCode(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (! PairingCode::query()->where('code', $code)->where('expires_at', '>', now())->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('No fue posible generar un código de vinculación.');
    }

    private function tokenCacheKey(string $screenUuid): string
    {
        return 'device.token.'.$screenUuid;
    }
}
