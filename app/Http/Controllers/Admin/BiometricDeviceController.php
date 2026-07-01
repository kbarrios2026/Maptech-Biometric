<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiometricDeviceController extends Controller
{
    public function index()
    {
        $devices = BiometricDevice::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.biometric_devices.index', compact('devices'));
    }

    public function create()
    {
        return view('admin.biometric_devices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:biometric_devices,serial_number',
            'ip_address' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'comm_key' => 'nullable|string|max:255',
            'sync_mode' => 'required|in:push,pull',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $device = BiometricDevice::create($data);

        return redirect()->route('admin.biometric-devices.show', $device)
            ->with('success', 'Biometric device created.');
    }

    public function show(BiometricDevice $biometricDevice)
    {
        $webhookUrl = $this->buildWebhookUrl($biometricDevice);
        $connectionGuide = $this->buildConnectionGuide($biometricDevice, $webhookUrl);

        return view('admin.biometric_devices.show', compact('biometricDevice', 'webhookUrl', 'connectionGuide'));
    }

    public function edit(BiometricDevice $biometricDevice)
    {
        return view('admin.biometric_devices.edit', compact('biometricDevice'));
    }

    public function update(Request $request, BiometricDevice $biometricDevice)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:biometric_devices,serial_number,' . $biometricDevice->id,
            'ip_address' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'comm_key' => 'nullable|string|max:255',
            'sync_mode' => 'required|in:push,pull',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $biometricDevice->update($data);

        return redirect()->route('admin.biometric-devices.show', $biometricDevice)
            ->with('success', 'Biometric device updated.');
    }

    public function destroy(BiometricDevice $biometricDevice)
    {
        BiometricDevice::query()
            ->whereKey($biometricDevice->id)
            ->delete();

        return redirect()->route('admin.biometric-devices.index')
            ->with('success', 'Biometric device deleted.');
    }

    /**
     * Test connection to a biometric device.
     */
    public function testConnection(BiometricDevice $biometricDevice, ZktecoService $zkteco): JsonResponse
    {
        if (empty($biometricDevice->ip_address)) {
            return response()->json([
                'success' => false,
                'message' => 'No IP address configured for this device.',
            ]);
        }

        $result = $zkteco->testConnection(
            $biometricDevice->ip_address,
            $biometricDevice->port ?? 4370,
            $biometricDevice->comm_key ?? '0'
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Device connected successfully.'
                : ($result['error'] ?? 'Connection failed.'),
            'data' => $result,
        ]);
    }

    /**
     * Sync attendance logs from a device (pull mode).
     */
    public function syncAttendance(BiometricDevice $biometricDevice, ZktecoService $zkteco): JsonResponse
    {
        if (empty($biometricDevice->ip_address)) {
            return response()->json([
                'success' => false,
                'message' => 'No IP address configured for this device.',
            ]);
        }

        if ($biometricDevice->sync_mode !== 'pull') {
            return response()->json([
                'success' => false,
                'message' => 'This device is configured for push mode. Use the push webhook URL instead.',
            ]);
        }

        $result = $zkteco->pullAndProcessAttendance($biometricDevice);

        return response()->json([
            'success' => empty($result['errors']),
            'message' => "Processed {$result['processed']} of {$result['total']} records.",
            'data' => $result,
        ]);
    }

    /**
     * Enable push mode on the device.
     */
    public function enablePush(BiometricDevice $biometricDevice, ZktecoService $zkteco): JsonResponse
    {
        if (empty($biometricDevice->ip_address)) {
            return response()->json([
                'success' => false,
                'message' => 'No IP address configured for this device.',
            ]);
        }

        $enabled = $zkteco->enablePushMode($biometricDevice);

        return response()->json([
            'success' => $enabled,
            'message' => $enabled
                ? 'Push mode enabled. Device will now send real-time events.'
                : 'Failed to enable push mode. Check device connection.',
        ]);
    }

    /**
     * Get webhook URL for push mode devices.
     */
    public function getWebhookUrl(BiometricDevice $biometricDevice): JsonResponse
    {
        $url = $this->buildWebhookUrl($biometricDevice);
        $connectionGuide = $this->buildConnectionGuide($biometricDevice, $url);

        return response()->json([
            'url' => $url,
            'device_token' => $biometricDevice->device_token,
            'method' => 'POST',
            'connection_guide' => $connectionGuide,
            'example_payload' => [
                'biometric_id' => '12345',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'status' => 0,
            ],
        ]);
    }

    /**
     * Poll for recent attendance records (for live dashboard feed).
     */
    public function recentAttendance(BiometricDevice $biometricDevice): JsonResponse
    {
        $attendances = Attendance::query()
            ->where('source', $biometricDevice->name)
            ->with('employee:id,first_name,last_name,biometric_id')
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee' => $attendance->employee?->full_name ?? 'Unknown',
                    'employee_id' => $attendance->employee?->biometric_id ?? '-',
                    'date' => $attendance->attendance_date?->format('Y-m-d'),
                    'check_in' => $attendance->check_in_time,
                    'check_out' => $attendance->check_out_time,
                    'status' => $attendance->status,
                    'source' => $attendance->source,
                    'created_at' => $attendance->created_at?->diffForHumans(),
                ];
            });

        return response()->json([
            'device' => $biometricDevice->name,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Build the absolute webhook URL that should be configured on the device.
     */
    private function buildWebhookUrl(BiometricDevice $biometricDevice): string
    {
        $baseUrl = rtrim((string) config('app.url', request()->getSchemeAndHttpHost()), '/');

        return $baseUrl . route('device.zkteco.attendance', $biometricDevice->device_token, false);
    }

    /**
     * Build a short connection guide for the device settings screen.
     */
    private function buildConnectionGuide(BiometricDevice $biometricDevice, string $webhookUrl): array
    {
        $parsedUrl = parse_url($webhookUrl);
        $shortToken = $this->buildShortToken($biometricDevice);
        $baseUrl = rtrim((string) config('app.url', request()->getSchemeAndHttpHost()), '/');
        $shortUrl = $baseUrl . route('device.zkteco.attendance', $shortToken, false);

        return [
            'server_ip' => $parsedUrl['host'] ?? parse_url((string) config('app.url', ''), PHP_URL_HOST),
            'server_port' => $parsedUrl['port'] ?? (parse_url((string) config('app.url', ''), PHP_URL_PORT) ?? 80),
            'device_ip' => $biometricDevice->ip_address,
            'device_port' => $biometricDevice->port ?? 4370,
            'short_token' => $shortToken,
            'recommended_url' => $shortUrl,
            'full_url' => $webhookUrl,
            'recommended_notes' => [
                'Use the server IP or hostname shown here, not 127.0.0.1 or localhost.',
                'Keep the device on the same LAN or open the webhook URL through a public domain/VPN if it is remote.',
                'For push mode, configure the device to POST attendance events to the short webhook URL.',
                'If short token ever conflicts in the future, use the full webhook URL.',
            ],
        ];
    }

    /**
     * Build the shortest unique token prefix for keypad-friendly URL entry.
     */
    private function buildShortToken(BiometricDevice $biometricDevice): string
    {
        $token = (string) $biometricDevice->device_token;
        $otherTokens = BiometricDevice::query()
            ->where('id', '!=', $biometricDevice->id)
            ->whereNotNull('device_token')
            ->pluck('device_token');

        $minLength = 6;
        $maxLength = min(16, strlen($token));

        for ($length = $minLength; $length <= $maxLength; $length++) {
            $prefix = substr($token, 0, $length);
            $conflict = $otherTokens->contains(function ($otherToken) use ($prefix) {
                return str_starts_with((string) $otherToken, $prefix);
            });

            if (! $conflict) {
                return $prefix;
            }
        }

        return $token;
    }
}
