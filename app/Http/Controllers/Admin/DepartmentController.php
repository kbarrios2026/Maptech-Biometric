<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::latest()->paginate(15);
        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.departments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'nullable|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $department = Department::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_department',
            'model' => 'Department',
            'model_id' => $department->id,
            'description' => "Created department: {$department->name}",
            'new_values' => $department->toArray(),
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'code' => 'nullable|string|max:50|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldValues = $department->toArray();
        $department->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_department',
            'model' => 'Department',
            'model_id' => $department->id,
            'description' => "Updated department: {$department->name}",
            'old_values' => $oldValues,
            'new_values' => $department->toArray(),
        ]);

        return redirect()->route('admin.departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        if ($department->employees()->exists()) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Department cannot be deleted while it has assigned employees.');
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_department',
            'model' => 'Department',
            'model_id' => $department->id,
            'description' => "Deleted department: {$department->name}",
            'old_values' => $department->toArray(),
        ]);

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
