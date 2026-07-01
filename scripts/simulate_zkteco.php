<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = 'ykPlt2xdt49Ae8r7M0KdnSzXhQ1nKuPsu4otJPhzCrOdvwmr';
$device = \App\Models\BiometricDevice::where('device_token', $token)->first();
if (! $device) {
    echo "No device found for token: $token\n";
    exit(1);
}

$service = app(\App\Services\ZktecoAttendanceService::class);
$logs = [
    ['biometric_id' => '12345', 'timestamp' => '2026-06-30 12:00:00', 'status' => 0],
];
$processed = $service->processLogs($device, $logs);
echo "Processed: $processed\n";
