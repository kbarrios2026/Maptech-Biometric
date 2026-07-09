<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $positions = Position::with('department')->latest()->paginate(10);
        return view('admin.positions.index', compact('positions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.positions.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $position = Position::create([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'base_salary' => $request->base_salary,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_position',
            'model' => 'Position',
            'model_id' => $position->id,
            'description' => "Created position: {$position->name}",
            'new_values' => $position->toArray(),
        ]);

        return redirect()->route('admin.positions.index')
            ->with('success', 'Position created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        return view('admin.positions.show', compact('position'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Position $position)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.positions.edit', compact('position', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions,name,' . $position->id,
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldValues = $position->toArray();
        $position->update([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'base_salary' => $request->base_salary,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_position',
            'model' => 'Position',
            'model_id' => $position->id,
            'description' => "Updated position: {$position->name}",
            'old_values' => $oldValues,
            'new_values' => $position->toArray(),
        ]);

        return redirect()->route('admin.positions.show', $position)
            ->with('success', 'Position updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return redirect()->route('admin.positions.index')
                ->with('error', 'Position cannot be deleted while it has assigned employees.');
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_position',
            'model' => 'Position',
            'model_id' => $position->id,
            'description' => "Deleted position: {$position->name}",
            'old_values' => $position->toArray(),
        ]);

        $position->delete();

        return redirect()->route('admin.positions.index')
            ->with('success', 'Position deleted successfully.');
    }
}
