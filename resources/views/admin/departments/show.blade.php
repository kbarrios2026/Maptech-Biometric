@extends('layouts.app')

@section('title', "Department Details - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Department Details</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $department->name }}</p>
        <p><strong>Code:</strong> {{ $department->code ?? '-' }}</p>
        <p><strong>Status:</strong> {{ $department->is_active ? 'Active' : 'Inactive' }}</p>
        <p><strong>Description:</strong> {{ $department->description ?? '-' }}</p>
        <p><strong>Employees:</strong> {{ $department->employees()->count() }}</p>
    </div>
</div>
@endsection