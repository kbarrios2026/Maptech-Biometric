<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

$emps = \App\Models\Employee::limit(10)->get();
echo "Employees in database:\n";
foreach ($emps as $e) {
    echo "  ID: $e->id | $e->first_name $e->last_name | Biometric: $e->biometric_id\n";
}
