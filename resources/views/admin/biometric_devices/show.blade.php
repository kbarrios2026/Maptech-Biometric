@extends('layouts.app')

@section('title', 'View Biometric Device')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">{{ $biometricDevice->name }}</h1>
            <div>
                <a href="{{ route('admin.biometric-devices.edit', $biometricDevice) }}" class="btn btn-secondary">Edit</a>
                <a href="{{ route('admin.biometric-devices.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <!-- Device Info -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Device Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Serial Number</dt>
                        <dd class="col-sm-8">{{ $biometricDevice->serial_number ?? '-' }}</dd>

                        <dt class="col-sm-4">IP Address</dt>
                        <dd class="col-sm-8">{{ $biometricDevice->ip_address ?? '-' }}</dd>

                        <dt class="col-sm-4">Port</dt>
                        <dd class="col-sm-8">{{ $biometricDevice->port }}</dd>

                        <dt class="col-sm-4">Sync Mode</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $biometricDevice->sync_mode === 'push' ? 'info' : 'warning' }}">
                                {{ ucfirst($biometricDevice->sync_mode) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Active</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $biometricDevice->is_active ? 'success' : 'danger' }}">
                                {{ $biometricDevice->is_active ? 'Yes' : 'No' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Last Synced</dt>
                        <dd class="col-sm-8">
                            {{ $biometricDevice->last_synced_at ? $biometricDevice->last_synced_at->diffForHumans() : 'Never' }}
                        </dd>

                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $biometricDevice->notes ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Actions Panel -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Device Actions</h5>
                </div>
                <div class="card-body">
                    <!-- Test Connection -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary w-100" id="testConnectionBtn" onclick="testConnection()">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                        <div id="connectionResult" class="mt-2"></div>
                    </div>

                    @if($biometricDevice->sync_mode === 'pull')
                    <!-- Sync Attendance (Pull Mode) -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success w-100" id="syncBtn" onclick="syncAttendance()">
                            <i class="fas fa-sync"></i> Sync Attendance Now
                        </button>
                        <div id="syncResult" class="mt-2"></div>
                    </div>
                    @endif

                    @if($biometricDevice->sync_mode === 'push')
                    <!-- Enable Push Mode -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-info w-100" id="enablePushBtn" onclick="enablePush()">
                            <i class="fas fa-broadcast-tower"></i> Enable Push Mode
                        </button>
                        <div id="pushResult" class="mt-2"></div>
                    </div>
                    @endif

                    <!-- Webhook URL (Push Mode) -->
                    @if($biometricDevice->sync_mode === 'push')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Webhook URL (for device push)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="webhookUrl" value="{{ $webhookUrl }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">Copy</button>
                        </div>
                        <small class="text-muted">
                            Configure this URL in your ZKTeco device's push settings. Use the server IP below, not localhost.
                        </small>
                    </div>

                    <div class="mb-3 p-3 border rounded bg-light">
                        <h6 class="fw-bold mb-3">Recommended Device Connection</h6>
                        <dl class="row mb-0 small">
                            <dt class="col-sm-5">Server IP / Host</dt>
                            <dd class="col-sm-7">{{ $connectionGuide['server_ip'] ?? '-' }}</dd>

                            <dt class="col-sm-5">Server Port</dt>
                            <dd class="col-sm-7">{{ $connectionGuide['server_port'] ?? '-' }}</dd>

                            <dt class="col-sm-5">Device IP</dt>
                            <dd class="col-sm-7">{{ $connectionGuide['device_ip'] ?? '-' }}</dd>

                            <dt class="col-sm-5">Device Port</dt>
                            <dd class="col-sm-7">{{ $connectionGuide['device_port'] ?? '-' }}</dd>
                        </dl>

                        <div class="alert alert-info py-2 mt-3 mb-0 small">
                            <div><strong>Recommended webhook (short):</strong> {{ $connectionGuide['recommended_url'] ?? $webhookUrl }}</div>
                            <div class="mt-1"><strong>Full webhook:</strong> {{ $connectionGuide['full_url'] ?? $webhookUrl }}</div>
                            <div class="mt-1"><strong>Short token:</strong> {{ $connectionGuide['short_token'] ?? '-' }}</div>
                            <ul class="mb-0 mt-2 ps-3">
                                @foreach(($connectionGuide['recommended_notes'] ?? []) as $note)
                                <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Feed -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Attendance Records</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshAttendance()">
                    <i class="fas fa-redo"></i> Refresh
                </button>
                <span class="badge bg-secondary ms-2" id="autoRefreshBadge">Auto: ON</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="attendanceTable">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Biometric ID</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    <tr>
                        <td colspan="8" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let autoRefreshInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        refreshAttendance();
        // Auto-refresh every 15 seconds for live feed
        autoRefreshInterval = setInterval(refreshAttendance, 15000);
    });

    /**
     * Test connection to the biometric device.
     */
    function testConnection() {
        const btn = document.getElementById('testConnectionBtn');
        const result = document.getElementById('connectionResult');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
        result.innerHTML = '';

        fetch('{{ route("admin.biometric-devices.test-connection", $biometricDevice) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = `
                    <div class="alert alert-success py-2 mb-0">
                        <strong>Connected!</strong><br>
                        Serial: ${data.data.serial || 'N/A'}<br>
                        Version: ${data.data.version || 'N/A'}<br>
                        Device Time: ${data.data.device_time || 'N/A'}<br>
                        Records on Device: ${data.data.attendance_count ?? 'N/A'}
                    </div>`;
            } else {
                result.innerHTML = `<div class="alert alert-danger py-2 mb-0">${data.message}</div>`;
            }
        })
        .catch(err => {
            result.innerHTML = `<div class="alert alert-danger py-2 mb-0">Connection error: ${err.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
        });
    }

    /**
     * Sync attendance from device (pull mode).
     */
    function syncAttendance() {
        const btn = document.getElementById('syncBtn');
        const result = document.getElementById('syncResult');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Syncing...';
        result.innerHTML = '';

        fetch('{{ route("admin.biometric-devices.sync", $biometricDevice) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = `<div class="alert alert-success py-2 mb-0">${data.message}</div>`;
                refreshAttendance();
            } else {
                result.innerHTML = `<div class="alert alert-warning py-2 mb-0">${data.message}</div>`;
            }
        })
        .catch(err => {
            result.innerHTML = `<div class="alert alert-danger py-2 mb-0">Sync error: ${err.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Sync Attendance Now';
        });
    }

    /**
     * Enable push mode on the device.
     */
    function enablePush() {
        const btn = document.getElementById('enablePushBtn');
        const result = document.getElementById('pushResult');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enabling...';
        result.innerHTML = '';

        fetch('{{ route("admin.biometric-devices.enable-push", $biometricDevice) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            result.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} py-2 mb-0">${data.message}</div>`;
        })
        .catch(err => {
            result.innerHTML = `<div class="alert alert-danger py-2 mb-0">Error: ${err.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broadcast-tower"></i> Enable Push Mode';
        });
    }

    /**
     * Refresh the attendance feed.
     */
    function refreshAttendance() {
        const tbody = document.getElementById('attendanceBody');

        fetch('{{ route("admin.biometric-devices.recent-attendance", $biometricDevice) }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.attendances || data.attendances.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No attendance records yet.</td></tr>';
                return;
            }

            tbody.innerHTML = data.attendances.map(a => `
                <tr>
                    <td>${a.employee}</td>
                    <td>${a.employee_id}</td>
                    <td>${a.date}</td>
                    <td>${a.check_in || '-'}</td>
                    <td>${a.check_out || '-'}</td>
                    <td><span class="badge bg-${a.status === 'Present' ? 'success' : a.status === 'Late' ? 'warning' : a.status === 'Absent' ? 'danger' : 'secondary'}">${a.status}</span></td>
                    <td>${a.source || '-'}</td>
                    <td>${a.created_at}</td>
                </tr>
            `).join('');
        })
        .catch(() => {
            // Silently fail on auto-refresh
        });
    }

    /**
     * Copy webhook URL to clipboard.
     */
    function copyWebhookUrl() {
        const input = document.getElementById('webhookUrl');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = input.nextElementSibling;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => { btn.textContent = originalText; }, 2000);
        });
    }
</script>
@endpush
@endsection
