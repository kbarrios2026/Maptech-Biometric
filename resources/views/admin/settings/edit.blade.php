@extends('layouts.app')

@section('title', 'Edit Setting - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Edit Setting</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <form method="POST" action="{{ route('admin.settings.update', $setting->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-3"><label class="form-label">Key</label><input class="form-control" value="{{ $setting->key }}" disabled></div>
            <div class="mb-3"><label class="form-label">Value</label><textarea name="value" class="form-control" rows="4">{{ old('value', $setting->value) }}</textarea></div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
