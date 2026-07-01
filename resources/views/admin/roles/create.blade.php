@extends('layouts.app')

@section('title', 'Create Role - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-plus-square"></i> Create Role</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Role Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Permissions</label>
                    <div class="row">
                        @foreach($availablePermissions as $permission)
                        <div class="col-md-4 mb-2">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="permissions[]" value="{{ $permission }}" 
                                       @checked(is_array(old('permissions', [])) && in_array($permission, old('permissions', [])))>
                                <span class="form-check-label">{{ ucwords(str_replace('_', ' ', $permission)) }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('permissions')
                    <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </form>
        </div>
    </div>
</div>
@endsection
