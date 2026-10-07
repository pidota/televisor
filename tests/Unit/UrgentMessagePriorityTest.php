<?php

namespace Tests\Unit;

use App\Enums\ScreenStatus;
use App\Enums\UrgentMessageLayout;
use App\Enums\UrgentMessageStatus;
use App\Models\Screen;
use App\Models\UrgentMessage;
use App\Models\User;
use App\Services\ScreenContentResolver;
use App\Services\UrgentMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UrgentMessagePriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_urgent_message_overrides_playlist_resolution(): void
    {
        $user = User::factory()->create();

        $screen = Screen::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Lobby',
            'location' => 'Municipalidad',
            'status' => ScreenStatus::Active,
            'manifest_version' => 1,
        ]);

        UrgentMessage::query()->create([
            'title' => 'Alerta',
            'body' => 'Mensaje de prueba',
            'layout' => UrgentMessageLayout::TextFullscreen,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'priority' => 10,
            'status' => UrgentMessageStatus::Active,
            'applies_to_all_screens' => true,
            'created_by' => $user->id,
        ]);

        /** @var ScreenContentResolver $resolver */
        $resolver = app(ScreenContentResolver::class);

        $description = $resolver->describe($screen);

        $this->assertSame('urgent', $description['source']);
        $this->assertNull($description['playlist']);
        $this->assertSame('Alerta', $description['urgent_message']->title);
    }

    public function test_expire_due_marks_message_and_stops_resolution(): void
    {
        $user = User::factory()->create();

        $screen = Screen::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala',
            'location' => 'Test',
            'status' => ScreenStatus::Active,
            'manifest_version' => 1,
        ]);

        $message = UrgentMessage::query()->create([
            'title' => 'Vencido',
            'body' => 'Ya no aplica',
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subMinute(),
            'priority' => 5,
            'status' => UrgentMessageStatus::Active,
            'applies_to_all_screens' => true,
            'created_by' => $user->id,
        ]);

        /** @var UrgentMessageService $service */
        $service = app(UrgentMessageService::class);

        $this->assertSame(1, $service->expireDue());
        $this->assertSame(UrgentMessageStatus::Expired, $message->fresh()->status);

        /** @var ScreenContentResolver $resolver */
        $resolver = app(ScreenContentResolver::class);
        $this->assertNotSame('urgent', $resolver->describe($screen)['source']);
    }

    public function test_ticker_message_does_not_replace_playlist_source(): void
    {
        $user = User::factory()->create();

        $screen = Screen::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Recepción',
            'location' => 'Municipalidad',
            'status' => ScreenStatus::Active,
            'manifest_version' => 1,
        ]);

        UrgentMessage::query()->create([
            'title' => 'Aviso',
            'body' => 'TEXTO EN CINTA INFERIOR',
            'layout' => UrgentMessageLayout::TextTicker,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'priority' => 10,
            'status' => UrgentMessageStatus::Active,
            'applies_to_all_screens' => true,
            'created_by' => $user->id,
        ]);

        $description = app(ScreenContentResolver::class)->describe($screen);

        $this->assertNotSame('urgent', $description['source']);
        $this->assertSame('TEXTO EN CINTA INFERIOR', $description['urgent_message']->body);
        $this->assertSame(UrgentMessageLayout::TextTicker, $description['urgent_message']->layout);
    }
}
