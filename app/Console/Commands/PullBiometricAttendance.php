<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Services\ZktecoService;
use Illuminate\Console\Command;

class PullBiometricAttendance extends Command
{
    protected $signature = 'biometric:pull
        {--device= : The ID of the specific device to pull from}
        {--all : Pull from all active pull-mode devices}
        {--clear : Clear attendance logs from device after pulling}';

    protected $description = 'Pull attendance logs from ZKTeco biometric devices';

    /**
     * Execute the console command.
     */
    public function handle(ZktecoService $zkteco): int
    {
        $deviceId = $this->option('device');
        $pullAll = $this->option('all');
        $clear = $this->option('clear');

        $devices = collect();

        if ($deviceId) {
            $device = BiometricDevice::where('is_active', true)->find($deviceId);
            if (! $device) {
                $this->error("Device with ID {$deviceId} not found or inactive.");

                return Command::FAILURE;
            }
            $devices->push($device);
        } elseif ($pullAll) {
            $devices = BiometricDevice::where('is_active', true)
                ->where('sync_mode', 'pull')
                ->get();

            if ($devices->isEmpty()) {
                $this->warn('No active pull-mode devices found.');

                return Command::SUCCESS;
            }
        } else {
            // Default: pull from all active pull-mode devices
            $devices = BiometricDevice::where('is_active', true)
                ->where('sync_mode', 'pull')
                ->get();

            if ($devices->isEmpty()) {
                $this->warn('No active pull-mode devices found. Use --all or --device to specify.');

                return Command::SUCCESS;
            }
        }

        $totalProcessed = 0;
        $totalErrors = 0;

        foreach ($devices as $device) {
            $this->info("Processing device: {$device->name} ({$device->ip_address}:{$device->port})");

            if (empty($device->ip_address)) {
                $this->warn('  [SKIP] No IP address configured.');

                continue;
            }

            $result = $zkteco->pullAndProcessAttendance($device);

            if (! empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->error("  [ERROR] {$error}");
                    $totalErrors++;
                }
            }

            $this->line("  Total logs: {$result['total']}, Processed: {$result['processed']}");

            if ($clear && $result['total'] > 0 && empty($result['errors'])) {
                if ($zkteco->connect($device->ip_address, $device->port ?? 4370, $device->comm_key ?? '0')) {
                    $zkteco->clearAttendanceLogs();
                    $zkteco->disconnect();
                    $this->line('  [OK] Attendance logs cleared from device.');
                }
            }

            $totalProcessed += $result['processed'];
        }

        $this->newLine();
        $this->info("Done. Total records processed: {$totalProcessed}");

        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }

        return Command::SUCCESS;
    }
}
