<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatically pull attendance from all pull-mode ZKTeco devices every 5 minutes
Schedule::command('biometric:pull --all')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/biometric-pull.log'))
    ->description('Pull attendance logs from all active ZKTeco devices');
