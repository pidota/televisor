<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DevicePairingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:api-device-pair')->group(function () {
        Route::post('device/pair', [DevicePairingController::class, 'pair']);
        Route::post('device/activate', [DevicePairingController::class, 'activate']);
    });

    Route::middleware(['device.auth', 'throttle:api-device'])->group(function () {
        Route::get('device/config', [DeviceController::class, 'config'])->name('device.config');
        Route::get('device/playlist', [DeviceController::class, 'playlist'])->name('device.playlist');
        Route::post('device/heartbeat', [DeviceController::class, 'heartbeat'])->name('device.heartbeat');
        Route::post('device/playback-status', [DeviceController::class, 'playbackStatus'])->name('device.playback-status');
        Route::get('device/media/{mediaAsset:uuid}', [DeviceController::class, 'downloadMedia'])->name('device.media.download');
    });
});
