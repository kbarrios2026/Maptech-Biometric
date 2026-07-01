@extends('layouts.app')

@section('title', 'View Biometric Device')

@section('content')
<div class="content-wrapper">
<div class=\"row mb-4 align-items-center\">
        <div class=\"col-md-8\">
            <h1 class=\"h2 mb-0\"><i class=\"fas fa-fingerprint text-primary\"></i> {{ $biometricDevice->name }}</h1>
            <small class=\"text-muted d-block mt-1\">
                <i class=\"fas fa-barcode\"></i> Serial: <code>{{ $biometricDevice->serial_number ?? '-' }}</code> | 
                <i class=\"fas fa-network-wired\"></i> Address: <code>{{ $biometricDevice->ip_address }}:{{ $biometricDevice->port ?? 4370 }}</code>
            </small>
        </div>
        <div class=\"col-md-4 text-end\">
            <a href=\"{{ route('admin.biometric-devices.edit', $biometricDevice) }}\" class=\"btn btn-warning btn-sm\"><i class=\"fas fa-edit\"></i> Edit Device</a>
            <a href=\"{{ route('admin.biometric-devices.index') }}\" class=\"btn btn-outline-secondary btn-sm\"><i class=\"fas fa-arrow-left\"></i> Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">
        <i class=\"fas fa-check-circle\"></i> {{ session('success') }}
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
    </div>
    @endif

    <div class="row">
        <!-- Device Info -->
        <div class=\"col-lg-4 mb-4\">
            <div class=\"card h-100 border-0 shadow-sm\">
                <div class=\"card-header\" style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;\">
                    <h5 class=\"card-title mb-0\"><i class=\"fas fa-info-circle\"></i> Device Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-barcode"></i> Serial Number</small>
                        <code class="d-block bg-light p-2 rounded">{{ $biometricDevice->serial_number ?? '-' }}</code>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-network-wired"></i> IP Address</small>
                        <code class="d-block bg-light p-2 rounded">{{ $biometricDevice->ip_address ?? '-' }}</code>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-plug"></i> Port</small>
                        <code class="d-block bg-light p-2 rounded">{{ $biometricDevice->port }}</code>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-exchange-alt"></i> Sync Mode</small>
                        <span class="badge bg-{{ $biometricDevice->sync_mode === 'push' ? 'info' : 'warning' }} fs-6">
                            <i class="fas fa-{{ $biometricDevice->sync_mode === 'push' ? 'arrow-down' : 'arrow-up' }}"></i> 
                            {{ ucfirst($biometricDevice->sync_mode) }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-toggle-on"></i> Status</small>
                        <span class="badge bg-{{ $biometricDevice->is_active ? 'success' : 'danger' }} fs-6">
                            <i class="fas fa-{{ $biometricDevice->is_active ? 'check-circle' : 'times-circle' }}"></i> 
                            {{ $biometricDevice->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><i class="fas fa-history"></i> Last Synced</small>
                        <div class="bg-light p-2 rounded small">
                            {{ $biometricDevice->last_synced_at ? $biometricDevice->last_synced_at->diffForHumans() : 'Never' }}
                        </div>
                    </div>

                    @if($biometricDevice->notes)
                    <div>
                        <small class="text-muted d-block mb-1"><i class="fas fa-sticky-note"></i> Notes</small>
                        <div class="bg-light p-2 rounded small">{{ $biometricDevice->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions Panel -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class=\"card-header\" style=\"background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;\">
                    <h5 class=\"card-title mb-0\"><i class=\"fas fa-sliders-h\"></i> Device Actions</h5>
                </div>
                <div class="card-body">
                    <!-- Test Connection -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary w-100" id="testConnectionBtn" onclick="testConnection()">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                        <div id="connectionResult" class="mt-2"></div>
                    </div>

                    @if($biometricDevice->sync_mode === 'pull')
                    <!-- Sync Attendance (Pull Mode) -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-success w-100" id="syncBtn" onclick="syncAttendance()">
                            <i class="fas fa-sync"></i> Sync Attendance Now
                        </button>
                        <div id="syncResult" class="mt-2"></div>
                    </div>
                    @endif

                    @if($biometricDevice->sync_mode === 'push')
                    <!-- Enable Push Mode -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-info w-100" id="enablePushBtn" onclick="enablePush()">
                            <i class="fas fa-broadcast-tower"></i> Enable Push Mode
                        </button>
                        <div id="pushResult" class="mt-2"></div>
                    </div>
                    @endif

                    <!-- Webhook URL (Push Mode) -->
                    @if($biometricDevice->sync_mode === 'push')
                    <hr>
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2"><i class="fas fa-link"></i> Webhook URL</label>
                        <p class="text-muted small mb-2">Configure this URL in your ZKTeco device's push settings. Use the server IP shown below, not localhost.</p>
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control font-monospace" id="webhookUrl" value="{{ $webhookUrl }}" readonly style="font-size: 0.9rem; background-color: #f8f9fa;">
                            <button class="btn btn-outline-primary" type="button" onclick="copyWebhookUrl()" title="Copy to clipboard">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="card card-sm border-info">
                        <div class="card-header bg-info bg-opacity-10 border-info">
                            <h6 class="card-title mb-0"><i class="fas fa-cog text-info"></i> Recommended Device Connection Settings</h6>
                        </div>
                        <div class="card-body small">
                            <div class="row mb-2">
                                <div class="col-6">
                                    <strong class="d-block text-muted">Server IP / Host</strong>
                                    <code class="text-dark">{{ $connectionGuide['server_ip'] ?? '-' }}</code>
                                </div>
                                <div class="col-6">
                                    <strong class="d-block text-muted">Server Port</strong>
                                    <code class="text-dark">{{ $connectionGuide['server_port'] ?? '-' }}</code>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <strong class="d-block text-muted">Device IP</strong>
                                    <code class="text-dark">{{ $connectionGuide['device_ip'] ?? '-' }}</code>
                                </div>
                                <div class="col-6">
                                    <strong class="d-block text-muted">Device Port</strong>
                                    <code class="text-dark">{{ $connectionGuide['device_port'] ?? '-' }}</code>
                                </div>
                            </div>

                            <div class="alert alert-light border border-info mt-3 mb-0 small">
                                <div class="mb-2">
                                    <strong>Short webhook:</strong>
                                    <code class="d-block bg-white p-2 rounded mt-1 text-break">{{ $connectionGuide['recommended_url'] ?? $webhookUrl }}</code>
                                </div>
                                <div class="mb-2">
                                    <strong>Full webhook:</strong>
                                    <code class="d-block bg-white p-2 rounded mt-1 text-break" style="font-size: 0.75rem;">{{ $connectionGuide['full_url'] ?? $webhookUrl }}</code>
                                </div>
                                <div>
                                    <strong>Short token:</strong>
                                    <code class="d-block bg-white p-2 rounded mt-1 text-break">{{ $connectionGuide['short_token'] ?? '-' }}</code>
                                </div>
                                @if(!empty($connectionGuide['recommended_notes']))
                                <div class="mt-3">
                                    <strong class="d-block mb-2">Configuration Tips:</strong>
                                    <ul class="mb-0 ps-3">
                                        @foreach($connectionGuide['recommended_notes'] as $note)
                                        <li class="text-secondary">{{ $note }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Feed -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-list"></i> Recent Attendance Records</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshAttendance()" title="Manually refresh attendance data">
                        <i class="fas fa-redo"></i> Refresh
                    </button>
                    <span class="badge bg-success ms-2" id="autoRefreshBadge" title="Automatically refreshes every 15 seconds">
                        <i class="fas fa-check-circle"></i> Auto: ON
                    </span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="attendanceTable">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-user"></i> Employee</th>
                        <th><i class="fas fa-id-badge"></i> Biometric ID</th>
                        <th><i class="fas fa-calendar"></i> Date</th>
                        <th><i class="fas fa-sign-in-alt"></i> Check In</th>
                        <th><i class="fas fa-sign-out-alt"></i> Check Out</th>
                        <th><i class="fas fa-tag"></i> Status</th>
                        <th><i class="fas fa-database"></i> Source</th>
                        <th><i class="fas fa-clock"></i> Recorded</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm mb-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>Loading attendance records...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-light text-muted small py-2">
            <i class="fas fa-info-circle"></i> Records are automatically refreshed every 15 seconds. Pull mode syncs on demand; push mode displays real-time data from device.
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
