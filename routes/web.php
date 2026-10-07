<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\PlaylistAssignmentController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScreenGroupController;
use App\Http\Controllers\UrgentMessageController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\ScreenPairingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('pantallas', [ScreenController::class, 'index'])->name('screens.index');
    Route::get('pantallas/grupos', [ScreenGroupController::class, 'index'])->name('screen-groups.index');

    Route::middleware('can:manage-content')->group(function () {
        Route::get('pantallas/grupos/nuevo', [ScreenGroupController::class, 'create'])->name('screen-groups.create');
        Route::post('pantallas/grupos', [ScreenGroupController::class, 'store'])->name('screen-groups.store');
        Route::get('pantallas/grupos/{screenGroup}/editar', [ScreenGroupController::class, 'edit'])->name('screen-groups.edit');
        Route::put('pantallas/grupos/{screenGroup}', [ScreenGroupController::class, 'update'])->name('screen-groups.update');
        Route::put('pantallas/grupos/{screenGroup}/pantallas', [ScreenGroupController::class, 'syncMembers'])->name('screen-groups.members.sync');
        Route::delete('pantallas/grupos/{screenGroup}', [ScreenGroupController::class, 'destroy'])->name('screen-groups.destroy');

        Route::get('pantallas/vincular/nuevo', [ScreenPairingController::class, 'create'])->name('screens.pair.create');
        Route::post('pantallas/vincular', [ScreenPairingController::class, 'verify'])->name('screens.pair.verify');
        Route::get('pantallas/vincular/{pairing}/completar', [ScreenPairingController::class, 'completeForm'])->name('screens.pair.complete');
        Route::post('pantallas/vincular/{pairing}/completar', [ScreenPairingController::class, 'complete'])->name('screens.pair.store');

        Route::get('pantallas/{screen}/editar', [ScreenController::class, 'edit'])->name('screens.edit');
        Route::put('pantallas/{screen}', [ScreenController::class, 'update'])->name('screens.update');

        Route::post('pantallas/{screen}/deshabilitar', [ScreenController::class, 'disable'])->name('screens.disable');
        Route::post('pantallas/{screen}/habilitar', [ScreenController::class, 'enable'])->name('screens.enable');

        Route::get('playlists/{playlist}/asignacion', [PlaylistAssignmentController::class, 'edit'])->name('playlists.assign.edit');
        Route::put('playlists/{playlist}/asignacion', [PlaylistAssignmentController::class, 'update'])->name('playlists.assign.update');
    });

    Route::get('pantallas/grupos/{screenGroup}', [ScreenGroupController::class, 'show'])->name('screen-groups.show');

    Route::middleware('role:admin')->group(function () {
        Route::post('pantallas/{screen}/revocar', [ScreenController::class, 'revoke'])->name('screens.revoke');
        Route::delete('pantallas/{screen}', [ScreenController::class, 'destroy'])->name('screens.destroy');

        Route::get('usuarios', fn () => app(ModulePlaceholderController::class)->show('users'))
            ->name('users.index');

        Route::get('configuracion', fn () => app(ModulePlaceholderController::class)->show('settings'))
            ->name('settings.index');
    });

    Route::get('pantallas/{screen}', [ScreenController::class, 'show'])->name('screens.show');

    Route::get('contenido/videos', [MediaAssetController::class, 'videos'])->name('content.videos');
    Route::get('contenido/imagenes', [MediaAssetController::class, 'images'])->name('content.images');

    Route::get('contenido/archivos/{mediaAsset}', [MediaAssetController::class, 'show'])->name('media.show');
    Route::get('contenido/archivos/{mediaAsset}/vista', [MediaAssetController::class, 'preview'])->name('media.preview');

    Route::middleware('can:manage-content')->group(function () {
        Route::post('contenido/videos', [MediaAssetController::class, 'store'])
            ->defaults('type', 'videos')
            ->name('content.videos.store');
        Route::post('contenido/imagenes', [MediaAssetController::class, 'store'])
            ->defaults('type', 'imagenes')
            ->name('content.images.store');

        Route::get('contenido/archivos/{mediaAsset}/editar', [MediaAssetController::class, 'edit'])->name('media.edit');
        Route::put('contenido/archivos/{mediaAsset}', [MediaAssetController::class, 'update'])->name('media.update');
        Route::delete('contenido/archivos/{mediaAsset}', [MediaAssetController::class, 'destroy'])->name('media.destroy');
    });

    Route::get('playlists', [PlaylistController::class, 'index'])->name('playlists.index');

    Route::middleware('can:manage-content')->group(function () {
        Route::get('playlists/nueva', [PlaylistController::class, 'create'])->name('playlists.create');
        Route::post('playlists', [PlaylistController::class, 'store'])->name('playlists.store');
        Route::get('playlists/{playlist}/editar', [PlaylistController::class, 'edit'])->name('playlists.edit');
        Route::put('playlists/{playlist}', [PlaylistController::class, 'update'])->name('playlists.update');
        Route::delete('playlists/{playlist}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');

        Route::post('playlists/{playlist}/elementos', [PlaylistController::class, 'storeItem'])->name('playlists.items.store');
        Route::patch('playlists/{playlist}/elementos/{item}', [PlaylistController::class, 'updateItem'])->name('playlists.items.update');
        Route::delete('playlists/{playlist}/elementos/{item}', [PlaylistController::class, 'destroyItem'])->name('playlists.items.destroy');
        Route::post('playlists/{playlist}/reordenar', [PlaylistController::class, 'reorder'])->name('playlists.items.reorder');
    });

    Route::get('playlists/{playlist}', [PlaylistController::class, 'show'])->name('playlists.show');

    Route::get('programacion', [ScheduleController::class, 'index'])->name('schedules.index');

    Route::middleware('can:manage-content')->group(function () {
        Route::get('programacion/nueva', [ScheduleController::class, 'create'])->name('schedules.create');
        Route::post('programacion', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::get('programacion/{schedule}/editar', [ScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('programacion/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('programacion/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    Route::get('programacion/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show');

    Route::get('mensajes-urgentes', [UrgentMessageController::class, 'index'])->name('urgent-messages.index');

    Route::middleware('can:manage-content')->group(function () {
        Route::get('mensajes-urgentes/nuevo', [UrgentMessageController::class, 'create'])->name('urgent-messages.create');
        Route::post('mensajes-urgentes', [UrgentMessageController::class, 'store'])->name('urgent-messages.store');
        Route::get('mensajes-urgentes/{urgentMessage}/editar', [UrgentMessageController::class, 'edit'])->name('urgent-messages.edit');
        Route::put('mensajes-urgentes/{urgentMessage}', [UrgentMessageController::class, 'update'])->name('urgent-messages.update');
        Route::post('mensajes-urgentes/{urgentMessage}/activar', [UrgentMessageController::class, 'activate'])->name('urgent-messages.activate');
        Route::post('mensajes-urgentes/{urgentMessage}/cancelar', [UrgentMessageController::class, 'cancel'])->name('urgent-messages.cancel');
        Route::delete('mensajes-urgentes/{urgentMessage}', [UrgentMessageController::class, 'destroy'])->name('urgent-messages.destroy');
    });

    Route::get('mensajes-urgentes/{urgentMessage}', [UrgentMessageController::class, 'show'])->name('urgent-messages.show');
});
