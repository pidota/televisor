<?php

namespace App\Services;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Support\SettingStore;

class ScreenConnectivityService
{
    public function __construct(
        private readonly SettingStore $settings,
    ) {}

    public function isOnline(Screen $screen): bool
    {
        if ($screen->status !== ScreenStatus::Active || $screen->last_seen_at === null) {
            return false;
        }

        $threshold = $this->settings->getInt('device.offline_threshold_seconds', 180);

        return $screen->last_seen_at->gte(now()->subSeconds($threshold));
    }

    /**
     * Etiqueta para UI: online, offline, pending, disabled, revoked.
     */
    public function connectionLabel(Screen $screen): string
    {
        return match ($screen->status) {
            ScreenStatus::Pending => 'pending',
            ScreenStatus::Disabled => 'disabled',
            ScreenStatus::Revoked => 'revoked',
            ScreenStatus::Active => $this->isOnline($screen) ? 'online' : 'offline',
        };
    }
}
