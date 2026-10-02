<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\UniqueConstraintViolationException;

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

        $device = $sn !== ''
            ? BiometricDevice::query()
                ->where('serial_number', $sn)
                ->where('is_active', true)
                ->first()
            : null;

        if (! $device && $sn !== '') {
            Log::warning("ADMS: Registration request from unknown or inactive serial number SN={$sn}");
        }

        $body = implode("\r\n", [
            'GET OPTION FROM: '.$sn,
            'ATTLOGStamp=0',
            'OPERLOGStamp=0',
            'ATTPHOTOStamp=0',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;23:59',
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

        $device = $sn !== ''
            ? BiometricDevice::query()
                ->where('serial_number', $sn)
                ->where('is_active', true)
                ->first()
            : null;

        if (! $device) {
            Log::warning("ADMS: Ignoring data push from unknown or inactive serial number SN={$sn}");
            return response("OK\n", 200)->header('Content-Type', 'text/plain');
        }

        $body = $request->getContent();

        // Detect attendance log sections
        $containsAttendanceRows = preg_match(
            '/^[^\r\n\t]+\t\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/m',
            $body
        ) === 1;

        if (stripos($table, 'ATTLOG') !== false || stripos($body, 'ATTLOG') !== false || $containsAttendanceRows) {
            $this->processAttendanceLogs($device, $body);
        }

        // Device pushes enrollment user data
        if (
            stripos($table, 'ENROLL_USER') !== false
            || stripos($body, 'ENROLL_USER') !== false
            || preg_match('/(?:^|\R)USER\s+PIN=/i', $body)
        ) {
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
        $sn = $request->query('SN', $request->query('sn', ''));

        $device = $sn !== ''
            ? BiometricDevice::query()
                ->where('serial_number', $sn)
                ->where('is_active', true)
                ->first()
            : null;

        if (! $device) {
            if ($sn !== '') {
                Log::warning("ADMS: Ignoring command poll from unknown or inactive serial number SN={$sn}");
            }

            return response("OK\n", 200)->header('Content-Type', 'text/plain');
        }

        $replay = Cache::pull("zkteco.attlog-replay.{$sn}");
        if (is_array($replay) && isset($replay['from'], $replay['to'])) {
            Log::info("ADMS: Sending requested attendance replay to device {$device->name} for {$replay['from']} through {$replay['to']}.");

            return response("C:DATA QUERY ATTLOG StartTime={$replay['from']} 00:00:00 EndTime={$replay['to']} 23:59:59\r\n", 200)
                ->header('Content-Type', 'text/plain');
        }

        if (Cache::add("zkteco.query-attlog.{$sn}", true, now()->addMinutes(10))) {
            $start = now()->subDays(7)->startOfDay()->format('Y-m-d H:i:s');
            $end = now()->format('Y-m-d H:i:s');

            return response("C:DATA QUERY ATTLOG StartTime={$start} EndTime={$end}\r\n", 200)
                ->header('Content-Type', 'text/plain');
        }

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
            $statusForCheckIn = Attendance::statusForCheckIn($time);

            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if (! $attendance) {
                $attendance = new Attendance([
                    'employee_id'     => $employee->id,
                    'attendance_date' => $date,
                ]);
            }

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
                    $attendance->status         = $statusForCheckIn;
                }
            } else {
                if ($status === 1) {
                    if (empty($attendance->check_out_time) || $time > $attendance->check_out_time) {
                        $attendance->check_out_time = $time;
                    }
                } else {
                    if (empty($attendance->check_in_time) || $time < $attendance->check_in_time) {
                        $attendance->check_in_time = $time;
                    }
                    if ($attendance->status === 'Absent' || ! in_array($attendance->status, ['Leave', 'Overtime'], true)) {
                        $attendance->status = $statusForCheckIn;
                    }
                }
            }

            $attendance->source = $device->name;
            if (! in_array($attendance->status, ['Absent', 'Leave', 'Overtime'], true) && $attendance->check_in_time) {
                $attendance->status = Attendance::statusForCheckIn($attendance->check_in_time);
            }
            if (
                ! in_array($attendance->status, ['Absent', 'Leave'], true)
                && $attendance->overtime_approval_status !== 'Rejected'
                && Attendance::isOvertimeCheckout($attendance->check_out_time)
            ) {
                $attendance->status = 'Overtime';
                if (empty($attendance->overtime_approval_status)) {
                    $attendance->overtime_approval_status = 'Pending';
                }
                if (empty($attendance->overtime_reason)) {
                    $attendance->overtime_reason = 'Auto-detected from checkout time.';
                }
            }
            try {
                $attendance->save();
            } catch (UniqueConstraintViolationException $exception) {
                $attendance = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->first();

                if (! $attendance) {
                    throw $exception;
                }

                if ($status === 1) {
                    if (empty($attendance->check_out_time) || $time > $attendance->check_out_time) {
                        $attendance->check_out_time = $time;
                    }
                } elseif (empty($attendance->check_in_time) || $time < $attendance->check_in_time) {
                    $attendance->check_in_time = $time;
                }

                $attendance->source = $device->name;
                if (! in_array($attendance->status, ['Absent', 'Leave', 'Overtime'], true) && $attendance->check_in_time) {
                    $attendance->status = Attendance::statusForCheckIn($attendance->check_in_time);
                }
                if (
                    ! in_array($attendance->status, ['Absent', 'Leave'], true)
                    && $attendance->overtime_approval_status !== 'Rejected'
                    && Attendance::isOvertimeCheckout($attendance->check_out_time)
                ) {
                    $attendance->status = 'Overtime';
                    if (empty($attendance->overtime_approval_status)) {
                        $attendance->overtime_approval_status = 'Pending';
                    }
                    if (empty($attendance->overtime_reason)) {
                        $attendance->overtime_reason = 'Auto-detected from checkout time.';
                    }
                }

                $attendance->save();
                Log::debug("ADMS: Merged concurrent attendance scan for employee {$employee->id} on {$date}.");
            }
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
        $processed = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'ENROLL_USER') !== false) {
                continue;
            }

            $pin = null;
            if (preg_match('/^USER\s+PIN=(\S+)\s+Name=.*?\s+Pri=/i', $line, $matches)) {
                $pin = trim($matches[1]);
            } else {
                $parts = preg_split('/\t+/', $line);
                if (count($parts) >= 1) {
                    $pin = trim($parts[0]);
                }
            }

            if (empty($pin)) {
                continue;
            }

            $employee = Employee::query()->where('biometric_id', $pin)->first();

            if (! $employee) {
                $employee = Employee::query()->where('employee_id', $pin)->first();
            }

            if (! $employee) {
                Log::warning("ADMS: Skipping unassigned enrollment PIN={$pin}.");
                continue;
            } elseif (empty($employee->biometric_id)) {
                $employee->forceFill(['biometric_id' => $pin])->save();
            }

            EmployeeDevice::updateOrCreate(
                ['device_identifier' => $pin],
                [
                    'employee_id' => $employee->id,
                    'device_name' => $device->name,
                    'is_primary' => true,
                ]
            );

            $processed++;
        }

        Log::info("ADMS: Imported {$processed} device user record(s) from {$device->name}.");
    }

    /**
     * Resolve an employee by PIN / biometric ID.
     */
    private function resolveEmployee(string $pin, BiometricDevice $device): ?Employee
    {
        $employee = Employee::query()->where('biometric_id', $pin)->first()
            ?? Employee::query()->where('employee_id', $pin)->first()
            ?? optional(
                EmployeeDevice::query()->where('device_identifier', $pin)->with('employee')->first()
            )->employee;

        if (! $employee) {
            Log::warning("ADMS: Skipping attendance for unassigned PIN={$pin} on device {$device->name}.");
        }

        return $employee;
    }
}
