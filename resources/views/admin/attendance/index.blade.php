@extends('layouts.app')

@section('title', "Attendance - Maptech's Employee System")

@section('content')
<div class="content-wrapper attendance-index-page">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3"><i class="fas fa-calendar-check"></i> Attendance</h1>
            <div class="text-muted">Daily attendance records for {{
                \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="d-inline-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control" style="max-width: 220px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4 mb-4">
        <div class="col">
            <div class="card p-3 attendance-stat-card">
                <div class="text-muted stat-label">Attended Today</div>
                <h3 class="mb-0 stat-value">{{ $attendances->count() }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 attendance-stat-card">
                <div class="text-muted stat-label">Present</div>
                <h3 class="mb-0 stat-value">{{ $attendances->where('status', 'Present')->count() }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 attendance-stat-card">
                <div class="text-muted stat-label">Late</div>
                <h3 class="mb-0 stat-value">{{ $attendances->where('status', 'Late')->count() }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 attendance-stat-card">
                <div class="text-muted stat-label">Overtime</div>
                <h3 class="mb-0 stat-value">{{ $attendances->where('status', 'Overtime')->count() }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 attendance-stat-card">
                <div class="text-muted stat-label">Other</div>
                <h3 class="mb-0 stat-value">{{ $attendances->whereIn('status', ['Half Day', 'Leave', 'Absent'])->count() }}</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4 attendance-entry-card">
                <h5 class="mb-3 section-title">Quick Attendance Entry</h5>
                <form method="POST" action="{{ route('admin.attendance.store') }}" class="attendance-entry-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label entry-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label entry-label">Date</label>
                            <input type="date" name="attendance_date" class="form-control" value="{{ $date }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label entry-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Present">Present</option>
                                <option value="Late">Late</option>
                                <option value="Overtime">Overtime</option>
                                <option value="Half Day">Half Day</option>
                                <option value="Leave">Leave</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label entry-label">Check In</label>
                            <input type="time" name="check_in_time" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label entry-label">Check Out</label>
                            <input type="time" name="check_out_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label entry-label">Overtime Reason</label>
                        <input type="text" name="overtime_reason" class="form-control" placeholder="Required when status is Overtime">
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label entry-label">Source</label>
                        <input type="text" name="source" class="form-control" placeholder="Biometric / Manual / Import">
                    </div>
                    <div class="mb-3">
                        <label class="form-label entry-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Attendance</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle attendance-index-table">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                <tr>
                                    <td>
                                        <strong>{{ $attendance->employee?->full_name ?? 'Unknown' }}</strong><br>
                                        <small class="text-muted">{{ $attendance->employee?->employee_id ?? '-' }}</small>
                                    </td>
                                    <td>{{ $attendance->employee?->department?->name ?? '-' }}</td>
                                    <td>
                                        {{ $attendance->check_in_time ? \Illuminate\Support\Carbon::parse($attendance->check_in_time)->format('h:i:s A') : '-' }}
                                    </td>
                                    <td>
                                        {{ $attendance->check_out_time ? \Illuminate\Support\Carbon::parse($attendance->check_out_time)->format('h:i:s A') : '-' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $attendance->status === 'Present' ? 'bg-success' : ($attendance->status === 'Late' ? 'bg-warning text-dark' : ($attendance->status === 'Overtime' ? 'bg-overtime' : 'bg-secondary')) }}">
                                            {{ $attendance->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No attendance records found for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .attendance-index-page .text-muted {
        color: #94a3b8 !important;
    }

    .attendance-index-page .h3 {
        letter-spacing: .01em;
    }

    .attendance-stat-card {
        min-height: 86px;
    }

    .attendance-stat-card .stat-label {
        font-size: .76rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .attendance-stat-card .stat-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 700;
    }

    .attendance-entry-card .section-title {
        font-weight: 700;
        letter-spacing: .01em;
    }

    .attendance-entry-form .entry-label {
        font-size: .82rem;
        letter-spacing: .02em;
        font-weight: 600;
        margin-bottom: 7px;
    }

    .attendance-entry-form .form-control,
    .attendance-entry-form .form-select {
        min-height: 42px;
    }

    .attendance-index-table thead th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
        vertical-align: middle;
    }

    .attendance-index-table tbody td {
        vertical-align: middle;
    }

    .attendance-index-table thead th:nth-child(3),
    .attendance-index-table thead th:nth-child(4),
    .attendance-index-table thead th:nth-child(5),
    .attendance-index-table tbody td:nth-child(3),
    .attendance-index-table tbody td:nth-child(4),
    .attendance-index-table tbody td:nth-child(5) {
        text-align: center;
    }

    .attendance-index-table tbody td:nth-child(1),
    .attendance-index-table tbody td:nth-child(2) {
        text-align: left;
    }
</style>
@endsection
