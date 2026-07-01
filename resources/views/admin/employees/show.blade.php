@extends('layouts.app')

@section('title', 'Employee Details - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Employee Details</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <p><strong>Employee ID:</strong> {{ $employee->employee_id }}</p>
        <p><strong>Name:</strong> {{ $employee->full_name }}</p>
        <p><strong>Email:</strong> {{ $employee->email }}</p>
        <p><strong>Phone:</strong> {{ $employee->phone ?? '-' }}</p>
        <p><strong>Department:</strong> {{ $employee->department?->name ?? '-' }}</p>
        <p><strong>Position:</strong> {{ $employee->position?->name ?? '-' }}</p>
        <p><strong>Employment Type:</strong> {{ $employee->employmentType?->name ?? '-' }}</p>
        <p><strong>Status:</strong> {{ $employee->status?->name ?? $employee->employment_status }}</p>
        <p><strong>Biometric ID:</strong> {{ $employee->biometric_id ?? '-' }}</p>
        <p><strong>Monthly Salary:</strong> {{ $employee->monthly_salary ? number_format($employee->monthly_salary, 2) : '-' }}</p>
        <p><strong>Address:</strong> {{ $employee->address ?? '-' }}</p>
        <hr>
        <h5>Mapped Devices</h5>
        @if($employee->devices && $employee->devices->count())
            <ul>
                @foreach($employee->devices as $d)
                    <li>{{ $d->device_identifier }} @if($d->device_name) - {{ $d->device_name }} @endif @if($d->is_primary) <span class="badge bg-success">Primary</span> @endif</li>
                @endforeach
            </ul>
        @else
            <div class="text-muted">No devices mapped.</div>
        @endif
    </div>
</div>
@endsection