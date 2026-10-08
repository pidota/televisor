<?php

namespace App\Http\Controllers;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Services\LiveBroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveBroadcastController extends Controller
{
    public function __construct(
        private readonly LiveBroadcastService $live,
    ) {}

    public function index(): View
    {
        $this->authorize('manage-content');

        return view('live.index', [
            'enabled' => $this->live->isEnabled(),
            'allScreens' => $this->live->appliesToAllScreens(),
            'selectedScreens' => $this->live->selectedScreenIds(),
            'screens' => Screen::query()
                ->where('status', ScreenStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
            'rtmpUrl' => (string) config('live.rtmp_url'),
            'streamKey' => (string) config('live.stream_key'),
            'publishUser' => (string) config('live.publish_user'),
            'publishPassword' => (string) config('live.publish_password'),
            'obsServer' => rtrim((string) config('live.rtmp_url'), '/').'/'.config('live.stream_key')
                .'?user='.rawurlencode((string) config('live.publish_user'))
                .'&pass='.rawurlencode((string) config('live.publish_password')),
            'hlsUrl' => $this->live->hlsUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-content');

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'applies_to_all_screens' => ['nullable', 'boolean'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
        ]);

        $enabled = $request->boolean('enabled');
        $allScreens = $request->boolean('applies_to_all_screens');
        $screens = $data['screens'] ?? [];

        if ($enabled && ! $allScreens && $screens === []) {
            return back()
                ->withErrors(['screens' => 'Elige al menos una pantalla o marca todas.'])
                ->withInput();
        }

        $this->live->save($enabled, $allScreens, $screens);

        return redirect()
            ->route('live.index')
            ->with('status', $enabled
                ? 'Transmisión en vivo activada en las pantallas elegidas.'
                : 'Transmisión en vivo detenida. Las pantallas vuelven a su playlist.');
    }
}
