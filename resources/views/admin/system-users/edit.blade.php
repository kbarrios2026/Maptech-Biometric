@extends('layouts.app')

@section('title', 'Edit System User - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-user-edit"></i> Edit System User</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.system-users.update', $systemUser) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $systemUser->name) }}" required>
                    @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email', $systemUser->email) }}" required>
                    @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label">Role</label>
                    <select class="form-control @error('role_id') is-invalid @enderror" 
                            id="role_id" name="role_id" required>
                        <option value="">Select a role</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $systemUser->role_id) == $role->id)>
                            {{ $role->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('role_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="{{ route('admin.system-users.show', $systemUser) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </form>
        </div>
    </div>
</div>
@endsection
