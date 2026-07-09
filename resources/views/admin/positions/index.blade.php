@extends('layouts.app')

@section('title', "Positions - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3"><i class="fas fa-briefcase"></i> Positions</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.positions.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Position</a></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Base Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($positions as $position)
                    <tr>
                        <td><strong>{{ $position->name }}</strong></td>
                        <td>{{ $position->department?->name ?? '-' }}</td>
                        <td>{{ $position->base_salary ? number_format($position->base_salary, 2) : '-' }}</td>
                        <td><span class="badge {{ $position->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $position->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('admin.positions.show', $position) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.positions.edit', $position) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No positions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">{{ $positions->links() }}</div>
</div>
@endsection