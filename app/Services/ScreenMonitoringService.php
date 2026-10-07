<?php

namespace App\Services;

use App\Enums\ScreenStatus;
use App\Models\DeviceHeartbeat;
use App\Models\Screen;
use Illuminate\Support\Collection;

class ScreenMonitoringService
{
    public const ALERT_OFFLINE = 'offline';

    public const ALERT_MANIFEST_OUTDATED = 'manifest_outdated';

    public const ALERT_STORAGE_LOW = 'storage_low';

    public const ALERT_NEVER_CONNECTED = 'never_connected';

    public function __construct(
        private readonly ScreenConnectivityService $connectivity,
    ) {}

    /**
     * @return array{
     *   connection: string,
     *   manifest_status: string,
     *   expected_manifest_version: int,
     *   reported_manifest_version: ?int,
     *   storage_free_percent: ?float,
     *   alerts: list<string>,
     *   has_alerts: bool,
     * }
     */
    public function reportFor(Screen $screen): array
    {
        $connection = $this->connectivity->connectionLabel($screen);
        $expectedVersion = (int) $screen->manifest_version;
        $reportedVersion = $this->reportedManifestVersion($screen);

        $manifestStatus = $this->manifestSyncStatus($connection, $expectedVersion, $reportedVersion);
        $storagePercent = $this->storageFreePercent($screen);

        $alerts = $this->buildAlerts($screen, $connection, $manifestStatus, $storagePercent);

        return [
            'connection' => $connection,
            'manifest_status' => $manifestStatus,
            'expected_manifest_version' => $expectedVersion,
            'reported_manifest_version' => $reportedVersion,
            'storage_free_percent' => $storagePercent,
            'alerts' => $alerts,
            'has_alerts' => $alerts !== [],
        ];
    }

    public function enrich(Screen $screen): Screen
    {
        $screen->setAttribute('connection_label', $this->connectivity->connectionLabel($screen));
        $screen->setAttribute('monitoring', $this->reportFor($screen));

        return $screen;
    }

    /**
     * @param  Collection<int, Screen>  $screens
     * @return list<array{screen: Screen, alerts: list<string>}>
     */
    public function alertFeed(Collection $screens): array
    {
        $feed = [];

        foreach ($screens as $screen) {
            $monitoring = $screen->getAttribute('monitoring') ?? $this->reportFor($screen);
            if ($monitoring['has_alerts']) {
                $feed[] = [
                    'screen' => $screen,
                    'alerts' => $monitoring['alerts'],
                ];
            }
        }

        return $feed;
    }

    /**
     * @param  Collection<int, Screen>  $screens
     * @return array<string, int>
     */
    public function alertCounts(Collection $screens): array
    {
        $counts = [
            self::ALERT_OFFLINE => 0,
            self::ALERT_MANIFEST_OUTDATED => 0,
            self::ALERT_STORAGE_LOW => 0,
            self::ALERT_NEVER_CONNECTED => 0,
        ];

        foreach ($screens as $screen) {
            $monitoring = $screen->getAttribute('monitoring') ?? $this->reportFor($screen);
            foreach ($monitoring['alerts'] as $alert) {
                if (isset($counts[$alert])) {
                    $counts[$alert]++;
                }
            }
        }

        return $counts;
    }

    private function reportedManifestVersion(Screen $screen): ?int
    {
        if ($screen->relationLoaded('latestHeartbeat') && $screen->latestHeartbeat instanceof DeviceHeartbeat) {
            return (int) $screen->latestHeartbeat->manifest_version;
        }

        return null;
    }

    private function manifestSyncStatus(string $connection, int $expected, ?int $reported): string
    {
        if ($connection !== 'online' || $reported === null) {
            return 'unknown';
        }

        return $reported >= $expected ? 'synced' : 'outdated';
    }

    private function storageFreePercent(Screen $screen): ?float
    {
        $total = $screen->storage_total_bytes;
        $free = $screen->storage_free_bytes;

        if ($total === null || $free === null || $total <= 0) {
            return null;
        }

        return round(($free / $total) * 100, 1);
    }

    /**
     * @return list<string>
     */
    private function buildAlerts(
        Screen $screen,
        string $connection,
        string $manifestStatus,
        ?float $storagePercent,
    ): array {
        $alerts = [];

        if ($screen->status === ScreenStatus::Active && $screen->last_seen_at === null) {
            $alerts[] = self::ALERT_NEVER_CONNECTED;
        }

        if ($connection === 'offline' && $screen->status === ScreenStatus::Active) {
            $alerts[] = self::ALERT_OFFLINE;
        }

        if ($manifestStatus === 'outdated') {
            $alerts[] = self::ALERT_MANIFEST_OUTDATED;
        }

        if ($storagePercent !== null && $storagePercent < 10) {
            $alerts[] = self::ALERT_STORAGE_LOW;
        }

        return $alerts;
    }
}
