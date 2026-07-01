@extends('layouts.app')

@section('title', 'Employee Device Mapping')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">Employee Device Mapping</h1>
            <a href="{{ route('admin.employee-devices.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card p-4">
        <p><strong>Employee:</strong> {{ $device->employee->full_name ?? 'Unknown' }}</p>
        <p><strong>Device Identifier:</strong> {{ $device->device_identifier }}</p>
        <p><strong>Device Name:</strong> {{ $device->device_name ?? '-' }}</p>
        <p><strong>Primary:</strong> {{ $device->is_primary ? 'Yes' : 'No' }}</p>
    </div>
</div>
@endsection
