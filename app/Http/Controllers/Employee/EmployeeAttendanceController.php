<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceAbsenceReconciler;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request, AttendanceAbsenceReconciler $absenceReconciler)
    {
        $user = Auth::user();

        if (! $user || ! $user->employee) {
            abort(403, 'Employee profile not found.');
        }

        $employee = $user->employee()->with(['department', 'position', 'status'])->firstOrFail();
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $dateTo = min(Carbon::parse($dateTo)->toDateString(), Carbon::today()->toDateString());
        $absenceReconciler->reconcile($dateFrom, $dateTo);

        $attendances = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo)
            ->orderBy('attendance_date')
            ->get();

        $totalPresent = $attendances->where('status', 'Present')->count();
        $totalLate = $attendances->where('status', 'Late')->count();
        $totalAbsent = $attendances->where('status', 'Absent')->count();
        $totalLeave = $attendances->where('status', 'Leave')->count();
        $totalOvertime = $attendances->where('status', 'Overtime')->count();

        return view('employee.attendance.index', compact(
            'employee',
            'attendances',
            'dateFrom',
            'dateTo',
            'totalPresent',
            'totalLate',
            'totalAbsent',
            'totalLeave',
            'totalOvertime'
        ));
    }

    public function submitAbsenceReflection(Request $request, Attendance $attendance): RedirectResponse
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found.');
        abort_unless($attendance->employee_id === $employee->id, 404);
        abort_unless($attendance->status === 'Absent', 403, 'Reflections can only be submitted for absent days.');

        $data = $request->validate([
            'absence_reflection' => 'required|string|max:2000',
        ]);

        $attendance->forceFill([
            'absence_reflection' => $data['absence_reflection'],
        ])->save();

        return back()->with('success', 'Your absence reflection has been saved.');
    }
}
