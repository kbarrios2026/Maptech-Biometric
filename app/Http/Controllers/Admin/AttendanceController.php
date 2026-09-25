<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\UniqueConstraintViolationException;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $attendances = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereDate('attendance_date', $date)
            ->orderByDesc('check_in_time')
            ->get();

        $employees = Employee::orderBy('first_name', 'asc')->get();

        return view('admin.attendance.index', compact('attendances', 'date', 'employees'));
    }

    public function hrAdmin(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $dateTo = min(Carbon::parse($dateTo)->toDateString(), Carbon::today()->toDateString());
        $employeeId = $request->get('employee_id');
        $status = $request->get('status');

        $employees = Employee::with(['department', 'position', 'status'])
            ->orderBy('first_name', 'asc')
            ->get();
        $selectedEmployee = $employeeId ? Employee::findOrFail($employeeId) : null;

        $query = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $recordedAttendances = $query->get();
        $attendances = $this->buildHrDtrRows(
            $recordedAttendances,
            $employees,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo),
            $employeeId
        );

        if (in_array($status, ['Absent', 'Leave'], true)) {
            $attendances = $attendances->where('status', $status)->values();
        } else {
            $status = '';
        }

        $totalCount = $attendances->count();
        $presentCount = $attendances->where('status', 'Present')->count();
        $lateCount = $attendances->where('status', 'Late')->count();
        $overtimeCount = $attendances->where('status', 'Overtime')->count();
        $leaveCount = $attendances->where('status', 'Leave')->count();
        $absentCount = $attendances->where('status', 'Absent')->count();
        $otherCount = $totalCount - $presentCount - $lateCount - $overtimeCount - $leaveCount - $absentCount;

        $attendances = new LengthAwarePaginator(
            $attendances->forPage((int) $request->get('page', 1), 100)->values(),
            $totalCount,
            100,
            (int) $request->get('page', 1),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.attendance.hr_admin', compact(
            'attendances',
            'dateFrom',
            'dateTo',
            'employees',
            'employeeId',
            'status',
            'selectedEmployee',
            'totalCount',
            'presentCount',
            'lateCount',
            'overtimeCount',
            'leaveCount',
            'absentCount',
            'otherCount'
        ));
    }

    /**
     * Add weekday DTR rows for active employees without a recorded attendance entry.
     *
     * Employees marked "On Leave" receive Leave rows; other eligible employees
     * receive Absent rows. Existing records always take precedence.
     */
    private function buildHrDtrRows($recordedAttendances, $employees, Carbon $dateFrom, Carbon $dateTo, $employeeId)
    {
        $rows = $recordedAttendances->keyBy(
            fn (Attendance $attendance) => $attendance->employee_id . '|' . $attendance->attendance_date->toDateString()
        );

        $eligibleEmployees = $employees->filter(function (Employee $employee) use ($employeeId) {
            if ($employeeId && (string) $employee->id !== (string) $employeeId) {
                return false;
            }

            return $employee->is_active
                && in_array($employee->employment_status, ['Active', 'On Leave'], true);
        });

        for ($date = $dateFrom->copy()->startOfDay(); $date->lte($dateTo); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($eligibleEmployees as $employee) {
                if ($employee->joining_date && $employee->joining_date->gt($date)) {
                    continue;
                }

                $key = $employee->id . '|' . $date->toDateString();
                if ($rows->has($key)) {
                    continue;
                }

                $attendance = new Attendance([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->toDateString(),
                    'status' => $employee->employment_status === 'On Leave' ? 'Leave' : 'Absent',
                    'source' => 'DTR status',
                ]);
                $attendance->setRelation('employee', $employee);
                $rows->put($key, $attendance);
            }
        }

        return $rows->sort(function (Attendance $left, Attendance $right) {
            return $right->attendance_date <=> $left->attendance_date
                ?: strcmp((string) $right->check_in_time, (string) $left->check_in_time);
        })->values();
    }

    public function receipt(Request $request)
    {
        $scope = $request->get('scope', 'single');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $dateTo = min(Carbon::parse($dateTo)->toDateString(), Carbon::today()->toDateString());
        $employeeId = $request->get('employee_id');

        $employee = ($scope === 'single' && $employeeId) ? Employee::findOrFail($employeeId) : null;
        $employees = Employee::with(['department', 'position', 'status'])
            ->orderBy('first_name', 'asc')
            ->get();

        $query = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);

        if ($scope === 'single' && $employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $recordedAttendances = $query->get();
        $attendances = $this->buildHrDtrRows(
            $recordedAttendances,
            $employees,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo),
            $scope === 'single' ? $employeeId : null
        )->sort(function (Attendance $left, Attendance $right) {
            return $right->attendance_date <=> $left->attendance_date
                ?: strcmp((string) $right->check_in_time, (string) $left->check_in_time);
        })->values();

        $totalEmployees = $attendances->pluck('employee_id')->unique()->count();

        return view('admin.attendance.receipt', compact(
            'attendances',
            'scope',
            'employee',
            'dateFrom',
            'dateTo',
            'totalEmployees'
        ));
    }

    public function store(Request $request)
    {
        /** @var array{employee_id:int|string, attendance_date:string, check_in_time?:string|null, check_out_time?:string|null, status:string, source?:string|null, notes?:string|null, overtime_reason?:string|null} $data */
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'status' => 'required|in:Present,Late,Absent,Half Day,Leave,Overtime',
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'overtime_reason' => 'nullable|string|required_if:status,Overtime',
        ]);

        if (
            ! empty($data['check_in_time'])
            && Attendance::isHalfDayCheckIn($data['check_in_time'])
            && ! in_array($data['status'], ['Absent', 'Leave'], true)
        ) {
            $data['status'] = 'Half Day';
        }

        $overtime = ($data['status'] ?? null) === 'Overtime';

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'attendance_date' => $data['attendance_date'],
            ],
            [
                'check_in_time' => $data['check_in_time'] ?? null,
                'check_out_time' => $data['check_out_time'] ?? null,
                'status' => $data['status'],
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
                'overtime_reason' => $overtime ? ($data['overtime_reason'] ?? null) : null,
                'overtime_approval_status' => $overtime ? 'Pending' : null,
                'overtime_approval_reason' => null,
                'overtime_approved_by' => null,
                'overtime_approved_at' => null,
            ]
        );

        if (! $overtime && ! in_array($attendance->status, ['Absent', 'Leave'], true) && Attendance::isOvertimeCheckout($attendance->check_out_time)) {
            $attendance->forceFill([
                'status' => 'Overtime',
                'overtime_approval_status' => $attendance->overtime_approval_status ?? 'Pending',
                'overtime_reason' => $attendance->overtime_reason ?: 'Auto-detected from checkout time.',
            ])->save();
        }

        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'save_attendance',
                'model' => 'Attendance',
                'model_id' => null,
                'description' => 'Saved daily attendance record',
            ]);
        }

        return back()->with('success', 'Attendance saved.');
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:Absent,Leave',
            'return_url' => 'nullable|url',
        ]);

        $attributes = [
            'status' => $data['status'],
            'check_in_time' => null,
            'check_out_time' => null,
            'source' => 'HR Admin DTR',
            'notes' => $data['status'] === 'Leave'
                ? 'Marked on leave by HR Admin.'
                : 'Marked absent by HR Admin.',
            'overtime_reason' => null,
            'overtime_approval_status' => null,
            'overtime_approval_reason' => null,
            'overtime_approved_by' => null,
            'overtime_approved_at' => null,
        ];

        $identity = [
            'employee_id' => $data['employee_id'],
            'attendance_date' => $data['attendance_date'],
        ];

        $attendance = Attendance::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->first();

        if ($attendance) {
            $attendance->forceFill($attributes)->save();
        } else {
            try {
                Attendance::create($identity + $attributes);
            } catch (UniqueConstraintViolationException) {
                // A device sync can create the same row between the lookup and insert.
                Attendance::query()
                    ->where('employee_id', $data['employee_id'])
                    ->whereDate('attendance_date', $data['attendance_date'])
                    ->update($attributes);
            }
        }

        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_attendance_status',
                'model' => 'Attendance',
                'model_id' => null,
                'description' => "Marked employee {$data['employee_id']} as {$data['status']} for {$data['attendance_date']}.",
            ]);
        }

        return $data['return_url']
            ? redirect()->to($data['return_url'])->with('success', 'Attendance status updated.')
            : back()->with('success', 'Attendance status updated.');
    }

    public function reviewOvertime(Request $request, Attendance $attendance)
    {
        if ($attendance->status !== 'Overtime') {
            return back()->with('error', 'Only overtime records can be reviewed.');
        }

        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'reason' => 'required|string|max:1000',
        ]);

        $attendance->forceFill([
            'overtime_approval_status' => $data['decision'] === 'approved' ? 'Approved' : 'Rejected',
            'overtime_approval_reason' => $data['reason'],
            'overtime_approved_by' => Auth::id(),
            'overtime_approved_at' => now(),
        ])->save();

        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'review_overtime',
                'model' => 'Attendance',
                'model_id' => $attendance->id,
                'description' => 'Overtime ' . $data['decision'] . ' with reason: ' . $data['reason'],
            ]);
        }

        return back()->with('success', 'Overtime review saved.');
    }
}
