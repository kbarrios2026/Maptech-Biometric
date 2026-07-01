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

// Automatically pull and register users from ZKTeco device every hour
Schedule::command('device:pull-users')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/device-users.log'))
    ->description('Auto-pull and register users from ZKTeco device');

// Automatically pull entire device user database every 6 hours
Schedule::command('device:pull-database')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/device-database.log'))
    ->description('Pull and sync entire ZKTeco device user database');

// Alternative: Run every 30 minutes for faster user registration
// Schedule::command('device:pull-users')
//     ->everyThirtyMinutes()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/device-users.log'));
