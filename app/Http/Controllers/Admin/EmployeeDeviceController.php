<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDevice;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDeviceController extends Controller
{
    public function index()
    {
        $devices = EmployeeDevice::with('employee')->latest()->paginate(20);
        return view('admin.employee_devices.index', compact('devices'));
    }

    public function create()
    {
        $employees = Employee::orderBy('first_name')->get();
        return view('admin.employee_devices.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate(['employee_id' => 'required|exists:employees,id', 'device_identifier' => 'required|string|unique:employee_devices,device_identifier', 'device_name' => 'nullable|string', 'is_primary' => 'sometimes|boolean']);
        $data = $request->only(['employee_id', 'device_identifier', 'device_name']);
        $data['is_primary'] = $request->boolean('is_primary');
        $device = EmployeeDevice::create($data);
        if ($device->is_primary) {
            EmployeeDevice::where('employee_id', $device->employee_id)->where('id', '<>', $device->id)->update(['is_primary' => false]);
        }
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'create_employee_device', 'model' => 'EmployeeDevice', 'model_id' => $device->id, 'description' => "Mapped device {$device->device_identifier} to employee #{$device->employee_id}", 'new_values' => $device->toArray()]);
        }
        return redirect()->route('admin.employee-devices.index')->with('success', 'Device mapped.');
    }

    public function edit(EmployeeDevice $employeeDevice)
    {
        $employees = Employee::orderBy('first_name')->get();
        return view('admin.employee_devices.edit', ['device' => $employeeDevice, 'employees' => $employees]);
    }

    public function update(Request $request, EmployeeDevice $employeeDevice)
    {
        $request->validate(['employee_id' => 'required|exists:employees,id', 'device_identifier' => 'required|string|unique:employee_devices,device_identifier,' . $employeeDevice->id, 'device_name' => 'nullable|string', 'is_primary' => 'sometimes|boolean']);
        $old = $employeeDevice->toArray();
        $employeeDevice->update(array_merge($request->only(['employee_id', 'device_identifier', 'device_name']), ['is_primary' => $request->boolean('is_primary')]));
        if ($employeeDevice->is_primary) {
            EmployeeDevice::where('employee_id', $employeeDevice->employee_id)->where('id', '<>', $employeeDevice->id)->update(['is_primary' => false]);
        }
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'update_employee_device', 'model' => 'EmployeeDevice', 'model_id' => $employeeDevice->id, 'description' => "Updated device mapping {$employeeDevice->device_identifier}", 'old_values' => $old, 'new_values' => $employeeDevice->toArray()]);
        }
        return redirect()->route('admin.employee-devices.index')->with('success', 'Device updated.');
    }

    public function destroy(EmployeeDevice $employeeDevice)
    {
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'delete_employee_device', 'model' => 'EmployeeDevice', 'model_id' => $employeeDevice->id, 'description' => "Deleted device {$employeeDevice->device_identifier}", 'old_values' => $employeeDevice->toArray()]);
        }
        $employeeDevice->delete();
        return redirect()->route('admin.employee-devices.index')->with('success', 'Device mapping removed.');
    }
}
