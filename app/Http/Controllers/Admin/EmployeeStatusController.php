<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeStatusController extends Controller
{
    public function index()
    {
        $items = EmployeeStatus::latest()->paginate(10);
        return view('admin.employee_statuses.index', compact('items'));
    }

    public function create()
    {
        return view('admin.employee_statuses.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:employee_statuses,name', 'color' => 'nullable|string']);
        $item = EmployeeStatus::create($request->only(['name', 'color']));
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'create_employee_status', 'model' => 'EmployeeStatus', 'model_id' => $item->id, 'description' => "Created status {$item->name}", 'new_values' => $item->toArray()]);
        }
        return redirect()->route('admin.employee-statuses.index')->with('success', 'Status created.');
    }

    public function edit(EmployeeStatus $employeeStatus)
    {
        return view('admin.employee_statuses.edit', ['item' => $employeeStatus]);
    }

    public function update(Request $request, EmployeeStatus $employeeStatus)
    {
        $request->validate(['name' => 'required|string|unique:employee_statuses,name,' . $employeeStatus->id, 'color' => 'nullable|string']);
        $old = $employeeStatus->toArray();
        $employeeStatus->update($request->only(['name', 'color']));
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'update_employee_status', 'model' => 'EmployeeStatus', 'model_id' => $employeeStatus->id, 'description' => "Updated status {$employeeStatus->name}", 'old_values' => $old, 'new_values' => $employeeStatus->toArray()]);
        }
        return redirect()->route('admin.employee-statuses.index')->with('success', 'Status updated.');
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'delete_employee_status', 'model' => 'EmployeeStatus', 'model_id' => $employeeStatus->id, 'description' => "Deleted status {$employeeStatus->name}", 'old_values' => $employeeStatus->toArray()]);
        }
        $employeeStatus->delete();
        return redirect()->route('admin.employee-statuses.index')->with('success', 'Status deleted.');
    }
}
