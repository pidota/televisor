<?php

namespace App\Services;

use App\Enums\AssigneeType;
use App\Models\Schedule;
use App\Models\ScheduleTarget;
use App\Models\ScheduleTimeRule;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Models\User;
use App\Support\SettingStore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly AuditLogger $audit,
        private readonly ScreenManifestService $manifests,
    ) {}

    /**
     * @param  array{
     *   name: string,
     *   playlist_id: int,
     *   starts_at: string,
     *   ends_at: string,
     *   priority?: int,
     *   is_active?: bool,
     *   screens?: list<int>,
     *   groups?: list<int>,
     *   use_time_rules?: bool,
     *   days?: list<int>,
     *   start_time?: ?string,
     *   end_time?: ?string,
     * }  $data
     */
    public function create(array $data, User $user): Schedule
    {
        return DB::transaction(function () use ($data, $user) {
            $schedule = Schedule::query()->create([
                'name' => $data['name'],
                'playlist_id' => $data['playlist_id'],
                'starts_at' => $this->parseLocalDateTime($data['starts_at']),
                'ends_at' => $this->parseLocalDateTime($data['ends_at']),
                'priority' => $data['priority'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $user->id,
            ]);

            $this->syncTargets($schedule, $data['screens'] ?? [], $data['groups'] ?? []);
            $this->syncTimeRules($schedule, $data);
            $this->bumpManifestsForSchedule($schedule);

            $this->audit->log($user, 'schedule.created', $schedule);

            return $schedule->fresh(['targets', 'timeRules', 'playlist']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Schedule $schedule, array $data, User $user): Schedule
    {
        return DB::transaction(function () use ($schedule, $data, $user) {
            $schedule->fill([
                'name' => $data['name'] ?? $schedule->name,
                'playlist_id' => $data['playlist_id'] ?? $schedule->playlist_id,
                'starts_at' => isset($data['starts_at']) ? $this->parseLocalDateTime($data['starts_at']) : $schedule->starts_at,
                'ends_at' => isset($data['ends_at']) ? $this->parseLocalDateTime($data['ends_at']) : $schedule->ends_at,
                'priority' => $data['priority'] ?? $schedule->priority,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $schedule->is_active,
            ]);
            $schedule->save();

            if (array_key_exists('screens', $data) || array_key_exists('groups', $data)) {
                $this->syncTargets($schedule, $data['screens'] ?? [], $data['groups'] ?? []);
            }

            if (array_key_exists('use_time_rules', $data) || array_key_exists('days', $data)) {
                $this->syncTimeRules($schedule, $data);
            }

            $this->bumpManifestsForSchedule($schedule->fresh(['targets']));

            $this->audit->log($user, 'schedule.updated', $schedule);

            return $schedule->fresh(['targets', 'timeRules', 'playlist']);
        });
    }

    public function delete(Schedule $schedule, User $user): void
    {
        DB::transaction(function () use ($schedule, $user) {
            $this->bumpManifestsForSchedule($schedule);
            $this->audit->log($user, 'schedule.deleted', $schedule, ['name' => $schedule->name]);
            $schedule->delete();
        });
    }

    /**
     * @param  list<int>  $screenIds
     * @param  list<int>  $groupIds
     */
    private function syncTargets(Schedule $schedule, array $screenIds, array $groupIds): void
    {
        $schedule->targets()->delete();

        $screenIds = Screen::query()->whereIn('id', collect($screenIds)->map(fn ($id) => (int) $id))->pluck('id');
        foreach ($screenIds as $screenId) {
            ScheduleTarget::query()->create([
                'schedule_id' => $schedule->id,
                'target_type' => AssigneeType::Screen->value,
                'target_id' => $screenId,
            ]);
        }

        $groupIds = ScreenGroup::query()->whereIn('id', collect($groupIds)->map(fn ($id) => (int) $id))->pluck('id');
        foreach ($groupIds as $groupId) {
            ScheduleTarget::query()->create([
                'schedule_id' => $schedule->id,
                'target_type' => AssigneeType::ScreenGroup->value,
                'target_id' => $groupId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTimeRules(Schedule $schedule, array $data): void
    {
        $schedule->timeRules()->delete();

        if (empty($data['use_time_rules'])) {
            return;
        }

        $days = collect($data['days'] ?? [])->map(fn ($d) => (int) $d)->unique()->filter(fn ($d) => $d >= 1 && $d <= 7);
        $start = $this->normalizeTime($data['start_time'] ?? '08:00');
        $end = $this->normalizeTime($data['end_time'] ?? '17:00');

        foreach ($days as $day) {
            ScheduleTimeRule::query()->create([
                'schedule_id' => $schedule->id,
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }
    }

    private function normalizeTime(string $time): string
    {
        if (strlen($time) === 5) {
            return $time.':00';
        }

        return $time;
    }

    private function parseLocalDateTime(string $value): Carbon
    {
        $tz = $this->settings->get('app.timezone', config('app.timezone', 'UTC'));

        return Carbon::parse($value, $tz)->utc();
    }

    public function bumpManifestsForSchedule(Schedule $schedule): void
    {
        $schedule->loadMissing('targets');
        $screenIds = collect();

        foreach ($schedule->targets as $target) {
            if ($target->target_type === AssigneeType::Screen) {
                $screenIds->push($target->target_id);
            } elseif ($target->target_type === AssigneeType::ScreenGroup) {
                $group = ScreenGroup::query()->with('screens:id')->find($target->target_id);
                if ($group) {
                    $screenIds = $screenIds->merge($group->screens->pluck('id'));
                }
            }
        }

        $this->manifests->bumpForScreenIds($screenIds->unique()->values());
    }

    /**
     * @return array{screen_ids: list<int>, group_ids: list<int>}
     */
    public function targetIds(Schedule $schedule): array
    {
        $targets = $schedule->targets()->get();

        return [
            'screen_ids' => $targets
                ->filter(fn ($t) => $t->target_type === AssigneeType::Screen)
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'group_ids' => $targets
                ->filter(fn ($t) => $t->target_type === AssigneeType::ScreenGroup)
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<int>
     */
    public function timeRuleDays(Schedule $schedule): array
    {
        return $schedule->timeRules()->pluck('day_of_week')->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
    }

    public function sampleTimeRule(Schedule $schedule): ?ScheduleTimeRule
    {
        return $schedule->timeRules()->first();
    }
}
