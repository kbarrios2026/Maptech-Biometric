<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if(($scope ?? 'single') === 'all')
            Attendance Receipt - All Employees
        @else
            Attendance Receipt - {{ $employee?->full_name ?? 'Employee' }}
        @endif
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            background: #eef2f7;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page {
            max-width: 1120px;
            margin: 18px auto;
            background: #fff;
            border: 2px solid #c9ced6;
            border-radius: 10px;
            padding: 18px 18px 22px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .receipt-toolbar {
            max-width: 1120px;
            margin: 18px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            align-items: center;
            padding: 0 2px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 14px;
        }

        .logo {
            width: 86px;
            height: 86px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            font-weight: 700;
            letter-spacing: .12em;
            color: #64748b;
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            flex: 0 0 auto;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .kicker {
            font-size: .78rem;
            letter-spacing: .14em;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
        }

        .title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin: 2px 0 4px;
        }

        .subtitle {
            color: #6b7280;
            font-size: .95rem;
        }

        .meta {
            font-size: .95rem;
            line-height: 1.6;
            color: #374151;
            text-align: right;
        }

        .section-bar {
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, #4b5563, #6b7280);
            margin: 10px 0 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border: 1px solid #cfd6de;
            border-bottom: none;
        }

        .cell {
            min-height: 72px;
            padding: 12px 14px;
            border-right: 1px solid #cfd6de;
            border-bottom: 1px solid #cfd6de;
            background: #fff;
        }

        .cell:nth-child(3n) {
            border-right: none;
        }

        .label {
            font-size: .72rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .value {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }

        .section-header {
            background: #5f6368;
            color: #fff;
            padding: 8px 12px;
            font-size: .92rem;
            font-weight: 700;
            border-radius: 4px;
            margin: 2px 0 8px;
        }

        .report-table thead th {
            background: #4b5563;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: .78rem;
        }

        .report-table td,
        .report-table th {
            vertical-align: middle;
            font-size: .92rem;
        }

        .signoff {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            margin-top: 28px;
        }

        .sign-line {
            border-bottom: 1px solid #111827;
            height: 26px;
        }

        .sign-label {
            margin-top: 6px;
            font-size: .82rem;
            color: #374151;
            font-weight: 600;
        }

        .footer {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: .9rem;
            color: #6b7280;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            body {
                background: #fff;
            }

            .page {
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }

            .no-print {
                display: none !important;
            }

            .section-bar,
            .section-header,
            .report-table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-toolbar no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="{{ route('admin.attendance.index', ['date' => $dateFrom ?? $date, 'employee_id' => $employee?->id]) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="page">
        <div class="header">
            <div class="header-left">
                <div class="logo">LOGO</div>
                <div>
                    <div class="title">Maptech Date Time Report</div>
                    <div class="subtitle">
                        @if(($scope ?? 'single') === 'all')
                            All Employees
                        @else
                            {{ $employee?->full_name ?? 'Unknown' }} ({{ $employee?->employee_id ?? '-' }})
                        @endif
                    </div>
                </div>
            </div>
            <div class="meta">
                <div><strong>Printed:</strong> {{ now()->format('M d, Y h:i A') }}</div>
                <div>
                    <strong>Report Range:</strong>
                    @if(($dateFrom ?? null) && ($dateTo ?? null) && $dateFrom !== $dateTo)
                        {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}
                    @else
                        {{ \Illuminate\Support\Carbon::parse($dateFrom ?? $date)->format('M d, Y') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="section-bar"></div>

        <div class="grid mb-4">
            @if(($scope ?? 'single') === 'all')
                <div class="cell">
                    <div class="label">Report Scope</div>
                    <div class="value">All Employees</div>
                </div>
                <div class="cell">
                    <div class="label">Report Date</div>
                    <div class="value">
                        @if(($dateFrom ?? null) && ($dateTo ?? null) && $dateFrom !== $dateTo)
                            {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}
                        @else
                            {{ \Illuminate\Support\Carbon::parse($dateFrom ?? $date)->format('M d, Y') }}
                        @endif
                    </div>
                </div>
                <div class="cell">
                    <div class="label">Employees Included</div>
                    <div class="value">{{ $totalEmployees ?? 0 }}</div>
                </div>
                <div class="cell">
                    <div class="label">Attendance Rows</div>
                    <div class="value">{{ $attendances->count() }}</div>
                </div>
                <div class="cell">
                    <div class="label">Source</div>
                    <div class="value">Biometric / Manual</div>
                </div>
                <div class="cell">
                    <div class="label">Generated At</div>
                    <div class="value">{{ now()->format('M d, Y h:i A') }}</div>
                </div>
            @else
                <div class="cell">
                    <div class="label">Employee Name</div>
                    <div class="value">{{ $employee?->full_name ?? 'Unknown' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Employee ID</div>
                    <div class="value">{{ $employee?->employee_id ?? '-' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Department</div>
                    <div class="value">{{ $employee?->department?->name ?? '-' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Position</div>
                    <div class="value">{{ $employee?->position?->name ?? '-' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Status</div>
                    <div class="value">{{ $employee?->status?->name ?? '-' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Report Date</div>
                    <div class="value">
                        @if(($dateFrom ?? null) && ($dateTo ?? null) && $dateFrom !== $dateTo)
                            {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') }}
                        @else
                            {{ \Illuminate\Support\Carbon::parse($dateFrom ?? $date)->format('M d, Y') }}
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="section-header">Attendance Detail</div>
        <div class="table-responsive">
            <table class="table table-bordered report-table mb-0">
                <thead>
                    <tr>
                        @if(($scope ?? 'single') === 'all')
                            <th style="width: 24%">Employee</th>
                            <th style="width: 12%">Employee ID</th>
                        @endif
                        <th style="width: 22%">Date</th>
                        <th style="width: 14%">Check In</th>
                        <th style="width: 14%">Check Out</th>
                        <th style="width: 14%">Status</th>
                        <th style="width: 14%">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            @if(($scope ?? 'single') === 'all')
                                <td>{{ $attendance->employee?->full_name ?? 'Unknown' }}</td>
                                <td>{{ $attendance->employee?->employee_id ?? '-' }}</td>
                            @endif
                            <td>{{ $attendance->attendance_date?->format('M d, Y') ?? $date }}</td>
                            <td>{{ $attendance->check_in_time ?? '-' }}</td>
                            <td>{{ $attendance->check_out_time ?? '-' }}</td>
                            <td>{{ $attendance->status }}</td>
                            <td>{{ $attendance->source ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (($scope ?? 'single') === 'all') ? 7 : 5 }}" class="text-center text-muted py-4">
                                @if(($scope ?? 'single') === 'all')
                                    No attendance found for all employees in the selected date range.
                                @else
                                    No attendance found for this employee in the selected date range.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="signoff">
            <div>
                <div class="sign-line"></div>
                <div class="sign-label">Prepared By</div>
            </div>
            <div>
                <div class="sign-line"></div>
                <div class="sign-label">Verified By</div>
            </div>
            <div>
                <div class="sign-line"></div>
                <div class="sign-label">Employee Acknowledgment</div>
            </div>
        </div>

        <div class="footer">Employee Management System - Date & Time Report</div>
    </div>

</body>
</html>
