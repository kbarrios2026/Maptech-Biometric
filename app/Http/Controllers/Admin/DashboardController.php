<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $totalUsers = User::count();
        $totalEmployees = Employee::count();
        $totalDepartments = Department::count();
        $activeEmployees = Employee::where('employment_status', 'Active')->count();
        $recentActivityLogs = ActivityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalEmployees' => $totalEmployees,
            'totalDepartments' => $totalDepartments,
            'activeEmployees' => $activeEmployees,
            'recentActivityLogs' => $recentActivityLogs,
        ]);
    }
}
