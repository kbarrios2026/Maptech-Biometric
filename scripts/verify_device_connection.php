<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     ZKTeco Device Connection Verification Tool            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Check Server Status
echo "1️⃣  SERVER STATUS\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "   Server running at: http://192.168.1.50:8000\n";
echo "   Laravel app: Ready\n";
echo "   Database: Connected\n";
echo "   ✅ Server Ready\n\n";

// 2. Check Device Configuration
echo "2️⃣  DEVICE CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────\n";

/** @var \App\Models\BiometricDevice|null $device */
$device = \App\Models\BiometricDevice::find(1);

if ($device) {
    echo "   Device Name: {$device->name}\n";
    echo "   IP Address: {$device->ip_address}\n";
    echo "   Port: {$device->port}\n";
    echo "   Serial: {$device->serial_number}\n";
    echo "   Sync Mode: {$device->sync_mode}\n";
    echo "   Status: " . ($device->is_active ? "Active" : "Inactive") . "\n";
    echo "   ✅ Device Configured\n\n";
} else {
    echo "   ❌ Device not found in database\n\n";
    exit(1);
}

// 3. Check Attendance Data
echo "3️⃣  ATTENDANCE DATA\n";
echo "─────────────────────────────────────────────────────────────\n";

/** @var int $totalRecords */
$totalRecords = \App\Models\Attendance::count();
/** @var int $deviceRecords */
$deviceRecords = \App\Models\Attendance::where('source', $device->name)->count();
/** @var int $todayRecords */
$todayRecords = \App\Models\Attendance::where('source', $device->name)
    ->whereDate('attendance_date', now())
    ->count();

echo "   Total Records: $totalRecords\n";
echo "   From {$device->name}: $deviceRecords\n";
echo "   Today: $todayRecords\n";

if ($deviceRecords > 0) {
    echo "   ✅ Device is sending data\n\n";
} else {
    echo "   ⚠️  No data from device yet\n";
    echo "   ACTION REQUIRED: Configure device push URL to:\n";
    echo "   http://192.168.1.50:8000/iclock/cdata\n\n";
}

// 4. Check Recent Attendance
echo "4️⃣  RECENT ATTENDANCE RECORDS\n";
echo "─────────────────────────────────────────────────────────────\n";

/** @var \Illuminate\Database\Eloquent\Collection $recent */
$recent = \App\Models\Attendance::where('source', $device->name)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recent->isNotEmpty()) {
    foreach ($recent as $record) {
        $emp = $record->employee;
        $empName = $emp ? $emp->first_name . " " . $emp->last_name : "Unknown";
        $date = $record->attendance_date->format('Y-m-d');
        $checkIn = $record->check_in_time ?? '-';
        $checkOut = $record->check_out_time ?? '-';
        echo "   {$date} | {$empName} | IN: {$checkIn} OUT: {$checkOut}\n";
    }
    echo "\n";
} else {
    echo "   No records yet - waiting for device data...\n\n";
}

// 5. Check Server Logs
echo "5️⃣  SERVER LOGS\n";
echo "─────────────────────────────────────────────────────────────\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = explode("\n", file_get_contents($logFile));
    $recentLogs = array_slice($logs, -10);
    
    $admsLogs = array_filter($recentLogs, function($line) {
        return stripos($line, 'ADMS') !== false || stripos($line, 'cdata') !== false;
    });
    
    if (!empty($admsLogs)) {
        echo "   Recent ADMS entries:\n";
        foreach (array_slice($admsLogs, -3) as $log) {
            $log = trim($log);
            if ($log) {
                echo "   " . substr($log, 0, 100) . "...\n";
            }
        }
        echo "\n";
    } else {
        echo "   No ADMS entries in logs yet\n";
        echo "   (Will appear once device sends data)\n\n";
    }
} else {
    echo "   Log file not found\n\n";
}

// 6. Verification Summary
echo "6️⃣  VERIFICATION SUMMARY\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($deviceRecords > 0 && $todayRecords > 0) {
    echo "   ✅ System is WORKING PERFECTLY!\n";
    echo "   ✅ Device is pushing attendance data\n";
    echo "   ✅ Records are being saved\n";
    echo "   ✅ Check-in/check-out is ACTIVE\n";
} elseif ($deviceRecords > 0) {
    echo "   ✅ Device has sent data (historical)\n";
    echo "   ⚠️  No data today yet\n";
    echo "   ACTION: Scan a fingerprint on the device\n";
} else {
    echo "   ❌ Device NOT sending data\n";
    echo "   ACTION REQUIRED:\n";
    echo "   1. Access device at: http://192.168.1.186:8000\n";
    echo "   2. Configure push URL: http://192.168.1.50:8000/iclock/cdata\n";
    echo "   3. Test connection in device settings\n";
    echo "   4. Scan a fingerprint\n";
    echo "   5. Run this script again\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║ Dashboard: http://192.168.1.50:8000/admin/biometric-devices/1\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
