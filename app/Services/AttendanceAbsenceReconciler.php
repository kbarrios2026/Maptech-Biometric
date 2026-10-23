<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceAbsenceReconciler
{
    public function reconcile(string $dateFrom, string $dateTo): void
    {
        DB::table('attendances as stale_absences')
            ->whereDate('stale_absences.attendance_date', '>=', $dateFrom)
            ->whereDate('stale_absences.attendance_date', '<=', $dateTo)
            ->where('stale_absences.status', 'Absent')
            ->where('stale_absences.source', 'Attendance Reconciliation')
            ->whereNull('stale_absences.check_in_time')
            ->whereNull('stale_absences.check_out_time')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('attendances as recorded')
                    ->whereColumn('recorded.employee_id', 'stale_absences.employee_id')
                    ->where('recorded.status', '!=', 'Absent')
                    ->where(function ($query) {
                        $query->whereRaw('DATE(recorded.attendance_date) = DATE(stale_absences.attendance_date)')
                            ->orWhere(function ($query) {
                                $query->whereRaw('DATE(recorded.attendance_date) < DATE(stale_absences.attendance_date)')
                                    ->whereRaw('DATE(recorded.check_out_date) >= DATE(stale_absences.attendance_date)');
                            });
                    });
            })
            ->delete();

        $date = Carbon::parse($dateFrom)->startOfDay();
        $lastCompletedDate = Carbon::parse($dateTo)->startOfDay()
            ->min(Carbon::today()->subDay());

        if ($date->gt($lastCompletedDate)) {
            return;
        }

        $employees = Employee::query()
            ->where('is_active', true)
            ->where('employment_status', 'Active')
            ->whereDate('joining_date', '<=', $lastCompletedDate)
            ->get(['id', 'joining_date']);

        for (; $date->lte($lastCompletedDate); $date->addDay()) {
            if (! $date->isWeekday()) {
                continue;
            }

            $eligibleEmployeeIds = $employees
                ->filter(fn (Employee $employee) => $employee->joining_date->lte($date))
                ->modelKeys();

            if ($eligibleEmployeeIds === []) {
                continue;
            }

            $existingEmployeeIds = Attendance::query()
                ->where(function ($query) use ($date) {
                    $query->whereDate('attendance_date', $date->toDateString())
                        ->orWhere(function ($query) use ($date) {
                            $query->whereDate('attendance_date', '<', $date->toDateString())
                                ->whereDate('check_out_date', '>=', $date->toDateString());
                        });
                })
                ->whereIn('employee_id', $eligibleEmployeeIds)
                ->pluck('employee_id')
                ->all();
            $suppressedEmployeeIds = DB::table('attendance_absence_suppressions')
                ->whereDate('attendance_date', $date->toDateString())
                ->whereIn('employee_id', $eligibleEmployeeIds)
                ->pluck('employee_id')
                ->all();
            $existingEmployeeIds = array_fill_keys($existingEmployeeIds, true);
            foreach ($suppressedEmployeeIds as $employeeId) {
                $existingEmployeeIds[$employeeId] = true;
            }
            $now = now();
            $missingAttendances = [];

            foreach ($eligibleEmployeeIds as $employeeId) {
                if (isset($existingEmployeeIds[$employeeId])) {
                    continue;
                }

                $missingAttendances[] = [
                    'employee_id' => $employeeId,
                    'attendance_date' => $date->toDateString(),
                    'check_in_time' => null,
                    'check_out_time' => null,
                    'status' => 'Absent',
                    'source' => 'Attendance Reconciliation',
                    'notes' => 'Automatically marked absent because no attendance was recorded for this weekday.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($missingAttendances, 100) as $attendanceChunk) {
                Attendance::query()->insertOrIgnore($attendanceChunk);
            }
        }
    }
}
