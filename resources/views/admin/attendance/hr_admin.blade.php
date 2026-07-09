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
                    <div class="report-label">Overtime</div>
                    <div class="report-value">{{ $overtimeCount }}</div>
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

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4 no-print">
        <div class="col">
            <div class="card p-3 h-100 hr-summary-card">
                <div class="text-muted">Attended Today</div>
                <h3 class="mb-0">{{ $attendances->count() }}</h3>
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
                        <th class="ot-review-col">OT Review</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr class="{{ $attendance->status === 'Overtime' ? 'ot-row' : '' }}">
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
                                <span class="badge {{ $attendance->status === 'Present' ? 'bg-success' : ($attendance->status === 'Late' ? 'bg-warning text-dark' : ($attendance->status === 'Overtime' ? 'bg-overtime' : 'bg-secondary')) }}">
                                    {{ $attendance->status }}
                                </span>
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
        transition: background-color .18s ease;
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
</script>
@endsection
