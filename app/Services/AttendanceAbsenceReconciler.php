<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceAbsenceReconciler
{
    public function reconcile(string $dateFrom, string $dateTo): void
    {
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
                ->whereDate('attendance_date', $date->toDateString())
                ->whereIn('employee_id', $eligibleEmployeeIds)
                ->pluck('employee_id')
                ->all();
            $existingEmployeeIds = array_fill_keys($existingEmployeeIds, true);
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
