<?php

namespace App\Http\Middleware;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Services\DeviceTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function __construct(
        private readonly DeviceTokenService $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null || $plainToken === '') {
            return response()->json(['message' => 'Token de dispositivo requerido.'], 401);
        }

        $hash = $this->tokens->hashToken($plainToken);

        $screen = Screen::query()->where('device_token_hash', $hash)->first();

        if ($screen === null) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        if ($screen->status === ScreenStatus::Revoked) {
            return response()->json(['message' => 'Pantalla revocada.'], 403);
        }

        if ($screen->status === ScreenStatus::Disabled) {
            return response()->json(['message' => 'Pantalla deshabilitada.'], 403);
        }

        if ($screen->status !== ScreenStatus::Active) {
            return response()->json(['message' => 'Pantalla no activa.'], 403);
        }

        $request->attributes->set('device_screen', $screen);

        return $next($request);
    }
}
