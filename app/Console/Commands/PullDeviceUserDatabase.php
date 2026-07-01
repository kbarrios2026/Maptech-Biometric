<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Services\ZktecoWebService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullDeviceUserDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:pull-database {--device=1 : Device ID} {--force : Force pull even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull entire user database from ZKTeco device and auto-register new users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceId = $this->option('device');
        /** @var \App\Models\BiometricDevice|null $device */
        $device = BiometricDevice::find($deviceId);

        if (! $device) {
            $this->error("Device #{$deviceId} not found");

            return 1;
        }

        $this->info("🔄 Pulling user database from device: {$device->name}");
        $this->info("   IP: {$device->ip_address}:{$device->port}");

        // Check if recently synced (unless --force is used)
        if (! $this->option('force') && $device->last_synced_at && $device->last_synced_at->diffInMinutes(now()) < 5) {
            $this->warn("Device synced recently ({$device->last_synced_at->diffForHumans()}). Use --force to retry.");

            return 0;
        }

        try {
            // Test connectivity first
            $this->line("🔌 Testing device connectivity...");
            if (! ZktecoWebService::testConnectivity($device->ip_address, $device->port)) {
                $this->error("❌ Cannot reach device at {$device->ip_address}:{$device->port}");
                $this->line("   Device may be offline or unreachable.");
                Log::warning("Device unreachable during pull-database", [
                    'device_id' => $device->id,
                    'ip' => $device->ip_address,
                ]);

                return 1;
            }
            $this->line("✓ Device is online");

            // Pull users from device
            $this->line("📥 Requesting user database from device...");
            $remoteUsers = ZktecoWebService::pullUsersFromDevice(
                $device->ip_address,
                $device->port
            );

            if ($remoteUsers === null) {
                $this->warn("⚠️  Device didn't return user list via HTTP API");
                $this->line("   (Device may not support HTTP user API in push mode)");
                $this->line("   Continuing with auto-registration on next attendance...");

                $device->update(['last_synced_at' => now()]);

                return 0;
            }

            $this->info("✓ Retrieved ".count($remoteUsers)." users from device");

            // Get default employment values
            $defaultEmploymentType = EmploymentType::firstOrCreate(
                ['name' => 'Regular'],
                ['description' => 'Regular employee']
            );

            $defaultDepartment = Department::firstOrCreate(
                ['department_name' => 'General'],
                ['description' => 'General department']
            );

            $defaultPosition = Position::firstOrCreate(
                ['position_name' => 'Staff'],
                ['description' => 'Staff position']
            );

            // Process users
            $new = 0;
            $updated = 0;
            $skipped = 0;

            $this->line("\n📋 Processing users:");

            foreach ($remoteUsers as $remoteUser) {
                try {
                    // Extract user info
                    $biometricId = $remoteUser['pin'] ?? $remoteUser['id'] ?? null;
                    $firstName = $remoteUser['first_name'] ?? $remoteUser['name'] ?? 'Device';
                    $lastName = $remoteUser['last_name'] ?? 'User '.$biometricId;

                    if (! $biometricId) {
                        $this->line("  ⊘ Skipped: Invalid user (no ID)");
                        $skipped++;
                        continue;
                    }

                    // Check if employee exists
                    /** @var \App\Models\Employee|null $employee */
                    $employee = Employee::where('biometric_id', $biometricId)->first();

                    if ($employee) {
                        // Update if needed
                        $updated_fields = false;
                        if ($employee->first_name === 'Device User' || $employee->first_name === 'Auto User') {
                            $employee->update([
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                            ]);
                            $updated_fields = true;
                        }

                        if ($updated_fields) {
                            $this->line("  ↻ Updated: Biometric {$biometricId} → {$employee->full_name}");
                            $updated++;
                        } else {
                            $this->line("  ✓ Exists: Biometric {$biometricId} → {$employee->full_name}");
                            $skipped++;
                        }
                    } else {
                        // Create new employee
                        $newEmployee = Employee::create([
                            'biometric_id' => $biometricId,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => strtolower($firstName.'.'.$lastName.'@'.$device->name.'.local'),
                            'phone' => null,
                            'address' => null,
                            'department_id' => $defaultDepartment->id,
                            'position_id' => $defaultPosition->id,
                            'employment_type_id' => $defaultEmploymentType->id,
                            'hired_date' => now(),
                            'status' => 'active',
                        ]);

                        $this->line("  ✚ Created: Biometric {$biometricId} → {$newEmployee->full_name} (ID: {$newEmployee->id})");
                        $new++;

                        Log::info("Auto-registered device user: {$newEmployee->full_name} ({$biometricId})", [
                            'device_id' => $device->id,
                            'employee_id' => $newEmployee->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->line("  ✗ Error processing user: {$e->getMessage()}");
                    Log::error("Error processing device user", [
                        'device_id' => $device->id,
                        'user_data' => $remoteUser,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Summary
            $this->info("\n📊 Summary:");
            $this->info("   Created:  {$new}");
            $this->info("   Updated:  {$updated}");
            $this->info("   Existing: {$skipped}");

            // Update device sync time
            $device->update(['last_synced_at' => now()]);

            $this->info("\n✅ Device user database pull completed!");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            Log::error("Device database pull error: {$e->getMessage()}");

            return 1;
        }
    }
}
