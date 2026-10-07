<?php

namespace Tests\Feature;

use App\Enums\ScreenStatus;
use App\Enums\UrgentMessageLayout;
use App\Enums\UrgentMessageStatus;
use App\Models\UrgentMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesDeviceScreens;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use CreatesDeviceScreens;
    use RefreshDatabase;

    public function test_pair_requires_valid_uuid(): void
    {
        $this->postJson('/api/v1/device/pair', ['screen_uuid' => 'not-a-uuid'])
            ->assertStatus(422);
    }

    public function test_pair_returns_six_digit_code(): void
    {
        $uuid = (string) Str::uuid();

        $response = $this->postJson('/api/v1/device/pair', ['screen_uuid' => $uuid])
            ->assertOk()
            ->json('data');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $response['code']);
        $this->assertSame($uuid, $response['screen_uuid']);
    }

    public function test_device_config_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/device/config')
            ->assertStatus(401);
    }

    public function test_device_config_with_valid_token(): void
    {
        [, $token] = $this->createActiveScreenWithToken();

        $this->withToken($token)
            ->getJson('/api/v1/device/config')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'screen' => ['uuid', 'name', 'manifest_version'],
                    'sync' => ['heartbeat_interval_seconds', 'manifest_poll_seconds'],
                    'server_time',
                    'timezone',
                ],
            ]);
    }

    public function test_revoked_screen_cannot_access_api(): void
    {
        [, $token] = $this->createActiveScreenWithToken(['status' => ScreenStatus::Revoked]);

        $this->withToken($token)
            ->getJson('/api/v1/device/config')
            ->assertStatus(403);
    }

    public function test_heartbeat_updates_last_seen(): void
    {
        [$screen, $token] = $this->createActiveScreenWithToken(['last_seen_at' => null]);

        $this->withToken($token)
            ->postJson('/api/v1/device/heartbeat', [
                'status' => 'online',
                'app_version' => '1.3.0',
                'manifest_version' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.accepted', true);

        $this->assertNotNull($screen->fresh()->last_seen_at);
    }

    public function test_urgent_message_takes_priority_in_playlist_manifest(): void
    {
        [$screen, $token] = $this->createActiveScreenWithToken();
        $user = User::factory()->create();

        UrgentMessage::query()->create([
            'title' => 'Alerta test',
            'body' => 'Contenido urgente',
            'layout' => UrgentMessageLayout::TextFullscreen,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'priority' => 10,
            'status' => UrgentMessageStatus::Active,
            'applies_to_all_screens' => true,
            'created_by' => $user->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/device/playlist')
            ->assertOk()
            ->assertJsonPath('data.source', 'urgent')
            ->assertJsonPath('data.urgent_message.title', 'Alerta test')
            ->assertJsonPath('data.items', []);
    }
}
