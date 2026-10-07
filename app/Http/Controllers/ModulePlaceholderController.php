<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ModulePlaceholderController extends Controller
{
    public function show(string $module): View
    {
        $titles = [
            'screens' => 'Pantallas',
            'videos' => 'Videos',
            'images' => 'Imágenes',
            'playlists' => 'Playlists',
            'schedules' => 'Programación',
            'urgent-messages' => 'Mensajes urgentes',
            'users' => 'Usuarios',
            'settings' => 'Configuración',
        ];

        return view('modules.placeholder', [
            'title' => $titles[$module] ?? 'Módulo',
        ]);
    }
}
