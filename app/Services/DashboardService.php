<?php

namespace App\Services;

use App\Enums\PlaylistStatus;
use App\Enums\ScreenStatus;
use App\Enums\UrgentMessageStatus;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\UrgentMessage;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ScreenConnectivityService $connectivity,
        private readonly ScreenMonitoringService $monitoring,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        $screensQuery = Screen::query();
        $activeScreens = (clone $screensQuery)->where('status', ScreenStatus::Active);

        $onlineCount = (clone $activeScreens)
            ->get()
            ->filter(fn (Screen $screen) => $this->connectivity->isOnline($screen))
            ->count();

        $registeredActive = (clone $activeScreens)->count();

        $monitored = $this->monitoredScreens(null);
        $alertCounts = $this->monitoring->alertCounts($monitored);

        return [
            'screens_total' => $screensQuery->count(),
            'screens_active' => $registeredActive,
            'screens_online' => $onlineCount,
            'screens_offline' => max($registeredActive - $onlineCount, 0),
            'screens_with_alerts' => $monitored->filter(
                fn (Screen $s) => ($s->getAttribute('monitoring')['has_alerts'] ?? false)
            )->count(),
            'alerts_offline' => $alertCounts[ScreenMonitoringService::ALERT_OFFLINE],
            'alerts_manifest_outdated' => $alertCounts[ScreenMonitoringService::ALERT_MANIFEST_OUTDATED],
            'alerts_storage_low' => $alertCounts[ScreenMonitoringService::ALERT_STORAGE_LOW],
            'media_total' => MediaAsset::query()->count(),
            'playlists_active' => Playlist::query()->where('status', PlaylistStatus::Active)->count(),
            'schedules_active' => Schedule::query()
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->count(),
            'urgent_messages_active' => UrgentMessage::query()
                ->where('status', UrgentMessageStatus::Active)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->count(),
            'storage_used_bytes' => (int) MediaAsset::query()->sum('size_bytes'),
        ];
    }

    /**
     * @return Collection<int, Screen>
     */
    public function monitoredScreens(?string $filter): Collection
    {
        $screens = Screen::query()
            ->with([
                'currentPlaylist:id,name',
                'currentMediaAsset:id,name',
                'latestHeartbeat',
            ])
            ->orderByRaw('CASE WHEN last_seen_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get()
            ->map(fn (Screen $screen) => $this->monitoring->enrich($screen));

        return match ($filter) {
            'online' => $screens->filter(
                fn (Screen $s) => ($s->getAttribute('monitoring')['connection'] ?? '') === 'online'
            )->values(),
            'offline' => $screens->filter(
                fn (Screen $s) => ($s->getAttribute('monitoring')['connection'] ?? '') === 'offline'
            )->values(),
            'alerts' => $screens->filter(
                fn (Screen $s) => ($s->getAttribute('monitoring')['has_alerts'] ?? false)
            )->values(),
            default => $screens,
        };
    }

    /**
     * @return list<array{screen: Screen, alerts: list<string>}>
     */
    public function alertFeed(): array
    {
        return $this->monitoring->alertFeed($this->monitoredScreens(null));
    }

    /**
     * @return Collection<int, Screen>
     */
    public function screenStatuses(): Collection
    {
        return $this->monitoredScreens(null);
    }
}
