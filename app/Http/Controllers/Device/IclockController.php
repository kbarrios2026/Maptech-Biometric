<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles ZKTeco ADMS (Attendance Data Management System) push protocol.
 *
 * Device flow:
 *   1. GET /iclock/cdata?SN=xxx&options=all  → server sends config
 *   2. POST /iclock/cdata                    → device pushes attendance logs
 *   3. GET /iclock/getrequest?SN=xxx         → device polls for pending commands
 *   4. POST /iclock/devicecmd               → device ACKs command result
 */
class IclockController extends Controller
{
    /**
     * GET /iclock/cdata — device registration and config handshake.
     */
    public function cdata(Request $request): Response
    {
        $sn = $request->query('SN', '');

        Log::info("ADMS: Device registration from SN={$sn}", $request->query());

        // Find or auto-register device by serial number
        $device = BiometricDevice::query()->where('serial_number', $sn)->first();
        if (! $device) {
            $device = BiometricDevice::query()->where('is_active', true)->first();
        }

        if ($device && $device->serial_number !== $sn && $sn) {
            $device->forceFill(['serial_number' => $sn])->save();
        }

        $body = implode("\r\n", [
            'GET OPTION FROM: '.$sn,
            'ATTLOGStamp=9999',
            'OPERLOGStamp=9999',
            'ATTPHOTOStamp=0',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog EnrollUser',
            'TimeZone=+8',
            'Realtime=1',
            'EncryptedFlag=0',
        ])."\r\n";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST /iclock/cdata — device pushes attendance or enrollment records.
     */
    public function postCdata(Request $request): Response
    {
        $sn = $request->query('SN', $request->query('sn', ''));
        $table = $request->query('table', $request->query('action', ''));

        Log::info("ADMS: Data push SN={$sn} table={$table}", [
            'body_excerpt' => substr($request->getContent(), 0, 500),
        ]);

        $device = BiometricDevice::query()->where('serial_number', $sn)->first()
            ?? BiometricDevice::query()->where('is_active', true)->first();

        if (! $device) {
            Log::warning("ADMS: No active device found for SN={$sn}");
            return response("OK\n", 200)->header('Content-Type', 'text/plain');
        }

        $body = $request->getContent();

        // Detect attendance log sections
        if (stripos($table, 'ATTLOG') !== false || stripos($body, 'ATTLOG') !== false) {
            $this->processAttendanceLogs($device, $body);
        }

        // Device pushes enrollment user data
        if (stripos($table, 'ENROLL_USER') !== false || stripos($body, 'ENROLL_USER') !== false) {
            $this->processEnrollment($device, $body);
        }

        $device->forceFill(['last_synced_at' => now()])->save();

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * GET /iclock/getrequest — device polls for pending server commands.
     */
    public function getRequest(Request $request): Response
    {
        // No commands queued — tell device nothing to do
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST /iclock/devicecmd — device reports command execution result.
     */
    public function deviceCmd(Request $request): Response
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Parse and save attendance log records from ADMS body.
     *
     * ATTLOG line format (tab-separated):
     *   PIN  Time  Status  Verify  WorkCode  Reserved
     * Example:
     *   1234\t2026-06-23 09:15:00\t0\t1\t0\t0
     */
    private function processAttendanceLogs(BiometricDevice $device, string $body): void
    {
        $lines = preg_split('/\r\n|\n|\r/', $body);
        $processed = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip header/meta lines
            if ($line === '' || stripos($line, 'ATTLOG') !== false || str_starts_with($line, 'SN=')) {
                continue;
            }

            // Tab-separated: PIN  Time  Status  Verify  WorkCode  Reserved
            $parts = preg_split('/\t+/', $line);
            if (count($parts) < 2) {
                continue;
            }

            $pin       = trim($parts[0]);
            $timeStr   = trim($parts[1]);
            $status    = (int) ($parts[2] ?? 0); // 0=check-in, 1=check-out

            if ($pin === '' || $timeStr === '') {
                continue;
            }

            try {
                $ts = Carbon::parse($timeStr);
            } catch (\Throwable) {
                continue;
            }

            $employee = $this->resolveEmployee($pin, $device);
            if (! $employee) {
                Log::info("ADMS: No employee for PIN={$pin}, skipping.");
                continue;
            }

            $date    = $ts->toDateString();
            $time    = $ts->format('H:i:s');
            $isLate  = $time > '08:00:00';

            $attendance = Attendance::firstOrNew([
                'employee_id'     => $employee->id,
                'attendance_date' => $date,
            ]);

            if (! $attendance->exists) {
                // Option 2: Check if first scan is checkout (status=1), mark it correctly
                if ($status === 1) {
                    // First scan is checkout - mark as checkout instead of check-in
                    $attendance->check_in_time  = null;
                    $attendance->check_out_time = $time;
                    $attendance->status         = 'Present';
                    // Option 3: Log warning for orphaned checkout
                    Log::warning("ADMS: Orphaned checkout for employee {$employee->id} on {$date} at {$time}. No check-in recorded.");
                } else {
                    $attendance->check_in_time  = $time;
                    $attendance->check_out_time = null;
                    $attendance->status         = $isLate ? 'Late' : 'Present';
                }
            } else {
                if ($status === 1 || (! empty($attendance->check_in_time) && $time > $attendance->check_in_time)) {
                    $attendance->check_out_time = $time;
                } else {
                    $attendance->check_in_time = $time;
                    if ($attendance->status === 'Absent') {
                        $attendance->status = $isLate ? 'Late' : 'Present';
                    }
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

        Log::info("ADMS: Processed {$processed} attendance record(s) from {$device->name}.");
    }

    /**
     * Process ENROLL_USER data (save biometric ID mapping).
     */
    private function processEnrollment(BiometricDevice $device, string $body): void
    {
        $lines = preg_split('/\r\n|\n|\r/', $body);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'ENROLL_USER') !== false) {
                continue;
            }

            $parts = preg_split('/\t+/', $line);
            // Format: PIN  Name  Privilege  Password  Card  Group  TimeZone  VerifyStyle
            if (count($parts) < 2) {
                continue;
            }

            $pin  = trim($parts[0]);
            $name = trim($parts[1] ?? '');

            if ($pin === '') {
                continue;
            }

            // Try to match by biometric_id and update employee name if blank
            $employee = $this->resolveEmployee($pin, $device);
            if ($employee && empty($employee->biometric_id)) {
                $employee->forceFill(['biometric_id' => $pin])->save();
            }

            // Save device enrollment mapping
            EmployeeDevice::firstOrCreate(
                ['device_identifier' => $pin],
                ['employee_id' => $employee?->id, 'device_name' => $device->name, 'is_primary' => true]
            );
        }
    }

    /**
     * Resolve an employee by PIN / biometric ID.
     */
    private function resolveEmployee(string $pin, BiometricDevice $device): ?Employee
    {
        return Employee::query()->where('biometric_id', $pin)->first()
            ?? Employee::query()->where('employee_id', $pin)->first()
            ?? optional(
                EmployeeDevice::query()->where('device_identifier', $pin)->with('employee')->first()
            )->employee
            ?? $this->autoCreateEmployeeFromPin($pin, $device);
    }

    private function autoCreateEmployeeFromPin(string $pin, BiometricDevice $device): ?Employee
    {
        if ($pin === '') {
            return null;
        }

        try {
            return DB::transaction(function () use ($pin, $device): Employee {
                $existing = Employee::query()->where('biometric_id', $pin)->first();
                if ($existing) {
                    return $existing;
                }

                $hash = substr(sha1($pin), 0, 12);
                $userEmail = "auto.user.{$hash}@maptech.local";
                $employeeEmail = "auto.employee.{$hash}@maptech.local";

                $user = User::query()->firstOrCreate(
                    ['email' => $userEmail],
                    [
                        'name' => "Auto User {$pin}",
                        'password' => Hash::make(Str::random(32)),
                    ]
                );

                $employee = Employee::query()->create([
                    'user_id' => $user->id,
                    'employee_id' => $this->generateUniqueEmployeeCode($pin),
                    'first_name' => 'Auto',
                    'last_name' => "User {$pin}",
                    'email' => $employeeEmail,
                    'joining_date' => now()->toDateString(),
                    'employment_status' => 'Active',
                    'biometric_id' => $pin,
                    'is_active' => true,
                ]);

                EmployeeDevice::query()->firstOrCreate(
                    ['device_identifier' => $pin],
                    [
                        'employee_id' => $employee->id,
                        'device_name' => $device->name,
                        'is_primary' => true,
                    ]
                );

                Log::info('ADMS: Auto-created employee from unknown PIN.', [
                    'pin' => $pin,
                    'employee_id' => $employee->id,
                    'device_id' => $device->id,
                ]);

                return $employee;
            });
        } catch (\Throwable $e) {
            Log::error('ADMS: Failed auto-creating employee from unknown PIN.', [
                'pin' => $pin,
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function generateUniqueEmployeeCode(string $identifier): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $identifier) ?: 'AUTO';
        $base = 'AUTO-'.$clean;
        $candidate = Str::upper(substr($base, 0, 24));
        $suffix = 1;

        while (Employee::query()->where('employee_id', $candidate)->exists()) {
            $tail = '-'.$suffix;
            $candidate = Str::upper(substr($base, 0, 24 - strlen($tail))).$tail;
            $suffix++;
        }

        return $candidate;
    }
}
