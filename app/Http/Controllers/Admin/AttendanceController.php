<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $attendances = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->whereDate('attendance_date', $date)
            ->orderBy('check_in_time')
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
            ->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->orderBy('attendance_date')->orderBy('check_in_time')->get();

        $totalCount = $attendances->count();
        $presentCount = $attendances->where('status', 'Present')->count();
        $lateCount = $attendances->where('status', 'Late')->count();
        $otherCount = $totalCount - $presentCount - $lateCount;

        return view('admin.attendance.hr_admin', compact(
            'attendances', 'dateFrom', 'dateTo', 'employees', 'employeeId', 'selectedEmployee',
            'totalCount', 'presentCount', 'lateCount', 'otherCount'
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

        $attendances = $query->orderBy('attendance_date')->orderBy('check_in_time')->get();

        $totalEmployees = $attendances->pluck('employee_id')->unique()->count();

        return view('admin.attendance.receipt', compact(
            'attendances', 'scope', 'employee', 'dateFrom', 'dateTo', 'totalEmployees'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'status' => 'required|in:Present,Late,Absent,Half Day,Leave',
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'attendance_date' => $request->attendance_date,
            ],
            [
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,
                'status' => $request->status,
                'source' => $request->source,
                'notes' => $request->notes,
            ]
        );

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
}
