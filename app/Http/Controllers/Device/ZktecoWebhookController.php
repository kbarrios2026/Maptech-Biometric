<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Services\ZktecoAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZktecoWebhookController extends Controller
{
    public function store(Request $request, string $deviceToken, ZktecoAttendanceService $service): JsonResponse
    {
        $device = BiometricDevice::where('device_token', $deviceToken)
            ->where('is_active', true)
            ->firstOrFail();

        $payload = $request->all();
        $logs = $payload['logs'] ?? $payload['data'] ?? $payload['attendance'] ?? $payload;

        if (! is_array($logs)) {
            $logs = [$logs];
        }

        $processed = $service->processLogs($device, $logs);

        return response()->json([
            'success' => true,
            'message' => 'Attendance logs received.',
            'processed' => $processed,
            'device' => $device->name,
        ]);
    }
}
