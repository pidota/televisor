<?php

namespace App\Support;

use App\Models\Screen;
use Illuminate\Http\Request;

trait DeviceRequest
{
    protected function deviceScreen(Request $request): Screen
    {
        /** @var Screen|null $screen */
        $screen = $request->attributes->get('device_screen');

        if ($screen === null) {
            abort(401, 'Dispositivo no autenticado.');
        }

        return $screen;
    }
}
