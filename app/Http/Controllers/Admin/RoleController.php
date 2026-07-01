<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    private $availablePermissions = [
        'view_dashboard',
        'manage_users',
        'manage_roles',
        'manage_employees',
        'manage_departments',
        'manage_positions',
        'manage_biometric_devices',
        'manage_attendance',
        'manage_schedules',
        'manage_leaves',
        'manage_payroll',
        'view_reports',
        'view_activity_logs',
    ];

    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::paginate(15);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $availablePermissions = $this->availablePermissions;
        return view('admin.roles.create', compact('availablePermissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'in:' . implode(',', $this->availablePermissions),
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_role',
            'model' => 'Role',
            'model_id' => $role->id,
            'description' => "Created new role: {$role->name}",
            'new_values' => $role->toArray(),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $availablePermissions = $this->availablePermissions;
        return view('admin.roles.edit', compact('role', 'availablePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => "required|string|unique:roles,name,{$role->id}|max:255",
            'description' => 'nullable|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'in:' . implode(',', $this->availablePermissions),
        ]);

        $oldValues = $role->toArray();
        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_role',
            'model' => 'Role',
            'model_id' => $role->id,
            'description' => "Updated role: {$role->name}",
            'old_values' => $oldValues,
            'new_values' => $role->toArray(),
        ]);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role with assigned users.');
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_role',
            'model' => 'Role',
            'model_id' => $role->id,
            'description' => "Deleted role: {$role->name}",
            'old_values' => $role->toArray(),
        ]);

        $role->delete();
        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
