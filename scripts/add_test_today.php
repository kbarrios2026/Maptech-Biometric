<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Carbon;

// Add today's attendance records for testing
$todayDate = today();

\App\Models\Attendance::create([
    'employee_id' => 4, // Stephen Von Ramos (ID 4, biometric 24)
    'attendance_date' => $todayDate,
    'check_in_time' => '09:15:30',
    'check_out_time' => null,
    'status' => 'Present',
    'source' => 'ZKTeco'
]);

\App\Models\Attendance::create([
    'employee_id' => 11, // Auto User 22 (ID 11, biometric 22)
    'attendance_date' => $todayDate,
    'check_in_time' => '08:45:00',
    'check_out_time' => null,
    'status' => 'Present',
    'source' => 'ZKTeco'
]);

echo "✓ Added 2 test records for today (" . $todayDate . ")" . PHP_EOL;

$today = Carbon::today();
$tomorrow = $today->copy()->addDay();
/** @var \App\Models\Attendance|null $latest */
$latest = \App\Models\Attendance::query()
    ->where('attendance_date', '>=', $today->format('Y-m-d'))
    ->where('attendance_date', '<', $tomorrow->format('Y-m-d'))
    ->latest('id')
    ->first();
echo "Latest today: " . $latest->employee?->first_name . " at " . $latest->check_in_time . PHP_EOL;
