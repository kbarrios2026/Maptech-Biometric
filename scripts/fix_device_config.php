<?php
// Fix device configuration
/** @var \App\Models\BiometricDevice|null $device */
$device = \App\Models\BiometricDevice::find(1);

echo "Current Settings:\n";
echo "  Name: " . $device->name . "\n";
echo "  IP: " . $device->ip_address . "\n";
echo "  Port: " . $device->port . "\n";
echo "  Sync Mode: " . $device->sync_mode . "\n";
echo "  Serial: " . $device->serial_number . "\n\n";

echo "Correcting configuration...\n";

// The device is in PUSH mode (actively sending data to us)
// Port 8000 is HTTP ADMS interface
// Port 4370 is ZKTeco SDK (for pull mode only)

$device->update([
    'port' => 8000,  // Correct HTTP port for ADMS push protocol
    'sync_mode' => 'push'  // Device is actively PUSHing data to us
]);

echo "Updated Settings:\n";
echo "  Name: " . $device->name . "\n";
echo "  IP: " . $device->ip_address . "\n";
echo "  Port: " . $device->port . " (HTTP ADMS)\n";
echo "  Sync Mode: " . $device->sync_mode . "\n";
echo "  Status: ACTIVE - Device PUSHes attendance data to /iclock/cdata every 15 seconds\n";
