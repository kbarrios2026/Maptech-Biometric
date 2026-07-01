@extends('layouts.app')

@section('title', 'Edit Biometric Device')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Edit {{ $biometricDevice->name }}</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.biometric-devices.update', $biometricDevice) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $biometricDevice->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $biometricDevice->serial_number) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $biometricDevice->ip_address) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Port</label>
                    <input type="number" name="port" class="form-control" value="{{ old('port', $biometricDevice->port) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Communication Key</label>
                    <input type="text" name="comm_key" class="form-control" value="{{ old('comm_key', $biometricDevice->comm_key) }}" placeholder="Default: 0">
                    <small class="text-muted">ZKTeco communication key (usually 0 if not set on device)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sync Mode</label>
                    <select name="sync_mode" class="form-control">
                        <option value="push" {{ old('sync_mode', $biometricDevice->sync_mode) == 'push' ? 'selected' : '' }}>Push (device sends data to server)</option>
                        <option value="pull" {{ old('sync_mode', $biometricDevice->sync_mode) == 'pull' ? 'selected' : '' }}>Pull (server fetches data from device)</option>
                    </select>
                    <small class="text-muted">
                        <strong>Push:</strong> Device sends attendance in real-time via webhook.<br>
                        <strong>Pull:</strong> Server connects to device and downloads logs.
                    </small>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $biometricDevice->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes', $biometricDevice->notes) }}</textarea>
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.biometric-devices.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
