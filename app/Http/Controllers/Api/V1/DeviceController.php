<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceHeartbeatRequest;
use App\Http\Requests\Api\DevicePlaybackStatusRequest;
use App\Models\MediaAsset;
use App\Services\DeviceManifestService;
use App\Services\DeviceTelemetryService;
use App\Support\DeviceRequest;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceController extends Controller
{
    use DeviceRequest;

    public function __construct(
        private readonly SettingStore $settings,
        private readonly DeviceManifestService $manifests,
        private readonly DeviceTelemetryService $telemetry,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $screen = $this->deviceScreen($request);

        return response()->json([
            'data' => [
                'screen' => [
                    'uuid' => $screen->uuid,
                    'name' => $screen->name,
                    'manifest_version' => $screen->manifest_version,
                ],
                'sync' => [
                    'heartbeat_interval_seconds' => $this->settings->getInt('device.heartbeat_interval_seconds', 60),
                    'manifest_poll_seconds' => $this->settings->getInt('device.manifest_poll_seconds', 120),
                ],
                'server_time' => now()->toIso8601String(),
                'timezone' => $this->settings->get('app.timezone', config('app.timezone')),
            ],
        ]);
    }

    public function playlist(Request $request): JsonResponse
    {
        $screen = $this->deviceScreen($request);

        return response()->json([
            'data' => $this->manifests->build($screen),
        ]);
    }

    public function heartbeat(DeviceHeartbeatRequest $request): JsonResponse
    {
        $screen = $this->deviceScreen($request);

        $this->telemetry->recordHeartbeat(
            $screen,
            $request->validated(),
            $request->ip()
        );

        return response()->json([
            'data' => [
                'accepted' => true,
                'manifest_version' => $screen->fresh()->manifest_version,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function playbackStatus(DevicePlaybackStatusRequest $request): JsonResponse
    {
        $screen = $this->deviceScreen($request);

        $this->telemetry->recordPlayback($screen, $request->validated());

        return response()->json([
            'data' => ['accepted' => true],
        ]);
    }

    public function downloadMedia(Request $request, MediaAsset $mediaAsset): StreamedResponse|JsonResponse
    {
        $screen = $this->deviceScreen($request);

        if (! $this->manifests->screenCanDownload($screen, $mediaAsset)) {
            return response()->json(['message' => 'Archivo no autorizado para esta pantalla.'], 403);
        }

        if (! Storage::disk($mediaAsset->disk)->exists($mediaAsset->path)) {
            return response()->json(['message' => 'Archivo no disponible.'], 404);
        }

        return Storage::disk($mediaAsset->disk)->response(
            $mediaAsset->path,
            $mediaAsset->original_filename,
            [
                'Content-Type' => $mediaAsset->mime_type,
                'Content-Length' => $mediaAsset->size_bytes,
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }
}
