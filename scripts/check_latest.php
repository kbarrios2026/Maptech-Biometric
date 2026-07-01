<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Carbon;

$latest = \App\Models\Attendance::latest('id')->first();
echo "Latest record:" . PHP_EOL;
echo "  ID: " . $latest->id . PHP_EOL;
echo "  Employee: " . ($latest->employee?->first_name ?? 'null') . PHP_EOL;
echo "  Date: " . ($latest->attendance_date ?? 'null') . PHP_EOL;
echo "  Check-in: " . $latest->check_in_time . PHP_EOL;
echo "  Created: " . $latest->created_at . PHP_EOL;
echo "  Source: " . $latest->source . PHP_EOL;

$today = Carbon::today();
$tomorrow = $today->copy()->addDay();
/** @var \App\Models\Attendance|null $todayRecord */
$todayRecord = \App\Models\Attendance::query()
    ->where('attendance_date', '>=', $today->format('Y-m-d'))
    ->where('attendance_date', '<', $tomorrow->format('Y-m-d'))
    ->latest('id')
    ->first();
echo PHP_EOL . "Latest TODAY:" . PHP_EOL;
if ($todayRecord) {
    echo "  ID: " . $todayRecord->id . PHP_EOL;
    echo "  Employee: " . ($todayRecord->employee?->first_name ?? 'null') . PHP_EOL;
    echo "  Check-in: " . $todayRecord->check_in_time . PHP_EOL;
} else {
    echo "  (none)" . PHP_EOL;
}
