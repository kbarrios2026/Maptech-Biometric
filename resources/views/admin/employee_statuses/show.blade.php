@extends('layouts.app')

@section('title', 'Employee Status Details')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">Employee Status Details</h1>
            <a href="{{ route('admin.employee-statuses.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $item->name }}</p>
        <p><strong>Color:</strong> {{ $item->color ?? '-' }}</p>
    </div>
</div>
@endsection
