@extends('layouts.app')

@section('title', 'HR Admin Attendance - Employee Management System')

@section('content')
<div class="content-wrapper attendance-page">
    <div class="row mb-4 align-items-center g-3 attendance-header">
        <div class="col-12 col-xl-4">
            <h1 class="h3"><i class="fas fa-calendar-check"></i> HR Admin Attendance</h1>
            <div class="text-muted">
                Daily attendance records for {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}
                @if($selectedEmployee)
                    - {{ $selectedEmployee->full_name }} ({{ $selectedEmployee->employee_id }})
                @else
                    - All Employees
                @endif
            </div>
        </div>
        <div class="col-12 col-xl-8 mt-0">
            <form method="GET" action="{{ route('admin.attendance.hr-admin') }}" class="attendance-toolbar">
                <div class="toolbar-bar">
                    <span class="toolbar-chip">Filter</span>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                    <select name="employee_id" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $employeeId === (string) $employee->id)>
                                {{ $employee->full_name }} ({{ $employee->employee_id }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>

                <div class="toolbar-bar toolbar-bar-print">
                    <span class="toolbar-chip">Print</span>
                    <a class="btn btn-outline-primary"
                       href="{{ route('admin.attendance.receipt', ['scope' => 'all', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       target="_blank" rel="noopener">
                        <i class="fas fa-print me-1"></i> All Employees DTR
                    </a>
                    <a class="btn btn-primary {{ empty($employeeId) ? 'disabled' : '' }}"
                       href="{{ empty($employeeId) ? '#' : route('admin.attendance.receipt', ['scope' => 'single', 'employee_id' => $employeeId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       target="_blank" rel="noopener"
                       @if(empty($employeeId)) aria-disabled="true" tabindex="-1" @endif>
                        <i class="fas fa-user me-1"></i> Single Employee DTR
                    </a>
                </div>
            </form>
            @if(empty($employeeId))
                <div class="text-muted small mt-2">Select an employee to enable Single Employee DTR printing.</div>
            @endif
        </div>
    </div>

    <div class="card mb-4 report-preview no-print">
        <div class="card-body p-4">
            <div class="report-header mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="report-logo-slot">
                        <span>LOGO</span>
                    </div>
                    <div>
                        <div class="report-kicker">Date Time Report</div>
                        <h2 class="report-title mb-1">{{ config('app.name', 'Employee Management System') }}</h2>
                        <div class="text-muted">
                            @if($selectedEmployee)
                                {{ $selectedEmployee->full_name }} ({{ $selectedEmployee->employee_id }})
                            @else
                                All Employees
                            @endif
                        </div>
                    </div>
                </div>
                <div class="text-end report-meta">
                    <div><strong>Printed:</strong> {{ now()->format('M d, Y h:i A') }}</div>
                    <div><strong>Range:</strong> {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}</div>
                </div>
            </div>

            <div class="report-section-bar mb-3"></div>

            <div class="report-grid mb-4">
                <div class="report-block">
                    <div class="report-label">Scope</div>
                    <div class="report-value">{{ $selectedEmployee ? 'Single Employee' : 'All Employees' }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Total Records</div>
                    <div class="report-value">{{ $totalCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Present</div>
                    <div class="report-value">{{ $presentCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Late</div>
                    <div class="report-value">{{ $lateCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Other</div>
                    <div class="report-value">{{ $otherCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Report Date</div>
                    <div class="report-value">{{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}</div>
                </div>
            </div>

            <div class="report-footer text-center text-muted">
                HR Admin - Date & Time Report
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Attended Today</div>
                <h3 class="mb-0">{{ $attendances->count() }}</h3>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Present</div>
                <h3 class="mb-0">{{ $presentCount }}</h3>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Late</div>
                <h3 class="mb-0">{{ $lateCount }}</h3>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Other</div>
                <h3 class="mb-0">{{ $otherCount }}</h3>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle hr-attendance-table">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
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
                            <td>
                                <strong>{{ $attendance->employee?->full_name ?? 'Unknown' }}</strong><br>
                                <small class="text-muted">{{ $attendance->employee?->employee_id ?? '-' }}</small>
                            </td>
                            <td>{{ $attendance->employee?->department?->name ?? '-' }}</td>
                            <td>{{ $attendance->attendance_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                {{ $attendance->check_in_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $attendance->check_in_time)->format('h:i:s A') : '-' }}
                            </td>
                            <td>
                                {{ $attendance->check_out_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $attendance->check_out_time)->format('h:i:s A') : '-' }}
                            </td>
                            <td>
                                <span class="badge {{ $attendance->status === 'Present' ? 'bg-success' : ($attendance->status === 'Late' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $attendance->status }}
                                </span>
                            </td>
                            <td>{{ $attendance->source ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No attendance records found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($attendances, 'links'))
            <div class="p-3 border-top">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .attendance-page {
        max-width: 1320px;
        margin-left: auto;
        margin-right: auto;
    }

    .attendance-header {
        margin-left: 0;
        margin-right: 0;
    }

    .attendance-toolbar {
        width: 100%;
        max-width: none;
        margin: 0;
        display: grid;
        gap: 8px;
    }

    .toolbar-bar {
        display: grid;
        grid-template-columns: auto minmax(160px, 220px) minmax(160px, 220px) minmax(220px, 1fr) auto;
        gap: 8px;
        align-items: center;
        padding: 8px;
        border: 1px solid #d8dee6;
        border-radius: 10px;
        background: #fff;
    }

    .toolbar-bar-print {
        grid-template-columns: auto auto auto;
        background: #f8fafc;
    }

    .report-preview {
        border: 1px solid #d6d6d6;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%);
    }

    .report-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .report-logo-slot {
        width: 64px;
        height: 64px;
        border: 1px dashed #c2c8d0;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        background: #fff;
    }

    .report-kicker {
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: #6366f1;
    }

    .report-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
    }

    .report-meta {
        font-size: .84rem;
        color: #475569;
    }

    .report-section-bar {
        height: 4px;
        border-radius: 4px;
        background: linear-gradient(90deg, #6366f1 0%, #818cf8 60%, #c7d2fe 100%);
    }

    .report-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .report-block {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        background: #fff;
    }

    .report-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        font-weight: 700;
    }

    .report-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin-top: 2px;
    }

    .report-footer {
        font-size: .8rem;
        letter-spacing: .04em;
        border-top: 1px dashed #d6d6d6;
        padding-top: 12px;
    }

    .toolbar-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        padding: 0 10px;
        border-radius: 8px;
        background: #e5e7eb;
        color: #1f2937;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        white-space: nowrap;
    }

    .toolbar-bar .form-select,
    .toolbar-bar .form-control,
    .toolbar-bar .btn {
        min-width: 0;
    }

    .toolbar-bar .btn {
        white-space: nowrap;
    }

    .hr-summary-card {
        min-height: 96px;
    }

    .hr-attendance-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .hr-attendance-table td,
    .hr-attendance-table th {
        vertical-align: middle;
        font-size: .92rem;
    }

    body.dark-mode .toolbar-bar {
        background: #192238;
        border-color: #2a3f5a;
    }

    body.dark-mode .toolbar-bar-print {
        background: #0f172a;
    }

    body.dark-mode .toolbar-chip {
        background: #2a3f5a;
        color: #cbd5e1;
    }

    body.dark-mode .report-preview {
        border-color: #2a3f5a;
        background: linear-gradient(180deg, #192238 0%, #111a2b 100%);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    }

    body.dark-mode .report-logo-slot {
        border-color: #334155;
        color: #94a3b8;
        background: #0f172a;
    }

    body.dark-mode .report-title {
        color: #e2e8f0;
    }

    body.dark-mode .report-meta {
        color: #cbd5e1;
    }

    body.dark-mode .report-block {
        border-color: #334155;
        background: #0f172a;
    }

    body.dark-mode .report-label {
        color: #94a3b8;
    }

    body.dark-mode .report-value {
        color: #e2e8f0;
    }

    body.dark-mode .report-footer {
        border-top-color: #334155;
        color: #94a3b8 !important;
    }

    @media (max-width: 1199.98px) {
        .toolbar-bar,
        .toolbar-bar-print {
            grid-template-columns: 1fr;
        }

        .toolbar-chip {
            width: fit-content;
        }
    }

    @media (max-width: 767.98px) {
        .report-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
