<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->employee) {
            abort(403, 'Employee profile not found.');
        }

        $employee = $user->employee()->with(['department', 'position', 'status'])->firstOrFail();
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $dateTo = min(Carbon::parse($dateTo)->toDateString(), Carbon::today()->toDateString());

        $attendances = Attendance::with(['employee.department', 'employee.position', 'employee.status'])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo)
            ->orderBy('attendance_date', 'asc')
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
}
