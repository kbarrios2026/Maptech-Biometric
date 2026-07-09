@extends('layouts.app')

@section('title', "Position Details - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Position Details</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.positions.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $position->name }}</p>
        <p><strong>Department:</strong> {{ $position->department?->name ?? '-' }}</p>
        <p><strong>Base Salary:</strong> {{ $position->base_salary ? number_format($position->base_salary, 2) : '-' }}</p>
        <p><strong>Status:</strong> {{ $position->is_active ? 'Active' : 'Inactive' }}</p>
        <p><strong>Description:</strong> {{ $position->description ?? '-' }}</p>
        <p><strong>Employees:</strong> {{ $position->employees()->count() }}</p>
    </div>
</div>
@endsection