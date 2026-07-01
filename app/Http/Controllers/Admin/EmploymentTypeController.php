<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmploymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmploymentTypeController extends Controller
{
    public function index()
    {
        $types = EmploymentType::latest()->paginate(20);
        return view('admin.employment_types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.employment_types.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:employment_types,name', 'description' => 'nullable|string']);
        $type = EmploymentType::create($request->only(['name', 'description']));
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'create_employment_type', 'model' => 'EmploymentType', 'model_id' => $type->id, 'description' => "Created type {$type->name}", 'new_values' => $type->toArray()]);
        }
        return redirect()->route('admin.employment-types.index')->with('success', 'Employment type created.');
    }

    public function edit(EmploymentType $employmentType)
    {
        return view('admin.employment_types.edit', ['type' => $employmentType]);
    }

    public function update(Request $request, EmploymentType $employmentType)
    {
        $request->validate(['name' => 'required|string|unique:employment_types,name,' . $employmentType->id, 'description' => 'nullable|string']);
        $old = $employmentType->toArray();
        $employmentType->update($request->only(['name', 'description']));
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'update_employment_type', 'model' => 'EmploymentType', 'model_id' => $employmentType->id, 'description' => "Updated type {$employmentType->name}", 'old_values' => $old, 'new_values' => $employmentType->toArray()]);
        }
        return redirect()->route('admin.employment-types.index')->with('success', 'Employment type updated.');
    }

    public function destroy(EmploymentType $employmentType)
    {
        if (class_exists('App\\Models\\ActivityLog')) {
            \App\Models\ActivityLog::create(['user_id' => Auth::id(), 'action' => 'delete_employment_type', 'model' => 'EmploymentType', 'model_id' => $employmentType->id, 'description' => "Deleted type {$employmentType->name}", 'old_values' => $employmentType->toArray()]);
        }
        $employmentType->delete();
        return redirect()->route('admin.employment-types.index')->with('success', 'Employment type deleted.');
    }
}
