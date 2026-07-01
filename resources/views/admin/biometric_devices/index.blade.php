@extends('layouts.app')

@section('title', 'Biometric Devices')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3"><i class="fas fa-fingerprint"></i> Biometric Devices</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.biometric-devices.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Device</a></div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Serial Number</th>
                        <th>IP Address</th>
                        <th>Port</th>
                        <th>Sync Mode</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                    <tr>
                        <td><strong>{{ $device->name }}</strong></td>
                        <td><code>{{ $device->serial_number ?? '-' }}</code></td>
                        <td>{{ $device->ip_address ?? '-' }}</td>
                        <td>{{ $device->port ?? 4370 }}</td>
                        <td>
                            <span class="badge {{ $device->sync_mode === 'push' ? 'bg-primary' : 'bg-info' }}">
                                {{ ucfirst($device->sync_mode) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $device->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $device->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.biometric-devices.show', $device) }}" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.biometric-devices.edit', $device) }}" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.biometric-devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this device?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fs-3 mb-3 d-block"></i>
                            <strong>No devices found</strong><br>
                            <small>Add your first biometric device to get started.</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($devices->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $devices->links() }}</div>
    @endif
</div>
@endsection
