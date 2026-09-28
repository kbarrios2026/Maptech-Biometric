@extends('layouts.app')

@section('title', 'My Attendance - Maptech\'s Employee System')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-clock"></i> My Attendance</h1>
        <p class="page-subtitle">Printable daily attendance record. This view is read-only and cannot be edited.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('employee.attendance.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Present</div>
                <div class="stat-value">{{ $totalPresent }}</div>
            </div>
            <div class="stat-icon" style="background:#f0fdf4; color:#22c55e;"><i class="fas fa-check"></i></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Late</div>
                <div class="stat-value">{{ $totalLate }}</div>
            </div>
            <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Absent</div>
                <div class="stat-value">{{ $totalAbsent }}</div>
            </div>
            <div class="stat-icon" style="background:#fef2f2; color:#ef4444;"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Leave</div>
                <div class="stat-value">{{ $totalLeave }}</div>
            </div>
            <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fas fa-umbrella-beach"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-invoice me-2 text-muted"></i>Attendance Records</span>
        <span class="badge bg-primary">Read Only</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive print-table">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}</td>
                            <td>{{ $attendance->check_in_time ? \Illuminate\Support\Carbon::parse($attendance->check_in_time)->format('h:i:s A') : '-' }}</td>
                            <td>{{ $attendance->check_out_time ? \Illuminate\Support\Carbon::parse($attendance->check_out_time)->format('h:i:s A') : '-' }}</td>
                            <td>
                                @php
                                    $statusClass = match($attendance->status) {
                                        'Present' => 'bg-success',
                                        'Late' => 'bg-warning',
                                        'Absent' => 'bg-danger',
                                        'Leave' => 'bg-info',
                                        'Overtime' => 'bg-overtime',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $attendance->status }}</span>
                            </td>
                            <td>{{ $attendance->source ?? 'Manual' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No attendance records found in the selected date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background: #fff !important;
        }

        .app-navbar,
        .app-sidebar,
        .sidebar-overlay,
        .btn,
        .form-control,
        .page-header,
        .stat-card,
        .card-header .badge,
        .d-flex.gap-2,
        .no-print {
            display: none !important;
        }

        .app-main {
            padding: 0 !important;
            background: #fff !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
        }

        .table {
            font-size: 12px;
        }
    }
</style>
@endsection
