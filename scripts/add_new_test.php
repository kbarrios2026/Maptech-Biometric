<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Carbon;

// Add a NEW test record with current time
$now = Carbon::now();
\App\Models\Attendance::create([
    'employee_id' => 5, // Auto User 21
    'attendance_date' => $now->toDateString(),
    'check_in_time' => $now->format('H:i:s'),
    'check_out_time' => null,
    'status' => 'Present',
    'source' => 'ZKTeco'
]);

echo "✓ Added new record at " . $now->format('H:i:s') . PHP_EOL;
