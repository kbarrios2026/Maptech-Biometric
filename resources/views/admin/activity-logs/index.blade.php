@extends('layouts.app')

@section('title', "Activity Logs - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-history"></i> Activity Logs</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}" placeholder="To Date">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="action" value="{{ request('action') }}" placeholder="Action">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs as $log)
                    <tr>
                        <td>
                            @if($log->user)
                            <strong>{{ $log->user->name }}</strong>
                            @else
                            <span class="text-muted">Unknown</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $log->action }}</span>
                        </td>
                        <td>{{ $log->description }}</td>
                        <td><small>{{ $log->ip_address }}</small></td>
                        <td>
                            <small>{{ $log->created_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            No activity logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $activityLogs->links() }}
    </div>
</div>
@endsection
