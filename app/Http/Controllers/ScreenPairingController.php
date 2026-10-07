<?php

namespace App\Http\Controllers;

use App\Http\Requests\Screen\CompletePairingRequest;
use App\Http\Requests\Screen\VerifyPairingCodeRequest;
use App\Models\PairingCode;
use App\Models\Screen;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class ScreenPairingController extends Controller
{
    public function __construct(
        private readonly PairingService $pairing,
    ) {}

    public function create(): View
    {
        $this->authorize('pair', Screen::class);

        return view('screens.pair');
    }

    public function verify(VerifyPairingCodeRequest $request): RedirectResponse
    {
        $pairing = $this->pairing->findAvailablePairingCode($request->string('code')->toString());

        if ($pairing === null) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Código inválido, expirado o ya utilizado.']);
        }

        return redirect()->route('screens.pair.complete', ['pairing' => $pairing->id]);
    }

    public function completeForm(PairingCode $pairing): View|RedirectResponse
    {
        $this->authorize('pair', Screen::class);

        if ($pairing->isUsed() || $pairing->isExpired()) {
            return redirect()
                ->route('screens.pair.create')
                ->with('error', 'El código ya no es válido. Solicite uno nuevo en el televisor.');
        }

        return view('screens.pair-complete', [
            'pairing' => $pairing,
        ]);
    }

    public function complete(CompletePairingRequest $request, PairingCode $pairing): RedirectResponse
    {
        try {
            $screen = $this->pairing->completeAdminPairing(
                $pairing,
                $request->safe()->only(['name', 'location', 'description']),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('screens.pair.create')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('screens.show', $screen)
            ->with('success', 'Pantalla vinculada correctamente. El televisor recibirá el token al consultar activación.');
    }
}
