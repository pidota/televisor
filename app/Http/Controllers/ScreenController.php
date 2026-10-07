<?php

namespace App\Http\Controllers;

use App\Enums\ScreenStatus;
use App\Http\Requests\Screen\UpdateScreenRequest;
use App\Models\Screen;
use App\Services\AuditLogger;
use App\Services\PairingService;
use App\Services\ScreenConnectivityService;
use App\Services\ScreenContentResolver;
use App\Services\ScreenMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function __construct(
        private readonly ScreenConnectivityService $connectivity,
        private readonly PairingService $pairing,
        private readonly AuditLogger $audit,
        private readonly ScreenContentResolver $contentResolver,
        private readonly ScreenMonitoringService $monitoring,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Screen::class);

        $query = Screen::query()->with(['currentPlaylist:id,name']);

        $search = $request->string('q')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        $statusFilter = $request->string('status')->toString();
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $connectionFilter = $request->string('connection')->toString();
        if ($connectionFilter === 'online' || $connectionFilter === 'offline') {
            $screens = $query->orderByDesc('updated_at')->get();
            $screens = $screens->filter(function (Screen $screen) use ($connectionFilter) {
                $label = $this->connectivity->connectionLabel($screen);

                return $connectionFilter === 'online'
                    ? $label === 'online'
                    : $label === 'offline';
            });
            $screens = new \Illuminate\Pagination\LengthAwarePaginator(
                $screens->forPage($request->integer('page', 1), 15)->values(),
                $screens->count(),
                15,
                $request->integer('page', 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $screens = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();
        }

        $screens->getCollection()->transform(function (Screen $screen) {
            $screen->setAttribute('connection_label', $this->connectivity->connectionLabel($screen));

            return $screen;
        });

        return view('screens.index', [
            'screens' => $screens,
            'filters' => [
                'q' => $search ?? '',
                'status' => $statusFilter,
                'connection' => $connectionFilter,
            ],
        ]);
    }

    public function show(Screen $screen): View
    {
        $this->authorize('view', $screen);

        $screen->load([
            'currentPlaylist:id,name',
            'currentMediaAsset:id,name',
            'groups:id,name',
            'playlistAssignments.playlist:id,name',
            'latestHeartbeat',
            'pairingCodes' => fn ($q) => $q->orderByDesc('created_at')->limit(5),
        ]);

        $this->monitoring->enrich($screen);
        $content = $this->contentResolver->describe($screen);

        $heartbeats = $screen->heartbeats()
            ->with(['playlist:id,name', 'mediaAsset:id,name'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('screens.show', compact('screen', 'content', 'heartbeats'));
    }

    public function edit(Screen $screen): View
    {
        $this->authorize('update', $screen);

        return view('screens.edit', compact('screen'));
    }

    public function update(UpdateScreenRequest $request, Screen $screen): RedirectResponse
    {
        $screen->update($request->validated());

        $this->audit->log($request->user(), 'screen.updated', $screen, $request->validated());

        return redirect()
            ->route('screens.show', $screen)
            ->with('success', 'Pantalla actualizada correctamente.');
    }

    public function disable(Screen $screen): RedirectResponse
    {
        $this->authorize('disable', $screen);

        if ($screen->status === ScreenStatus::Revoked) {
            return back()->with('error', 'No se puede deshabilitar una pantalla revocada.');
        }

        $screen->update(['status' => ScreenStatus::Disabled]);

        $this->audit->log(auth()->user(), 'screen.disabled', $screen);

        return back()->with('success', 'Pantalla deshabilitada.');
    }

    public function enable(Screen $screen): RedirectResponse
    {
        $this->authorize('update', $screen);

        if ($screen->status === ScreenStatus::Revoked) {
            return back()->with('error', 'Reactive la pantalla desde una nueva vinculación.');
        }

        if ($screen->device_token_hash === null) {
            return back()->with('error', 'La pantalla no tiene token; debe vincularse primero.');
        }

        $screen->update(['status' => ScreenStatus::Active]);

        $this->audit->log(auth()->user(), 'screen.enabled', $screen);

        return back()->with('success', 'Pantalla habilitada.');
    }

    public function revoke(Screen $screen): RedirectResponse
    {
        $this->authorize('revoke', $screen);

        $this->pairing->revokeScreen($screen, auth()->user());

        return back()->with('success', 'Pantalla revocada. El dispositivo ya no podrá autenticarse.');
    }

    public function destroy(Screen $screen): RedirectResponse
    {
        $this->authorize('delete', $screen);

        $this->audit->log(auth()->user(), 'screen.deleted', $screen);
        $screen->delete();

        return redirect()
            ->route('screens.index')
            ->with('success', 'Pantalla eliminada.');
    }
}
