@extends('layouts.app')

@section('title', "Edit Biometric Device")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3"><i class="fas fa-edit"></i> Edit Device: <strong>{{ $biometricDevice->name }}</strong></h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.biometric-devices.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Please fix the errors below</h5>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-fingerprint"></i> Device Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.biometric-devices.update', $biometricDevice) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold">Device Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $biometricDevice->name) }}" placeholder="e.g., Main Entrance" required>
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Serial Number</label>
                                    <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $biometricDevice->serial_number) }}" placeholder="BRWU232160143">
                                    @error('serial_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sync Mode <span class="text-danger">*</span></label>
                                    <select name="sync_mode" class="form-select @error('sync_mode') is-invalid @enderror">
                                        <option value="push" {{ old('sync_mode', $biometricDevice->sync_mode) == 'push' ? 'selected' : '' }}>Push (Real-time)</option>
                                        <option value="pull" {{ old('sync_mode', $biometricDevice->sync_mode) == 'pull' ? 'selected' : '' }}>Pull (On-demand)</option>
                                    </select>
                                    @error('sync_mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">IP Address</label>
                                    <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror" value="{{ old('ip_address', $biometricDevice->ip_address) }}" placeholder="192.168.1.100">
                                    @error('ip_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Port</label>
                                    <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', $biometricDevice->port) }}" min="1" max="65535">
                                    @error('port')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Communication Key</label>
                            <input type="text" name="comm_key" class="form-control @error('comm_key') is-invalid @enderror" value="{{ old('comm_key', $biometricDevice->comm_key) }}" placeholder="0">
                            <small class="text-muted d-block mt-1">ZKTeco device communication key (usually 0 if not configured)</small>
                            @error('comm_key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="e.g., Located in lobby, installed 2024">{{ old('notes', $biometricDevice->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $biometricDevice->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active"><strong>Active</strong></label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Device
                            </button>
                            <a href="{{ route('admin.biometric-devices.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-info"></i> Current Status</h6>
                    <div class="mb-3">
                        <small class="text-muted d-block">Last Synced</small>
                        <strong>{{ $biometricDevice->last_synced_at ? $biometricDevice->last_synced_at->diffForHumans() : 'Never' }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Created</small>
                        <strong>{{ $biometricDevice->created_at->format('M d, Y') }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Device Status</small>
                        <span class="badge {{ $biometricDevice->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $biometricDevice->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-2"><i class="fas fa-link text-warning"></i> Device Address</h6>
                    <p class="mb-0 text-muted small">{{ $biometricDevice->ip_address }}:{{ $biometricDevice->port ?? 4370 }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
