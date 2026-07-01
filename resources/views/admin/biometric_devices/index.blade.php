@extends('layouts.app')

@section('title', 'Biometric Devices')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-fingerprint"></i> Biometric Devices</h1>
            <a href="{{ route('admin.biometric-devices.create') }}" class="btn btn-primary">Add Device</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Serial</th>
                        <th>IP</th>
                        <th>Port</th>
                        <th>Sync Mode</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                    <tr>
                        <td>{{ $device->name }}</td>
                        <td>{{ $device->serial_number ?? '-' }}</td>
                        <td>{{ $device->ip_address ?? '-' }}</td>
                        <td>{{ $device->port }}</td>
                        <td>{{ ucfirst($device->sync_mode) }}</td>
                        <td>{{ $device->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.biometric-devices.show', $device) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('admin.biometric-devices.edit', $device) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('admin.biometric-devices.destroy', $device) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete device?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No devices found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $devices->links() }}</div>
    </div>
</div>
@endsection
