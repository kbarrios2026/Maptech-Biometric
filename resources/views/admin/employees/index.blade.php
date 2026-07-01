@extends('layouts.app')

@section('title', 'Employees - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3"><i class="fas fa-users"></i> Employees</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.employees.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Employee</a></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_id }}</td>
                        <td><strong>{{ $employee->full_name }}</strong></td>
                        <td>{{ $employee->department?->name ?? '-' }}</td>
                        <td>{{ $employee->position?->name ?? '-' }}</td>
                        <td><span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $employee->employment_status }}</span></td>
                        <td>
                            <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">{{ $employees->links() }}</div>
</div>
@endsection
