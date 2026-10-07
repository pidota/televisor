<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceActivateRequest;
use App\Http\Requests\Api\DevicePairRequest;
use App\Services\PairingService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DevicePairingController extends Controller
{
    public function __construct(
        private readonly PairingService $pairing,
    ) {}

    public function pair(DevicePairRequest $request): JsonResponse
    {
        try {
            $payload = $this->pairing->requestDevicePairing(
                $request->string('screen_uuid')->toString()
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function activate(DeviceActivateRequest $request): JsonResponse
    {
        $result = $this->pairing->pollDeviceActivation(
            $request->string('screen_uuid')->toString()
        );

        $statusCode = match ($result['status']) {
            'invalid' => 422,
            'inactive' => 403,
            default => 200,
        };

        return response()->json([
            'data' => $result,
        ], $statusCode);
    }
}
