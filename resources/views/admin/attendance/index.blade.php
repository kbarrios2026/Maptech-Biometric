@extends('layouts.app')

@section('title', 'Attendance - Employee Management System')

@section('content')
<div class="content-wrapper">
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

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted">Attended Today</div>
                <h3 class="mb-0">{{ $attendances->count() }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted">Present</div>
                <h3 class="mb-0">{{ $attendances->where('status', 'Present')->count() }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted">Late</div>
                <h3 class="mb-0">{{ $attendances->where('status', 'Late')->count() }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted">Other</div>
                <h3 class="mb-0">{{ $attendances->whereIn('status', ['Half Day', 'Leave', 'Absent'])->count() }}</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4">
                <h5 class="mb-3">Quick Attendance Entry</h5>
                <form method="POST" action="{{ route('admin.attendance.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="attendance_date" class="form-control" value="{{ $date }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Present">Present</option>
                                <option value="Late">Late</option>
                                <option value="Half Day">Half Day</option>
                                <option value="Leave">Leave</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check In</label>
                            <input type="time" name="check_in_time" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out</label>
                            <input type="time" name="check_out_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control" placeholder="Biometric / Manual / Import">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Attendance</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
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
                                        <span class="badge {{ $attendance->status === 'Present' ? 'bg-success' : ($attendance->status === 'Late' ? 'bg-warning text-dark' : 'bg-secondary') }}">
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
