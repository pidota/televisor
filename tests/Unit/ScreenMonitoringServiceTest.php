<?php

namespace Tests\Unit;

use App\Enums\ScreenStatus;
use App\Models\DeviceHeartbeat;
use App\Models\Screen;
use App\Services\ScreenMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScreenMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_outdated_manifest_when_online(): void
    {
        $screen = Screen::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test',
            'status' => ScreenStatus::Active,
            'manifest_version' => 5,
            'last_seen_at' => now(),
        ]);

        DeviceHeartbeat::query()->create([
            'screen_id' => $screen->id,
            'manifest_version' => 3,
            'status' => 'online',
        ]);

        $screen->load('latestHeartbeat');

        $service = app(ScreenMonitoringService::class);
        $report = $service->reportFor($screen);

        $this->assertSame('outdated', $report['manifest_status']);
        $this->assertContains(ScreenMonitoringService::ALERT_MANIFEST_OUTDATED, $report['alerts']);
    }

    public function test_manifest_synced_when_versions_match(): void
    {
        $screen = Screen::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'OK',
            'status' => ScreenStatus::Active,
            'manifest_version' => 2,
            'last_seen_at' => now(),
        ]);

        DeviceHeartbeat::query()->create([
            'screen_id' => $screen->id,
            'manifest_version' => 2,
        ]);

        $screen->load('latestHeartbeat');

        $report = app(ScreenMonitoringService::class)->reportFor($screen);

        $this->assertSame('synced', $report['manifest_status']);
        $this->assertNotContains(ScreenMonitoringService::ALERT_MANIFEST_OUTDATED, $report['alerts']);
    }
}
