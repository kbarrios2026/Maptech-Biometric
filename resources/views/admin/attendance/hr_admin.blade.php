@extends('layouts.app')

@section('title', "HR Admin Attendance - Maptech's Employee System")

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
                <span class="d-block small">Live updates refresh every 30 seconds.</span>
            </div>
        </div>
        <div class="col-12 col-xl-8 mt-0">
            <form method="GET" action="{{ route('admin.attendance.hr-admin') }}" class="attendance-toolbar">
                <div class="toolbar-bar">
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
                    <select name="status" class="form-select">
                        <option value="" @selected($status === '')>All Statuses</option>
                        <option value="Absent" @selected($status === 'Absent')>Absent</option>
                        <option value="Leave" @selected($status === 'Leave')>On Leave</option>
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
                        <h2 class="report-title mb-1">{{ config('app.name', "Maptech's Employee System") }}</h2>
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
                    <div class="report-label">Status Filter</div>
                    <div class="report-value">{{ $status === 'Leave' ? 'On Leave' : ($status ?: 'All Statuses') }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Total Records</div>
                        <div class="report-value">{{ $attendances->total() }}</div>
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
                    <div class="report-label">Overtime</div>
                    <div class="report-value">{{ $overtimeCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">On Leave</div>
                    <div class="report-value">{{ $leaveCount }}</div>
                </div>
                <div class="report-block">
                    <div class="report-label">Absent</div>
                    <div class="report-value">{{ $absentCount }}</div>
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

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-7 g-3 mb-4 no-print">
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Attendance Records</div>
                <h3 class="mb-0">{{ $totalCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Present</div>
                <h3 class="mb-0">{{ $presentCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Late</div>
                <h3 class="mb-0">{{ $lateCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Overtime</div>
                <h3 class="mb-0">{{ $overtimeCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">On Leave</div>
                <h3 class="mb-0 text-info">{{ $leaveCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Absent</div>
                <h3 class="mb-0 text-danger">{{ $absentCount }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Other</div>
                <h3 class="mb-0">{{ $otherCount }}</h3>
            </div>
        </div>
    </div>

    <section class="card mb-4 attendance-calendar" aria-labelledby="attendance-calendar-heading">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 id="attendance-calendar-heading" class="h5 mb-0">Daily Attendance Calendar</h2>
            <span class="text-muted small">Shows saved attendance only. Missing dates are not marked absent or added to the database.</span>
        </div>
        <div class="card-body">
            <div class="attendance-calendar-grid">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                    <div class="attendance-calendar-weekday">{{ $weekday }}</div>
                @endforeach

                @for($blankDay = 0; $blankDay < $calendarLeadingDays; $blankDay++)
                    <div class="attendance-calendar-day attendance-calendar-day-empty" aria-hidden="true"></div>
                @endfor

                @foreach($calendarDays as $calendarDay)
                    <article class="attendance-calendar-day {{ $calendarDay['total'] === 0 ? 'attendance-calendar-day-no-records' : '' }}"
                             aria-label="{{ $calendarDay['date']->format('l, F j, Y') }}: {{ $calendarDay['total'] }} saved attendance {{ \Illuminate\Support\Str::plural('record', $calendarDay['total']) }}">
                        <div class="attendance-calendar-date">
                            <span>{{ $calendarDay['date']->format('j') }}</span>
                            <small>{{ $calendarDay['date']->format('M') }}</small>
                        </div>
                        @if($calendarDay['total'] > 0)
                            <div class="attendance-calendar-total">{{ $calendarDay['total'] }} {{ \Illuminate\Support\Str::plural('record', $calendarDay['total']) }}</div>
                            <div class="attendance-calendar-statuses">
                                @foreach(['present' => 'Present', 'late' => 'Late', 'overtime' => 'OT', 'leave' => 'Leave', 'absent' => 'Absent', 'other' => 'Other'] as $statusKey => $statusLabel)
                                    @if($calendarDay[$statusKey] > 0)
                                        <span>{{ $statusLabel }}: {{ $calendarDay[$statusKey] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="attendance-calendar-no-records">No saved records</div>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

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
                        <th class="ot-review-col">OT Review</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr
                            class="{{ $attendance->status === 'Overtime' ? 'ot-row' : '' }}"
                            id="attendance-{{ $attendance->id }}"
                            data-attendance-id="{{ $attendance->id }}"
                        >
                            <td>
                                <strong class="employee-name">{{ $attendance->employee?->full_name ?? 'Unknown' }}</strong>
                                <small class="text-muted employee-id">{{ $attendance->employee?->employee_id ?? '-' }}</small>
                            </td>
                            <td>{{ $attendance->employee?->department?->name ?? '-' }}</td>
                            <td>{{ $attendance->attendance_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                {{ $attendance->check_in_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $attendance->check_in_time)->format('h:i:s A') : '-' }}
                            </td>
                            <td>
                                {{ $attendance->check_out_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $attendance->check_out_time)->format('h:i:s A') : '-' }}
                            </td>
                            <td class="status-cell">
                                @if(in_array($attendance->status, ['Absent', 'Leave'], true))
                                    <form method="POST" action="{{ route('admin.attendance.status') }}" class="absence-status-form">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $attendance->employee_id }}">
                                        <input type="hidden" name="attendance_date" value="{{ $attendance->attendance_date?->toDateString() }}">
                                        <input type="hidden" name="return_url" value="{{ url()->full() }}">
                                        <label class="visually-hidden" for="attendance-status-{{ $attendance->employee_id }}-{{ $attendance->attendance_date?->format('Ymd') }}">Update attendance status</label>
                                        <select id="attendance-status-{{ $attendance->employee_id }}-{{ $attendance->attendance_date?->format('Ymd') }}" name="status" class="form-select form-select-sm status-select {{ $attendance->status === 'Leave' ? 'status-select-leave' : 'status-select-absent' }}" aria-label="Change attendance status">
                                            <option value="Absent" @selected($attendance->status === 'Absent')>Absent</option>
                                            <option value="Leave" @selected($attendance->status === 'Leave')>On Leave</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary status-save-button d-none" aria-label="Save attendance status" title="Save attendance status">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="badge {{ $attendance->status === 'Present' ? 'bg-success' : ($attendance->status === 'Late' ? 'bg-warning text-dark' : ($attendance->status === 'Overtime' ? 'bg-overtime' : 'bg-secondary')) }}">
                                        {{ $attendance->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="ot-review-col">
                                @if($attendance->status === 'Overtime')
                                    @php
                                        $reviewStatus = $attendance->overtime_approval_status ?? 'Pending';
                                        $hasRejectedReason = $reviewStatus === 'Rejected' && filled($attendance->overtime_approval_reason);
                                        $reasonId = 'ot-reason-' . $attendance->id;
                                    @endphp

                                    <div class="small mb-2 ot-review-cell">
                                        @if($hasRejectedReason)
                                            <button
                                                type="button"
                                                class="badge bg-danger ot-reason-toggle"
                                                data-reason-target="{{ $reasonId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $reasonId }}">
                                                {{ $reviewStatus }}
                                            </button>
                                        @else
                                            <span class="badge {{ $reviewStatus === 'Approved' ? 'bg-success' : ($reviewStatus === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                {{ $reviewStatus }}
                                            </span>
                                        @endif

                                        @if($hasRejectedReason)
                                            <div id="{{ $reasonId }}" class="ot-reason mt-1 d-none">{{ $attendance->overtime_approval_reason }}</div>
                                        @endif
                                    </div>

                                    @if($reviewStatus === 'Pending')
                                        <form method="POST" action="{{ route('admin.attendance.overtime-review', $attendance) }}" class="ot-review-form">
                                            @csrf
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="HR reason" required>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <button type="submit" name="decision" value="approved" class="btn btn-success">Approve</button>
                                                <button type="submit" name="decision" value="rejected" class="btn btn-danger">Reject</button>
                                            </div>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $attendance->source ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No attendance records found for the selected filters.</td>
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
        grid-template-columns: minmax(130px, 1fr) minmax(130px, 1fr) minmax(180px, 1.4fr) minmax(115px, .9fr) auto;
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
        min-width: 60px;
        white-space: nowrap;
    }

    .hr-summary-card {
        min-height: 96px;
    }

    .attendance-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }

    .attendance-calendar-weekday {
        padding: 4px 8px;
        color: #64748b;
        font-size: .76rem;
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
    }

    .attendance-calendar-day {
        min-height: 112px;
        padding: 9px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
    }

    .attendance-calendar-day-empty {
        min-height: 0;
        padding: 0;
        border: 0;
        background: transparent;
    }

    .attendance-calendar-day-no-records {
        background: #f8fafc;
    }

    .attendance-calendar-date {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-weight: 700;
    }

    .attendance-calendar-date small {
        color: #64748b;
        font-size: .7rem;
        font-weight: 500;
    }

    .attendance-calendar-total {
        margin-top: 8px;
        font-size: .78rem;
        font-weight: 700;
    }

    .attendance-calendar-statuses {
        display: grid;
        gap: 2px;
        margin-top: 4px;
        color: #475569;
        font-size: .7rem;
        line-height: 1.3;
    }

    .attendance-calendar-no-records {
        margin-top: 12px;
        color: #64748b;
        font-size: .74rem;
        line-height: 1.3;
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
        transition: background-color .18s ease;
    }

    .hr-attendance-table tbody tr {
        height: 69px;
    }

    .hr-attendance-table td:last-child,
    .hr-attendance-table th:last-child {
        white-space: nowrap;
    }

    .hr-attendance-table .employee-name {
        display: block;
        white-space: nowrap;
    }

    .hr-attendance-table .employee-id {
        display: block;
    }

    .status-cell {
        min-width: 174px;
        text-align: center;
    }

    .status-cell > .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }

    .absence-status-form {
        display: inline-flex;
        align-items: center;
        gap: 0;
        margin: 0;
        max-width: 174px;
        vertical-align: middle;
        justify-content: center;
    }

    .absence-status-form .status-select {
        flex: 0 0 64px;
        min-width: 0;
        width: 64px;
        height: 21px;
        min-height: 21px;
        padding: .1rem .25rem;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        text-align-last: center;
        color: #fff;
        border: 0;
        border-radius: 6px;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        background-image: none;
    }

    .absence-status-form .status-select-leave {
        flex-basis: 78px;
        width: 78px;
    }

    .absence-status-form .status-select-absent {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .absence-status-form .status-select-leave {
        color: #1f2937;
        background-color: #0dcaf0;
        border-color: #0dcaf0;
    }

    .absence-status-form .status-select option {
        color: #1f2937;
        background: #fff;
        font-weight: 400;
    }

    .absence-status-form .status-select:focus-visible {
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .25);
        outline: 0;
    }

    .absence-status-form .status-save-button {
        flex: 0 0 auto;
        width: 21px;
        height: 21px;
        padding: 0;
        font-size: .64rem;
        line-height: 1;
        border-radius: 6px;
    }

    .hr-attendance-table tr.attendance-row-editing > td {
        background: rgba(245, 158, 11, 0.12);
    }

    .hr-attendance-table tr.attendance-row-saving > td {
        background: rgba(59, 130, 246, 0.12);
    }

    .ot-review-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin-bottom: 0 !important;
    }

    .ot-review-col {
        text-align: center;
        min-width: 200px;
        padding-left: .75rem !important;
        padding-right: .75rem !important;
    }

    .ot-review-col.is-editing {
        box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.45);
    }

    .ot-review-col.is-saving {
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.5);
    }

    .ot-reason-toggle {
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1.2;
        padding: .42em .76em;
        margin-inline: auto;
        text-align: center;
    }

    .ot-reason-toggle:focus-visible {
        outline: 2px solid rgba(59, 130, 246, 0.55);
        outline-offset: 2px;
    }

    .ot-review-cell .ot-reason {
        color: #64748b;
        font-size: .8rem;
        line-height: 1.35;
        max-width: 240px;
        word-break: break-word;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        text-align: left;
    }

    .ot-review-form {
        display: grid;
        gap: 8px;
        max-width: 220px;
        margin-inline: auto;
        margin-top: .35rem;
    }

    @media (max-width: 768px) {
        .attendance-calendar-grid {
            gap: 4px;
        }

        .attendance-calendar-day {
            min-height: 92px;
            padding: 5px;
        }

        .attendance-calendar-statuses {
            font-size: .62rem;
        }

        .status-cell {
            min-width: 160px;
        }

        .absence-status-form {
            max-width: 160px;
        }
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
        color: #d7e2f1;
    }

    body.dark-mode .report-block {
        border-color: #334155;
        background: #0f172a;
    }

    body.dark-mode .attendance-calendar-day {
        border-color: #334155;
        background: #0f172a;
    }

    body.dark-mode .attendance-calendar-day-no-records {
        background: #192238;
    }

    body.dark-mode .attendance-calendar-weekday,
    body.dark-mode .attendance-calendar-date small,
    body.dark-mode .attendance-calendar-statuses,
    body.dark-mode .attendance-calendar-no-records {
        color: #b7c6da;
    }

    body.dark-mode .report-label {
        color: #b7c6da;
    }

    body.dark-mode .report-value {
        color: #e2e8f0;
    }

    body.dark-mode .report-footer {
        border-top-color: #334155;
        color: #b6c5d9 !important;
    }

    body.dark-mode .attendance-header .text-muted,
    body.dark-mode .attendance-header .text-muted.small {
        color: #b4c3d7 !important;
    }

    body.dark-mode .ot-review-cell .ot-reason {
        color: #cbd5e1;
        background: #0f172a;
        border-color: #334155;
    }

    body.dark-mode .hr-attendance-table tr.attendance-row-editing > td {
        background: rgba(245, 158, 11, 0.16);
    }

    body.dark-mode .hr-attendance-table tr.attendance-row-saving > td {
        background: rgba(37, 99, 235, 0.22);
    }

    body.dark-mode .ot-review-col.is-editing {
        box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.55);
    }

    body.dark-mode .ot-review-col.is-saving {
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.6);
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

@section('scripts')
<script>
    document.querySelectorAll('.status-select').forEach((select) => {
        const form = select.closest('.absence-status-form');
        const saveButton = form?.querySelector('.status-save-button');
        const savedStatus = select.value;

        const syncStatusSaveButton = () => {
            saveButton?.classList.toggle('d-none', select.value === savedStatus);
        };

        select.addEventListener('change', () => {
            select.classList.toggle('status-select-leave', select.value === 'Leave');
            select.classList.toggle('status-select-absent', select.value === 'Absent');
            syncStatusSaveButton();
        });

        syncStatusSaveButton();
    });

    function syncReviewVisualState(form, options = {}) {
        const row = form.closest('tr');
        const reviewCell = form.closest('.ot-review-col');
        const reasonInput = form.querySelector('input[name="reason"]');

        if (!row || !reviewCell || !reasonInput) {
            return;
        }

        const focused = form.contains(document.activeElement);
        const dirty = reasonInput.value.trim().length > 0;
        const isSaving = options.isSaving === true;
        const isEditing = !isSaving && (focused || dirty);

        row.classList.toggle('attendance-row-editing', isEditing);
        reviewCell.classList.toggle('is-editing', isEditing);
        row.classList.toggle('attendance-row-saving', isSaving);
        reviewCell.classList.toggle('is-saving', isSaving);
    }

    document.addEventListener('focusin', function (event) {
        const form = event.target.closest('.ot-review-form');
        if (!form) {
            return;
        }

        syncReviewVisualState(form);
    });

    document.addEventListener('focusout', function (event) {
        const form = event.target.closest('.ot-review-form');
        if (!form) {
            return;
        }

        setTimeout(function () {
            syncReviewVisualState(form);
        }, 0);
    });

    document.addEventListener('input', function (event) {
        const form = event.target.closest('.ot-review-form');
        if (!form) {
            return;
        }

        syncReviewVisualState(form);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('.ot-review-form');
        if (!form) {
            return;
        }

        syncReviewVisualState(form, { isSaving: true });
    });

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('.ot-reason-toggle');
        if (!toggle) {
            return;
        }

        const targetId = toggle.getAttribute('data-reason-target');
        if (!targetId) {
            return;
        }

        const reason = document.getElementById(targetId);
        if (!reason) {
            return;
        }

        reason.classList.toggle('d-none');
        const isExpanded = !reason.classList.contains('d-none');
        toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });

    window.setInterval(function () {
        const activeElement = document.activeElement;
        const editingForm = activeElement instanceof HTMLElement && activeElement.closest('form');

        if (document.visibilityState === 'visible' && !editingForm) {
            window.location.reload();
        }
    }, 30000);
</script>
@endsection
