<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ZktecoAttendanceService
{
    public function processLogs(BiometricDevice $device, array $logs): int
    {
        $records = $this->normalizeLogs($logs);
        $processed = 0;

        foreach ($records as $log) {
            $employee = $this->resolveEmployee($log);
            if (! $employee) {
                continue;
            }

            $timestamp = $this->resolveTimestamp($log);
            if (! $timestamp) {
                continue;
            }

            $date = $timestamp->toDateString();
            $time = $timestamp->format('H:i:s');
            $status = $this->resolveStatus($log, $time);

            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'attendance_date' => $date,
            ]);

            if (! $attendance->exists) {
                $attendance->check_in_time = $time;
                $attendance->check_out_time = null;
                $attendance->status = $status;
            } else {
                if (empty($attendance->check_in_time) || $time < $attendance->check_in_time) {
                    $attendance->check_in_time = $time;
                }

                if (empty($attendance->check_out_time) || $time > $attendance->check_out_time) {
                    $attendance->check_out_time = $time;
                }

                if ($attendance->status === 'Absent') {
                    $attendance->status = $status;
                }
            }

            $attendance->source = $device->name;
            if (! in_array($attendance->status, ['Absent', 'Leave'], true) && Attendance::isOvertimeCheckout($attendance->check_out_time)) {
                $attendance->status = 'Overtime';
                if (empty($attendance->overtime_approval_status)) {
                    $attendance->overtime_approval_status = 'Pending';
                }
                if (empty($attendance->overtime_reason)) {
                    $attendance->overtime_reason = 'Auto-detected from checkout time.';
                }
            }
            $attendance->save();
            $processed++;
        }

        $device->forceFill(['last_synced_at' => now()])->save();

        return $processed;
    }

    protected function normalizeLogs(array $logs): array
    {
        if (Arr::isAssoc($logs)) {
            return [$logs];
        }

        return $logs;
    }

    protected function resolveEmployee(array $log): ?Employee
    {
        $identifier = $log['biometric_id']
            ?? $log['employee_id']
            ?? $log['enroll_id']
            ?? $log['pin']
            ?? $log['user_id']
            ?? null;

        if (! $identifier) {
            return null;
        }

        return Employee::query()->where('biometric_id', (string) $identifier)->first()
            ?? Employee::query()->where('employee_id', (string) $identifier)->first()
            ?? optional(EmployeeDevice::query()->where('device_identifier', (string) $identifier)->with('employee')->first())->employee;
    }

    protected function resolveTimestamp(array $log): ?Carbon
    {
        $value = $log['timestamp']
            ?? $log['time']
            ?? $log['datetime']
            ?? $log['punch_time']
            ?? null;

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveStatus(array $log, string $time): string
    {
        $status = strtolower((string) ($log['status'] ?? $log['event'] ?? $log['type'] ?? ''));

        if (str_contains($status, 'leave')) {
            return 'Leave';
        }

        if (str_contains($status, 'absent')) {
            return 'Absent';
        }

        if (str_contains($status, 'half')) {
            return 'Half Day';
        }

        // On-time attendance window: 12:01 AM to 7:59:59 AM.
        return ($time >= '00:01:00' && $time <= '07:59:59') ? 'Present' : 'Late';
    }
}
