<?php

namespace App\Http\Controllers;

use App\Enums\ScreenStatus;
use App\Enums\UrgentMessageLayout;
use App\Enums\UrgentMessageStatus;
use App\Http\Requests\UrgentMessage\StoreUrgentMessageRequest;
use App\Http\Requests\UrgentMessage\UpdateUrgentMessageRequest;
use App\Models\Screen;
use App\Models\UrgentMessage;
use App\Services\UrgentMessageService;
use App\Support\SettingStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UrgentMessageController extends Controller
{
    public function __construct(
        private readonly UrgentMessageService $urgentMessages,
        private readonly SettingStore $settings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', UrgentMessage::class);

        $query = UrgentMessage::query()
            ->with('creator:id,name')
            ->withCount('targets')
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('urgent-messages.index', [
            'messages' => $query->paginate(15)->withQueryString(),
            'filters' => ['status' => $status ?? ''],
            'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', UrgentMessage::class);

        return view('urgent-messages.form', [
            'urgentMessage' => new UrgentMessage([
                'priority' => 10,
                'applies_to_all_screens' => false,
                'layout' => UrgentMessageLayout::TextTicker,
            ]),
            'layouts' => UrgentMessageLayout::cases(),
            'mode' => 'create',
            'screens' => Screen::query()->where('status', ScreenStatus::Active)->orderBy('name')->get(['id', 'name', 'location']),
            'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
            'selectedScreens' => [],
        ]);
    }

    public function store(StoreUrgentMessageRequest $request): RedirectResponse
    {
        $status = $request->boolean('publish') ? UrgentMessageStatus::Active : UrgentMessageStatus::Draft;

        $message = $this->urgentMessages->create($request->validated(), $request->user(), $status);

        return redirect()
            ->route('urgent-messages.show', $message)
            ->with('success', $status === UrgentMessageStatus::Active
                ? 'Mensaje urgente publicado.'
                : 'Borrador guardado.');
    }

    public function show(UrgentMessage $urgentMessage): View
    {
        $this->authorize('view', $urgentMessage);

        $urgentMessage->load(['creator:id,name', 'targets.screen:id,name,location']);
        $tz = $this->settings->get('app.timezone', config('app.timezone'));

        return view('urgent-messages.show', [
            'message' => $urgentMessage,
            'timezone' => $tz,
            'affectedCount' => count($this->urgentMessages->affectedScreenIds($urgentMessage)),
        ]);
    }

    public function edit(UrgentMessage $urgentMessage): View
    {
        $this->authorize('update', $urgentMessage);

        $urgentMessage->load('targets');

        return view('urgent-messages.form', [
            'urgentMessage' => $urgentMessage,
            'mode' => 'edit',
            'layouts' => UrgentMessageLayout::cases(),
            'screens' => Screen::query()->where('status', ScreenStatus::Active)->orderBy('name')->get(['id', 'name', 'location']),
            'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
            'selectedScreens' => $urgentMessage->targets->pluck('screen_id')->all(),
        ]);
    }

    public function update(UpdateUrgentMessageRequest $request, UrgentMessage $urgentMessage): RedirectResponse
    {
        $this->urgentMessages->update($urgentMessage, $request->validated(), $request->user());

        if ($request->boolean('publish') && $urgentMessage->status === UrgentMessageStatus::Draft) {
            $this->urgentMessages->activate($urgentMessage, $request->user());
        }

        return redirect()
            ->route('urgent-messages.show', $urgentMessage)
            ->with('success', 'Mensaje actualizado.');
    }

    public function activate(UrgentMessage $urgentMessage): RedirectResponse
    {
        $this->authorize('update', $urgentMessage);

        $this->urgentMessages->activate($urgentMessage, auth()->user());

        return back()->with('success', 'Mensaje activado en pantallas.');
    }

    public function cancel(UrgentMessage $urgentMessage): RedirectResponse
    {
        $this->authorize('update', $urgentMessage);

        $this->urgentMessages->cancel($urgentMessage, auth()->user());

        return back()->with('success', 'Mensaje cancelado. Las pantallas volverán a su programación.');
    }

    public function destroy(UrgentMessage $urgentMessage): RedirectResponse
    {
        $this->authorize('delete', $urgentMessage);

        $this->urgentMessages->delete($urgentMessage, auth()->user());

        return redirect()
            ->route('urgent-messages.index')
            ->with('success', 'Mensaje eliminado.');
    }
}
