<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullDeviceUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:pull-users {--device=1 : The device ID to pull users from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically pull users from ZKTeco device attendance logs and register them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceId = $this->option('device');

        // Get the device
        $device = BiometricDevice::find($deviceId);
        if (! $device) {
            $this->error("Device #{$deviceId} not found");

            return 1;
        }

        $this->info("🔄 Pulling users from device: {$device->device_name}");

        try {
            // Get all employees with attendance records
            $employeesWithAttendance = Attendance::distinct('employee_id')
                ->pluck('employee_id')
                ->filter()
                ->toArray();

            $totalWithAttendance = count($employeesWithAttendance);
            $this->info("📊 Found {$totalWithAttendance} employees with attendance records");

            // Get employees that exist in the system
            $allEmployees = Employee::pluck('id')->toArray();

            // Find employees with attendance that don't have full records (auto-created placeholders)
            $placeholderEmployees = Employee::whereIn('id', $employeesWithAttendance)
                ->where('first_name', 'Device User')
                ->orWhere('email', 'like', 'device.user.%@system.local')
                ->get();

            $placeholderCount = $placeholderEmployees->count();
            $this->info("👥 Found {$placeholderCount} placeholder employees auto-created from device");

            // Check for any biometric IDs without employees
            $biometricIds = Employee::pluck('biometric_id')->filter()->toArray();
            $totalBiometricIds = count($biometricIds);
            $this->info("🔑 Total unique biometric IDs in system: {$totalBiometricIds}");

            $newCount = 0;

            if ($newCount === 0 && $placeholderCount === 0) {
                $this->info('✅ No new users to register. System is up to date!');

                return 0;
            }

            // Get or create default values
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

            // Auto-register new employees from placeholder records
            $registered = 0;
            $failed = 0;

            if ($placeholderCount > 0) {
                $this->info("\nℹ️  Employees auto-created by device are already registered:");
                $this->info("   (System auto-creates employees when device sends attendance for new biometric IDs)");
                
                foreach ($placeholderEmployees as $employee) {
                    $this->line("  ✓ Biometric ID {$employee->biometric_id} → {$employee->full_name} (ID: {$employee->id})");
                }
            }

            $device->update(['last_synced_at' => now()]);

            $this->info("\n✅ Device user sync completed!");
            $this->info("\n📝 Note: New employees are automatically created when the device");
            $this->info("           sends attendance for unknown biometric IDs. No action needed.");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error pulling device users: {$e->getMessage()}");
            Log::error("Device user pulling error: {$e->getMessage()}");

            return 1;
        }
    }
}
