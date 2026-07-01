<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Carbon;

// Add a check-out record for one of the today's records
$now = Carbon::now();

// Find the Auto User 21 record from today
/** @var \Illuminate\Database\Eloquent\Builder $attendance */
$attendance = \App\Models\Attendance::where('employee_id', 5)
    ->where('attendance_date', today())
    ->orderBy('id', 'desc')
    ->first();

if ($attendance) {
    // Update with check-out time
    $attendance->update([
        'check_out_time' => $now->format('H:i:s')
    ]);
    echo "✓ Added check-out at " . $now->format('H:i:s') . " for " . $attendance->employee->first_name . PHP_EOL;
} else {
    echo "✗ No record found for today" . PHP_EOL;
}
