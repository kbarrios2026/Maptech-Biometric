<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $employeeId = $request->get('employee_id');

        $employees = Employee::orderBy('first_name', 'asc')->get();
        $selectedEmployee = $employeeId ? Employee::findOrFail($employeeId) : null;

        $query = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $totalCount = (clone $query)->count();
        $presentCount = (clone $query)->where('status', 'Present')->count();
        $lateCount = (clone $query)->where('status', 'Late')->count();
        $overtimeCount = (clone $query)->where('status', 'Overtime')->count();
        $otherCount = $totalCount - $presentCount - $lateCount - $overtimeCount;

        $attendances = $query
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_time')
            ->paginate(100)
            ->withQueryString();

        return view('admin.attendance.hr_admin', compact(
            'attendances',
            'dateFrom',
            'dateTo',
            'employees',
            'employeeId',
            'selectedEmployee',
            'totalCount',
            'presentCount',
            'lateCount',
            'overtimeCount',
            'otherCount'
        ));
    }

    public function receipt(Request $request)
    {
        $scope = $request->get('scope', 'single');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $employeeId = $request->get('employee_id');

        $employee = ($scope === 'single' && $employeeId) ? Employee::findOrFail($employeeId) : null;

        $query = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        if ($scope === 'single' && $employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_time')
            ->get();

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
