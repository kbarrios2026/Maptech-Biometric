<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$device = \App\Models\BiometricDevice::first();

if ($device) {
    echo "=== Biometric Device Status ===\n";
    echo "Device Name: {$device->name}\n";
    echo "IP Address: {$device->ip_address}\n";
    echo "Port: " . ($device->port ?? 4370) . "\n";
    echo "Serial Number: " . ($device->serial_number ?? 'N/A') . "\n";
    echo "Active: " . ($device->is_active ? 'Yes' : 'No') . "\n";
    echo "Last Synced: " . ($device->last_synced_at ? $device->last_synced_at->diffForHumans() : 'Never') . "\n";
    echo "Sync Mode: " . ucfirst($device->sync_mode) . "\n";
    
    // Count recent attendance
    $recent = \App\Models\Attendance::where('source', $device->name)->count();
    echo "Total Attendance Records: $recent\n";
    
    $today = \App\Models\Attendance::where('source', $device->name)
        ->where('attendance_date', now()->toDateString())
        ->count();
    echo "Today's Records: $today\n";
} else {
    echo "No biometric device found!\n";
}
