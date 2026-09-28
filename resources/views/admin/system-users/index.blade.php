@extends('layouts.app')

@section('title', "System Users - Maptech's Employee System")

@section('content')
<style>
    .system-users-table {
        table-layout: fixed;
        min-width: 760px;
        border-collapse: collapse;
        border-spacing: 0;
    }

    .system-users-table th,
    .system-users-table td {
        vertical-align: middle;
    }

    .system-users-table th {
        padding: 9px 12px;
    }

    .system-users-table td {
        padding: 1px 12px;
        line-height: 16px;
    }

    .system-users-table td:nth-child(1),
    .system-users-table td:nth-child(2),
    .system-users-table td:nth-child(3) {
        white-space: nowrap;
    }

    .system-users-table th:nth-child(1),
    .system-users-table td:nth-child(1) {
        width: 17%;
    }

    .system-users-table th:nth-child(2),
    .system-users-table td:nth-child(2) {
        width: 37%;
    }

    .system-users-table th:nth-child(3),
    .system-users-table td:nth-child(3) {
        width: 20%;
    }

    .system-users-table th:nth-child(4),
    .system-users-table td:nth-child(4) {
        width: 12%;
    }

    .system-users-table th:nth-child(5),
    .system-users-table td:nth-child(5) {
        width: 14%;
    }

    .system-users-table .created-cell {
        white-space: nowrap;
    }

    .system-users-table .actions-cell {
        white-space: nowrap;
    }

    .system-users-table .actions-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .system-users-table .actions-group form {
        display: inline-flex;
        margin: 0;
    }

    .system-users-table .actions-group .btn {
        width: 32px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
</style>
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3"><i class="fas fa-user-tie"></i> System Users</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.system-users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New User
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 system-users-table">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role)
                            <span class="badge bg-info">{{ $user->role->name }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="created-cell"><small>{{ $user->created_at->format('M d, Y') }}</small></td>
                        <td class="actions-cell">
                            <div class="actions-group">
                                <a href="{{ route('admin.system-users.show', $user) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.system-users.edit', $user) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.system-users.destroy', $user) }}"
                                      onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            No system users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
