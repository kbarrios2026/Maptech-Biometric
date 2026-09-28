<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SystemUserController extends Controller
{
    /**
     * Display a listing of system users.
     */
    public function index()
    {
        $users = User::with('role')
            ->paginate(10);

        return view('admin.system-users.index', compact('users'));
    }

    /**
     * Show the form for creating a new system user.
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.system-users.create', compact('roles'));
    }

    /**
     * Store a newly created system user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_user',
            'model' => 'User',
            'model_id' => $user->id,
            'description' => "Created new system user: {$user->email}",
            'new_values' => $user->toArray(),
        ]);

        return redirect()->route('admin.system-users.index')
            ->with('success', 'System user created successfully.');
    }

    /**
     * Display the specified system user.
     */
    public function show(User $systemUser)
    {
        return view('admin.system-users.show', compact('systemUser'));
    }

    /**
     * Show the form for editing the specified system user.
     */
    public function edit(User $systemUser)
    {
        $roles = Role::all();
        return view('admin.system-users.edit', compact('systemUser', 'roles'));
    }

    /**
     * Update the specified system user.
     */
    public function update(Request $request, User $systemUser)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$systemUser->id}",
            'role_id' => 'required|exists:roles,id',
        ]);

        $oldValues = $systemUser->toArray();
        $systemUser->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_user',
            'model' => 'User',
            'model_id' => $systemUser->id,
            'description' => "Updated system user: {$systemUser->email}",
            'old_values' => $oldValues,
            'new_values' => $systemUser->toArray(),
        ]);

        return redirect()->route('admin.system-users.show', $systemUser)
            ->with('success', 'System user updated successfully.');
    }

    /**
     * Set a new password for the specified system user.
     */
    public function updatePassword(Request $request, User $systemUser)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $systemUser->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'reset_user_password',
            'model' => 'User',
            'model_id' => $systemUser->id,
            'description' => "Reset password for system user: {$systemUser->email}",
        ]);

        return redirect()->route('admin.system-users.edit', $systemUser)
            ->with('success', 'Password updated. The new password is ready to use.');
    }

    /**
     * Remove the specified system user.
     */
    public function destroy(User $systemUser)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_user',
            'model' => 'User',
            'model_id' => $systemUser->id,
            'description' => "Deleted system user: {$systemUser->email}",
            'old_values' => $systemUser->toArray(),
        ]);

        $systemUser->delete();
        return redirect()->route('admin.system-users.index')
            ->with('success', 'System user deleted successfully.');
    }
}
