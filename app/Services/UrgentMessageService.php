<?php

namespace App\Services;

use App\Enums\ScreenStatus;
use App\Enums\UrgentMessageLayout;
use App\Enums\UrgentMessageStatus;
use App\Models\Screen;
use App\Models\UrgentMessage;
use App\Models\UrgentMessageTarget;
use App\Models\User;
use App\Support\SettingStore;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class UrgentMessageService
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly AuditLogger $audit,
        private readonly ScreenManifestService $manifestBumps,
    ) {}

    public function activeForScreen(Screen $screen, ?CarbonInterface $at = null): ?UrgentMessage
    {
        $now = $at ? Carbon::instance($at) : now();
        if ($now->timezoneName !== 'UTC') {
            $now = $now->utc();
        }

        $query = UrgentMessage::query()
            ->where('status', UrgentMessageStatus::Active)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($builder) use ($screen) {
                $builder->where('applies_to_all_screens', true)
                    ->orWhereHas('targets', fn ($q) => $q->where('screen_id', $screen->id));
            })
            ->orderByDesc('priority')
            ->orderByDesc('starts_at');

        return $query->first();
    }

    /**
     * @param  array{
     *   title: string,
     *   body: string,
     *   starts_at: string,
     *   ends_at: string,
     *   priority?: int,
     *   layout?: string,
     *   applies_to_all_screens?: bool,
     *   screens?: list<int>,
     * }  $data
     */
    public function create(array $data, User $user, UrgentMessageStatus $status = UrgentMessageStatus::Draft): UrgentMessage
    {
        return DB::transaction(function () use ($data, $user, $status) {
            $message = UrgentMessage::query()->create([
                'title' => $data['title'],
                'body' => $data['body'],
                'layout' => $this->resolveLayout($data['layout'] ?? null),
                'starts_at' => $this->parseLocalDateTime($data['starts_at']),
                'ends_at' => $this->parseLocalDateTime($data['ends_at']),
                'priority' => $data['priority'] ?? 0,
                'status' => $status,
                'applies_to_all_screens' => (bool) ($data['applies_to_all_screens'] ?? false),
                'created_by' => $user->id,
            ]);

            $this->syncTargets($message, $data['screens'] ?? []);

            if ($status === UrgentMessageStatus::Active) {
                $this->bumpAffectedScreens($message);
            }

            $this->audit->log($user, 'urgent_message.created', $message);

            return $message->fresh(['targets']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(UrgentMessage $message, array $data, User $user): UrgentMessage
    {
        return DB::transaction(function () use ($message, $data, $user) {
            $wasActive = $message->status === UrgentMessageStatus::Active;

            $message->fill([
                'title' => $data['title'] ?? $message->title,
                'body' => $data['body'] ?? $message->body,
                'layout' => array_key_exists('layout', $data)
                    ? $this->resolveLayout($data['layout'])
                    : $message->layout,
                'starts_at' => isset($data['starts_at']) ? $this->parseLocalDateTime($data['starts_at']) : $message->starts_at,
                'ends_at' => isset($data['ends_at']) ? $this->parseLocalDateTime($data['ends_at']) : $message->ends_at,
                'priority' => $data['priority'] ?? $message->priority,
                'applies_to_all_screens' => array_key_exists('applies_to_all_screens', $data)
                    ? (bool) $data['applies_to_all_screens']
                    : $message->applies_to_all_screens,
            ]);
            $message->save();

            if (array_key_exists('screens', $data)) {
                $this->syncTargets($message, $data['screens'] ?? []);
            }

            if ($wasActive || $message->status === UrgentMessageStatus::Active) {
                $this->bumpAffectedScreens($message);
            }

            $this->audit->log($user, 'urgent_message.updated', $message);

            return $message->fresh(['targets']);
        });
    }

    public function activate(UrgentMessage $message, User $user): UrgentMessage
    {
        return DB::transaction(function () use ($message, $user) {
            $message->update(['status' => UrgentMessageStatus::Active]);
            $this->bumpAffectedScreens($message);
            $this->audit->log($user, 'urgent_message.activated', $message);

            return $message->fresh();
        });
    }

    public function cancel(UrgentMessage $message, User $user): UrgentMessage
    {
        return DB::transaction(function () use ($message, $user) {
            $message->update(['status' => UrgentMessageStatus::Cancelled]);
            $this->bumpAffectedScreens($message);
            $this->audit->log($user, 'urgent_message.cancelled', $message);

            return $message->fresh();
        });
    }

    /**
     * Mensajes activos cuya vigencia terminó: pasa a expired y fuerza nuevo manifiesto en pantallas.
     */
    public function expireDue(?CarbonInterface $at = null): int
    {
        $now = $at ? Carbon::instance($at) : now();
        if ($now->timezoneName !== 'UTC') {
            $now = $now->utc();
        }

        $due = UrgentMessage::query()
            ->where('status', UrgentMessageStatus::Active)
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($due as $message) {
            DB::transaction(function () use ($message) {
                $message->update(['status' => UrgentMessageStatus::Expired]);
                $this->bumpAffectedScreens($message);
            });
        }

        return $due->count();
    }

    public function delete(UrgentMessage $message, User $user): void
    {
        DB::transaction(function () use ($message, $user) {
            if ($message->status === UrgentMessageStatus::Active) {
                $this->bumpAffectedScreens($message);
            }
            $this->audit->log($user, 'urgent_message.deleted', $message);
            $message->targets()->delete();
            $message->delete();
        });
    }

    /**
     * @param  list<int>  $screenIds
     */
    private function syncTargets(UrgentMessage $message, array $screenIds): void
    {
        $message->targets()->delete();

        if ($message->applies_to_all_screens) {
            return;
        }

        $validIds = Screen::query()
            ->whereIn('id', collect($screenIds)->map(fn ($id) => (int) $id))
            ->pluck('id');

        foreach ($validIds as $screenId) {
            UrgentMessageTarget::query()->create([
                'urgent_message_id' => $message->id,
                'screen_id' => $screenId,
            ]);
        }
    }

    private function parseLocalDateTime(string $value): Carbon
    {
        $tz = $this->settings->get('app.timezone', config('app.timezone', 'UTC'));

        return Carbon::parse($value, $tz)->utc();
    }

    /**
     * @return list<int>
     */
    public function affectedScreenIds(UrgentMessage $message): array
    {
        if ($message->applies_to_all_screens) {
            return Screen::query()
                ->where('status', ScreenStatus::Active)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $message->targets()->pluck('screen_id')->map(fn ($id) => (int) $id)->all();
    }

    private function bumpAffectedScreens(UrgentMessage $message): void
    {
        $this->manifestBumps->bumpForScreenIds($this->affectedScreenIds($message));
    }

    private function resolveLayout(?string $value): UrgentMessageLayout
    {
        if ($value === null || $value === '') {
            return UrgentMessageLayout::TextTicker;
        }

        return UrgentMessageLayout::tryFrom($value) ?? UrgentMessageLayout::TextTicker;
    }
}
