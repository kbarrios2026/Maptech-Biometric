@extends('layouts.app')

@section('title', "Employment Type Details")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">Employment Type Details</h1>
            <a href="{{ route('admin.employment-types.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $type->name }}</p>
        <p><strong>Description:</strong> {{ $type->description ?? '-' }}</p>
    </div>
</div>
@endsection
