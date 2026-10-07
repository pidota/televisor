<?php

namespace App\Http\Controllers;

use App\Enums\PlaylistStatus;
use App\Enums\ScreenStatus;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Services\ScheduleService;
use App\Support\SettingStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $schedules,
        private readonly SettingStore $settings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Schedule::class);

        $query = Schedule::query()
            ->with(['playlist:id,name', 'creator:id,name'])
            ->withCount('targets')
            ->orderByDesc('starts_at');

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $activeFilter = $request->string('active')->toString();
        if ($activeFilter === '1') {
            $query->where('is_active', true);
        } elseif ($activeFilter === '0') {
            $query->where('is_active', false);
        }

        return view('schedules.index', [
            'schedules' => $query->paginate(15)->withQueryString(),
            'filters' => ['q' => $search, 'active' => $activeFilter],
            'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Schedule::class);

        return view('schedules.form', [
            'schedule' => new Schedule(['is_active' => true, 'priority' => 0]),
            'mode' => 'create',
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $schedule = $this->schedules->create($request->validated(), $request->user());

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', 'Programación creada.');
    }

    public function show(Schedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load(['playlist', 'targets', 'timeRules', 'creator:id,name']);
        $targets = $this->schedules->targetIds($schedule);
        $tz = $this->settings->get('app.timezone', config('app.timezone'));

        return view('schedules.show', [
            'schedule' => $schedule,
            'targets' => $targets,
            'timezone' => $tz,
            'days' => $this->schedules->timeRuleDays($schedule),
            'timeSample' => $this->schedules->sampleTimeRule($schedule),
        ]);
    }

    public function edit(Schedule $schedule): View
    {
        $this->authorize('update', $schedule);

        $schedule->load(['timeRules', 'targets']);
        $assigned = $this->schedules->targetIds($schedule);
        $timeSample = $this->schedules->sampleTimeRule($schedule);

        return view('schedules.form', [
            'schedule' => $schedule,
            'mode' => 'edit',
            'assignedScreenIds' => $assigned['screen_ids'],
            'assignedGroupIds' => $assigned['group_ids'],
            'timeRuleDays' => $this->schedules->timeRuleDays($schedule),
            'timeStart' => $timeSample ? substr((string) $timeSample->start_time, 0, 5) : '08:00',
            'timeEnd' => $timeSample ? substr((string) $timeSample->end_time, 0, 5) : '17:30',
            'useTimeRules' => $schedule->timeRules->isNotEmpty(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->schedules->update($schedule, $request->validated(), $request->user());

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', 'Programación actualizada.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $this->schedules->delete($schedule, auth()->user());

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Programación eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'playlists' => Playlist::query()
                ->whereIn('status', [PlaylistStatus::Active, PlaylistStatus::Draft])
                ->orderBy('name')
                ->get(['id', 'name']),
            'screens' => Screen::query()
                ->where('status', ScreenStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
            'groups' => ScreenGroup::query()->orderBy('name')->get(['id', 'name']),
            'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
            'weekdays' => [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo',
            ],
        ];
    }
}
