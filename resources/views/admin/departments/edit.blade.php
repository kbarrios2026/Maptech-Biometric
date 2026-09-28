@extends('layouts.app')

@section('title', "Edit Department - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Edit Department</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $department->name) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $department->description) }}</textarea>
                </div>
                <div class="col-12 form-check ms-3">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                </div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Update Department</button></div>
        </form>
    </div>
</div>
@endsection