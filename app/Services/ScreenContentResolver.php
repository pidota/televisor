<?php

namespace App\Services;

use App\Enums\AssigneeType;
use App\Enums\UrgentMessageLayout;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Support\SettingStore;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ScreenContentResolver
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly PlaylistAssignmentService $assignments,
        private readonly UrgentMessageService $urgentMessages,
        private readonly LiveBroadcastService $liveBroadcast,
    ) {}

    public function resolvePlaylist(Screen $screen, ?CarbonInterface $at = null): ?Playlist
    {
        $urgent = $this->urgentMessages->activeForScreen($screen, $at);
        if ($urgent !== null && $urgent->layout->overridesPlaylist()) {
            return null;
        }

        $scheduled = $this->resolveScheduledPlaylist($screen, $at);
        if ($scheduled !== null) {
            return $scheduled;
        }

        return $this->assignments->defaultPlaylistForScreen($screen);
    }

    public function resolveScheduledPlaylist(Screen $screen, ?CarbonInterface $at = null): ?Playlist
    {
        $utc = $at ? Carbon::instance($at)->utc() : now()->utc();
        $tz = $this->settings->get('app.timezone', config('app.timezone', 'UTC'));
        $local = $utc->copy()->timezone($tz);

        $groupIds = $screen->groups()->pluck('screen_groups.id');

        $schedules = Schedule::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', $utc)
            ->where('ends_at', '>=', $utc)
            ->whereHas('targets', function ($query) use ($screen, $groupIds) {
                $query->where(function ($inner) use ($screen) {
                    $inner->where('target_type', AssigneeType::Screen->value)
                        ->where('target_id', $screen->id);
                });

                if ($groupIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($groupIds) {
                        $inner->where('target_type', AssigneeType::ScreenGroup->value)
                            ->whereIn('target_id', $groupIds);
                    });
                }
            })
            ->with(['timeRules', 'playlist'])
            ->orderByDesc('priority')
            ->orderByDesc('starts_at')
            ->get();

        foreach ($schedules as $schedule) {
            if ($this->matchesTimeRules($schedule, $local)) {
                return $schedule->playlist;
            }
        }

        return null;
    }

    private function matchesTimeRules(Schedule $schedule, CarbonInterface $local): bool
    {
        if ($schedule->timeRules->isEmpty()) {
            return true;
        }

        $day = $local->dayOfWeekIso;
        $time = $local->format('H:i:s');

        foreach ($schedule->timeRules as $rule) {
            if ((int) $rule->day_of_week !== $day) {
                continue;
            }

            $start = is_string($rule->start_time) ? $rule->start_time : $rule->start_time->format('H:i:s');
            $end = is_string($rule->end_time) ? $rule->end_time : $rule->end_time->format('H:i:s');

            if ($time >= $start && $time <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{source: string, playlist: ?Playlist, schedule: ?Schedule}
     */
    public function describe(Screen $screen, ?CarbonInterface $at = null): array
    {
        $urgent = $this->urgentMessages->activeForScreen($screen, $at);
        if ($urgent !== null && $urgent->layout->overridesPlaylist()) {
            return [
                'source' => 'urgent',
                'playlist' => null,
                'schedule' => null,
                'urgent_message' => $urgent,
            ];
        }

        if ($this->liveBroadcast->appliesTo($screen)) {
            return [
                'source' => 'live',
                'playlist' => null,
                'schedule' => null,
                'urgent_message' => $this->tickerOverlayMessage($urgent),
                'live_url' => $this->liveBroadcast->hlsUrl(),
            ];
        }

        $utc = $at ? Carbon::instance($at)->utc() : now()->utc();
        $schedule = $this->findWinningSchedule($screen, $utc);

        if ($schedule?->playlist) {
            return [
                'source' => 'schedule',
                'playlist' => $schedule->playlist,
                'schedule' => $schedule,
                'urgent_message' => $this->tickerOverlayMessage($urgent),
            ];
        }

        $assigned = $this->assignments->defaultPlaylistForScreen($screen);

        return [
            'source' => $assigned ? 'assignment' : 'none',
            'playlist' => $assigned,
            'schedule' => null,
            'urgent_message' => $this->tickerOverlayMessage($urgent),
        ];
    }

    private function tickerOverlayMessage(?\App\Models\UrgentMessage $urgent): ?\App\Models\UrgentMessage
    {
        if ($urgent === null || $urgent->layout !== UrgentMessageLayout::TextTicker) {
            return null;
        }

        return $urgent;
    }

    private function findWinningSchedule(Screen $screen, CarbonInterface $utc): ?Schedule
    {
        $tz = $this->settings->get('app.timezone', config('app.timezone', 'UTC'));
        $local = $utc->copy()->timezone($tz);
        $groupIds = $screen->groups()->pluck('screen_groups.id');

        $schedules = Schedule::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', $utc)
            ->where('ends_at', '>=', $utc)
            ->whereHas('targets', function ($query) use ($screen, $groupIds) {
                $query->where(function ($inner) use ($screen) {
                    $inner->where('target_type', AssigneeType::Screen->value)
                        ->where('target_id', $screen->id);
                });
                if ($groupIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($groupIds) {
                        $inner->where('target_type', AssigneeType::ScreenGroup->value)
                            ->whereIn('target_id', $groupIds);
                    });
                }
            })
            ->with(['timeRules', 'playlist'])
            ->orderByDesc('priority')
            ->get();

        foreach ($schedules as $schedule) {
            if ($this->matchesTimeRules($schedule, $local)) {
                return $schedule;
            }
        }

        return null;
    }
}
