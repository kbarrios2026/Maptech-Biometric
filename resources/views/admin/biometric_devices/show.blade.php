@extends('layouts.app')

@section('title', 'View Biometric Device')

@section('content')
<div class="content-wrapper">
    <!-- Header with Device Status -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="h3 mb-1"><i class="fas fa-fingerprint"></i> {{ $biometricDevice->name }}</h1>
                    <div class="small opacity-75">
                        <i class="fas fa-barcode"></i> <code style="background: rgba(255,255,255,0.15); padding: 2px 6px; border-radius: 4px;">{{ $biometricDevice->serial_number ?? '-' }}</code>
                        <span class="mx-2">•</span>
                        <i class="fas fa-network-wired"></i> <code style="background: rgba(255,255,255,0.15); padding: 2px 6px; border-radius: 4px;">{{ $biometricDevice->ip_address }}:{{ $biometricDevice->port ?? 4370 }}</code>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div class="mb-2">
                        <span class="badge bg-light text-dark me-2">
                            <i class="fas fa-{{ $biometricDevice->sync_mode === 'push' ? 'arrow-down' : 'arrow-up' }}"></i> 
                            {{ ucfirst($biometricDevice->sync_mode) }} Mode
                        </span>
                        <span class="badge bg-{{ $biometricDevice->is_active ? 'success' : 'danger' }}">
                            <i class="fas fa-{{ $biometricDevice->is_active ? 'check-circle' : 'times-circle' }}"></i> 
                            {{ $biometricDevice->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="text-nowrap">
                        <a href="{{ route('admin.biometric-devices.edit', $biometricDevice) }}" class="btn btn-light btn-sm"><i class="fas fa-edit"></i> Edit</a>
                        <a href="{{ route('admin.biometric-devices.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="row g-4 mb-4">
        <!-- Device Details (Left) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle text-primary"></i> Device Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold mb-1">Serial Number</small>
                            <code class="bg-light p-2 rounded d-block text-break">{{ $biometricDevice->serial_number ?? '-' }}</code>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold mb-1">Port</small>
                            <code class="bg-light p-2 rounded d-block">{{ $biometricDevice->port }}</code>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold mb-1">IP Address</small>
                            <code class="bg-light p-2 rounded d-block text-break">{{ $biometricDevice->ip_address ?? '-' }}</code>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold mb-1">Last Synced</small>
                            <div class="bg-light p-2 rounded small">
                                {{ $biometricDevice->last_synced_at ? $biometricDevice->last_synced_at->diffForHumans() : 'Never' }}
                            </div>
                        </div>
                    </div>

                    @if($biometricDevice->notes)
                    <div>
                        <small class="text-muted d-block fw-bold mb-1">Notes</small>
                        <div class="bg-light p-2 rounded small text-break">{{ $biometricDevice->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Device Actions (Right) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-sliders-h text-danger"></i> Device Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($biometricDevice->sync_mode === 'push')
                        <!-- Push Mode: Configuration Instructions -->
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading mb-2"><i class="fas fa-broadcast-tower"></i> Push Mode Active</h6>
                                <p class="small mb-2">Your device is configured to <strong>send data to your server</strong>. No connection test needed.</p>
                                <p class="small mb-0"><strong>Next Step:</strong> Follow the setup instructions below to configure the webhook URL in your device's web interface.</p>
                            </div>
                        </div>
                        @else
                        <!-- Pull Mode: Test Connection -->
                        <div class="col-12">
                            <button type="button" class="btn btn-primary w-100 py-2" id="testConnectionBtn" onclick="testConnection()">
                                <i class="fas fa-plug"></i> Test Connection
                            </button>
                            <div id="connectionResult" class="mt-2"></div>
                        </div>

                        <!-- Sync Attendance (Pull Mode) -->
                        <div class="col-12">
                            <button type="button" class="btn btn-success w-100 py-2" id="syncBtn" onclick="syncAttendance()">
                                <i class="fas fa-sync"></i> Sync Attendance Now
                            </button>
                            <div id="syncResult" class="mt-2"></div>
                        </div>

                        <!-- Webhook URL (Pull Mode) -->
                        <div class="col-12">
                            <hr class="my-2">
                            <label class="form-label fw-bold small mb-2"><i class="fas fa-link"></i> Webhook URL</label>
                            <p class="text-muted small mb-2">This webhook URL is available if you switch to push mode:</p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" id="webhookUrl" value="{{ $webhookUrl }}" readonly style="background-color: #f8f9fa; font-size: 0.75rem;">
                                <button class="btn btn-outline-primary btn-sm" type="button" onclick="copyWebhookUrl()" title="Copy to clipboard">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Guide (Full Width) -->
    @if($biometricDevice->sync_mode === 'push')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-bottom py-3">
            <h5 class="card-title mb-0"><i class="fas fa-list-check text-info"></i> Setup Instructions</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-4">
                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Action Required</h6>
                <p class="mb-2">Your device is in <strong>push mode</strong>. You must configure it to send data to your server.</p>
                <p class="mb-0"><strong>Follow these steps:</strong></p>
            </div>

            <div class="row g-4">
                <!-- Step 1 -->
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">1</div>
                        <div class="grow">
                            <h6 class="fw-bold mb-2">Access Device Web Interface</h6>
                            <p class="text-muted small mb-2">Open your browser and navigate to:</p>
                            <code class="bg-light p-2 d-block rounded text-break">http://{{ $biometricDevice->ip_address }}:8000</code>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">2</div>
                        <div class="grow">
                            <h6 class="fw-bold mb-2">Log In</h6>
                            <p class="text-muted small mb-0">Use default credentials (usually admin/admin or admin/123456)</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">3</div>
                        <div class="grow">
                            <h6 class="fw-bold mb-2">Configure Server Settings</h6>
                            <p class="text-muted small mb-2">Navigate to: <strong>Settings → Network → Server</strong></p>
                            <p class="text-muted small mb-2">Set the following:</p>
                            <div class="bg-light p-3 rounded small text-muted">
                                <div class="mb-2"><i class="fas fa-network-wired text-primary"></i> <strong>Server IP:</strong> <code>{{ $connectionGuide['server_ip'] ?? '-' }}</code></div>
                                <div><i class="fas fa-plug text-primary"></i> <strong>Server Port:</strong> <code>{{ $connectionGuide['server_port'] ?? '-' }}</code></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">4</div>
                        <div class="grow">
                            <h6 class="fw-bold mb-2">Set Webhook URL</h6>
                            <p class="text-muted small mb-2">Navigate to: <strong>Settings → Server → Push Webhook</strong></p>
                            <p class="text-muted small mb-2">Paste this webhook URL:</p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" id="webhookUrlSetup" value="{{ $webhookUrl }}" readonly style="background-color: #f8f9fa; font-size: 0.75rem;">
                                <button class="btn btn-outline-primary btn-sm" type="button" onclick="copyWebhookUrl()" title="Copy to clipboard">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-4 mb-0">
                <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> After configuring, scan a fingerprint on the device to test. The attendance record should appear in this system within seconds.
            </div>
        </div>
    </div>
    @else
    <!-- Pull Mode Configuration Guide -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-bottom py-3">
            <h5 class="card-title mb-0"><i class="fas fa-cog text-info"></i> Configuration Guide (Pull Mode)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3 text-muted">Server Settings</h6>
                    <div class="row small">
                        <div class="col-6 mb-3">
                            <span class="text-muted d-block mb-1">IP Address</span>
                            <code class="bg-light p-2 rounded d-block">{{ $connectionGuide['server_ip'] ?? '-' }}</code>
                        </div>
                        <div class="col-6 mb-3">
                            <span class="text-muted d-block mb-1">Port</span>
                            <code class="bg-light p-2 rounded d-block">{{ $connectionGuide['server_port'] ?? '-' }}</code>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3 text-muted">Device Settings</h6>
                    <div class="row small">
                        <div class="col-6 mb-3">
                            <span class="text-muted d-block mb-1">IP Address</span>
                            <code class="bg-light p-2 rounded d-block">{{ $connectionGuide['device_ip'] ?? '-' }}</code>
                        </div>
                        <div class="col-6 mb-3">
                            <span class="text-muted d-block mb-1">Port</span>
                            <code class="bg-light p-2 rounded d-block">{{ $connectionGuide['device_port'] ?? '-' }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

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
@endsection

@section('scripts')
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
@endsection
