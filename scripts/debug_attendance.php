<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

$device = \App\Models\BiometricDevice::find(1);
echo 'Device 1 name: ' . $device?->name . PHP_EOL;

$sources = \App\Models\Attendance::distinct()->pluck('source');
echo 'Attendance sources: ' . $sources->join(', ') . PHP_EOL;

$bySource = \App\Models\Attendance::groupBy('source')->selectRaw('source, count(*) as count')->get();
echo PHP_EOL . 'Records by source:' . PHP_EOL;
foreach ($bySource as $row) {
    echo '  ' . $row->source . ': ' . $row->count . PHP_EOL;
}

// Test the actual query
echo PHP_EOL . 'Testing controller query:' . PHP_EOL;
$attendances = \App\Models\Attendance::query()
    ->where('source', $device->name)
    ->with('employee:id,first_name,last_name,biometric_id')
    ->latest()
    ->take(20)
    ->get();
    
echo 'Records for device ' . $device->name . ': ' . $attendances->count() . PHP_EOL;
if ($attendances->count() > 0) {
    echo 'First record: ' . $attendances[0]->employee?->full_name . ' - ' . $attendances[0]->check_in_time . PHP_EOL;
}
