<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RequestBiometricAttendanceReplay extends Command
{
    protected $signature = 'biometric:replay-attendance
        {device : The ID of the active ADMS push-mode device}
        {from : First date to replay (YYYY-MM-DD)}
        {to : Last date to replay (YYYY-MM-DD)}';

    protected $description = 'Ask an ADMS device to resend attendance logs for a date range';

    public function handle(): int
    {
        $device = BiometricDevice::query()
            ->where('is_active', true)
            ->where('sync_mode', 'push')
            ->find($this->argument('device'));

        if (! $device) {
            $this->error('An active push-mode device with that ID was not found.');

            return self::FAILURE;
        }

        if (empty($device->serial_number)) {
            $this->error('The device must have a registered serial number before requesting a replay.');

            return self::FAILURE;
        }

        try {
            $from = Carbon::createFromFormat('!Y-m-d', $this->argument('from'));
            $to = Carbon::createFromFormat('!Y-m-d', $this->argument('to'));
        } catch (\Throwable) {
            $this->error('Both replay dates must use YYYY-MM-DD format.');

            return self::FAILURE;
        }

        if ($from->format('Y-m-d') !== $this->argument('from') || $to->format('Y-m-d') !== $this->argument('to')) {
            $this->error('Both replay dates must be valid calendar dates in YYYY-MM-DD format.');

            return self::FAILURE;
        }

        if ($from->gt($to)) {
            $this->error('The replay start date must not be after the end date.');

            return self::FAILURE;
        }

        if ($to->isAfter(Carbon::today())) {
            $this->error('The replay end date must not be in the future.');

            return self::FAILURE;
        }

        if ($from->diffInDays($to) >= 31) {
            $this->error('A replay request may cover no more than 31 calendar days.');

            return self::FAILURE;
        }

        $cacheKey = "zkteco.attlog-replay.{$device->serial_number}";
        $queued = Cache::add($cacheKey, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ], now()->addDay());

        if (! $queued) {
            $this->error('A replay request is already queued for this device. Let the device poll, then try again.');

            return self::FAILURE;
        }

        $this->info("Queued attendance replay for {$device->name} ({$device->serial_number}) from {$from->toDateString()} through {$to->toDateString()}.");
        $this->line('The device must be online and polling this server to receive the request.');

        return self::SUCCESS;
    }
}
