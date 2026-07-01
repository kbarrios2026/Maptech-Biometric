<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncDeviceData extends Command
{
    protected $signature = 'device:sync {--device=1 : The biometric device ID} {--export : Export data to JSON files}';

    protected $description = 'Sync and view all users and attendance from ZKTeco device';

    public function handle()
    {
        $device = BiometricDevice::findOrFail($this->option('device'));

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  Device Sync Status: {$device->name}");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("Device IP: {$device->ip_address}:{$device->port}");
        $this->info("Serial: {$device->serial_number}");
        $this->info("Sync Mode: " . ucfirst($device->sync_mode));
        $this->info("Last Synced: " . ($device->last_synced_at ? $device->last_synced_at->diffForHumans() : 'Never'));
        $this->newLine();

        // Get device statistics
        $employees = Employee::count();
        $attendanceRecords = Attendance::where('source', $device->name)->count();
        $todayRecords = Attendance::where('source', $device->name)
            ->where('attendance_date', now()->toDateString())
            ->count();

        $this->info("📊 Database Statistics:");
        $this->info("  • Total Employees: $employees");
        $this->info("  • Total Attendance Records (this device): $attendanceRecords");
        $this->info("  • Today's Records: $todayRecords");
        $this->newLine();

        // Show employees from device
        $this->info("👥 Employees (from device scans):");
        $deviceEmployees = Employee::where('biometric_id', '!=', null)
            ->orderBy('biometric_id')
            ->limit(10)
            ->get(['id', 'biometric_id', 'first_name', 'last_name']);

        if ($deviceEmployees->count() > 0) {
            $this->table(
                ['ID', 'Biometric ID', 'Name'],
                $deviceEmployees->map(function ($emp) {
                    return [
                        $emp->id,
                        $emp->biometric_id,
                        "{$emp->first_name} {$emp->last_name}",
                    ];
                })->toArray()
            );
        } else {
            $this->warn("  No employees found");
        }

        if ($deviceEmployees->count() > 10) {
            $this->info("  ... and " . ($deviceEmployees->count() - 10) . " more employees");
        }
        $this->newLine();

        // Show recent attendance
        $this->info("📝 Recent Attendance Records:");
        $recentRecords = Attendance::where('source', $device->name)
            ->with('employee')
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->limit(5)
            ->get(['id', 'employee_id', 'attendance_date', 'check_in_time', 'check_out_time', 'status']);

        if ($recentRecords->count() > 0) {
            $this->table(
                ['Date', 'Employee', 'Time In', 'Time Out', 'Status'],
                $recentRecords->map(function ($att) {
                    return [
                        $att->attendance_date,
                        $att->employee->first_name . ' ' . $att->employee->last_name,
                        $att->check_in_time,
                        $att->check_out_time ?? '-',
                        $att->status,
                    ];
                })->toArray()
            );
        } else {
            $this->warn("  No attendance records found");
        }
        $this->newLine();

        // Export data if requested
        if ($this->option('export')) {
            $this->exportData($device);
        }

        $this->info("✅ Sync Status Check Complete!");
        $this->info("═══════════════════════════════════════════════════════");

        return Command::SUCCESS;
    }

    /**
     * Export all device data to JSON files.
     */
    protected function exportData(BiometricDevice $device): void
    {
        $this->info("📤 Exporting data to JSON files...");

        $exportDir = storage_path('exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        // Export employees
        $employees = Employee::where('biometric_id', '!=', null)
            ->orderBy('biometric_id')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'biometric_id' => $emp->biometric_id,
                    'first_name' => $emp->first_name,
                    'last_name' => $emp->last_name,
                    'email' => $emp->email,
                ];
            });

        file_put_contents(
            "$exportDir/employees_{$timestamp}.json",
            json_encode($employees, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->info("  ✓ Exported " . $employees->count() . " employees");

        // Export attendance
        $attendance = Attendance::where('source', $device->name)
            ->with('employee')
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function ($att) {
                $employeeName = $att->employee ? $att->employee->first_name . ' ' . $att->employee->last_name : 'Unknown';
                $biometricId = $att->employee ? $att->employee->biometric_id : 'N/A';
                
                return [
                    'id' => $att->id,
                    'employee_name' => $employeeName,
                    'biometric_id' => $biometricId,
                    'date' => $att->attendance_date,
                    'time_in' => $att->check_in_time,
                    'time_out' => $att->check_out_time,
                    'status' => $att->status,
                    'recorded_at' => $att->created_at->toIso8601String(),
                ];
            });

        file_put_contents(
            "$exportDir/attendance_{$timestamp}.json",
            json_encode($attendance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->info("  ✓ Exported " . $attendance->count() . " attendance records");
        $this->info("  📁 Files saved to: $exportDir/");
    }
}

