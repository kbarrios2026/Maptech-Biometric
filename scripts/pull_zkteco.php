<?php

/**
 * Manual ZKTeco device pull script.
 *
 * Opens a direct connection to the biometric device, tests the link,
 * pulls the attendance database, and stores the records.
 *
 * Usage:
 *   php scripts/pull_zkteco.php                 # uses first active device
 *   php scripts/pull_zkteco.php 192.168.1.186   # override IP
 *   php scripts/pull_zkteco.php 192.168.1.186 4370
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BiometricDevice;
use App\Services\ZktecoService;

function line(string $msg = ''): void
{
    echo $msg . PHP_EOL;
}

// -----------------------------------------------------------------
// Resolve the target device
// -----------------------------------------------------------------
$device = BiometricDevice::query()
    ->when(true, fn ($q) => $q->orderByDesc('is_active'))
    ->first();

if (! $device) {
    line('No biometric device found in the database.');
    exit(1);
}

$ip   = $argv[1] ?? $device->ip_address;
// ZKTeco pull protocol runs on UDP 4370 regardless of the web port (8000).
$port = isset($argv[2]) ? (int) $argv[2] : (int) ($device->port ?: ZktecoService::DEFAULT_PORT);
$key  = $device->comm_key ?? '0';

line('==================================================');
line(' ZKTeco Manual Pull');
line('==================================================');
line("Device       : {$device->name} (#{$device->id})");
line("Target IP     : {$ip}");
line("Target Port   : {$port}");
line("Comm Key      : {$key}");
line("Sync Mode     : {$device->sync_mode}");
line('--------------------------------------------------');

$service = app(ZktecoService::class);

// -----------------------------------------------------------------
// Step 1: Test connection / handshake
// -----------------------------------------------------------------
line('[1/3] Opening connection and reading device info...');
$info = $service->testConnection($ip, $port, $key);

if (! $info['success']) {
    line('  ✗ Connection FAILED: ' . ($info['error'] ?? 'unknown error'));
    line('');
    line('  The ZKTeco pull protocol uses UDP port 4370. If this fails:');
    line('   - Ensure the device "ADMS/Push" is disabled OR pull is allowed.');
    line('   - Confirm UDP 4370 is open (some firmware only opens it on demand).');
    line('   - Verify the Comm Key matches the device Comm Password.');
    line('');
    line('  Since this device is in PUSH mode, it may not answer pull requests.');
    line('  In that case the device must POST to the webhook instead.');
    exit(2);
}

line('  ✓ Connected.');
line('     Serial          : ' . ($info['serial'] ?? 'n/a'));
line('     Firmware        : ' . ($info['version'] ?? 'n/a'));
line('     Device time     : ' . ($info['device_time'] ?? 'n/a'));
line('     Records on device: ' . ($info['attendance_count'] ?? 'n/a'));
line('--------------------------------------------------');

// -----------------------------------------------------------------
// Step 2: Pull the attendance database and process it
// -----------------------------------------------------------------
line('[2/3] Pulling attendance database from device...');
$result = $service->pullAndProcessAttendance($device);

line("  Total records read : {$result['total']}");
line("  Records processed  : {$result['processed']}");

if (! empty($result['errors'])) {
    line('  Errors:');
    foreach ($result['errors'] as $err) {
        line('    - ' . $err);
    }
}
line('--------------------------------------------------');

// -----------------------------------------------------------------
// Step 3: Update sync timestamp
// -----------------------------------------------------------------
line('[3/3] Updating last_synced_at...');
$device->forceFill(['last_synced_at' => now()])->save();
line('  ✓ Done. Last synced at ' . $device->last_synced_at);
line('==================================================');
