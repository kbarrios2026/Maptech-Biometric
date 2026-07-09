@extends('layouts.app')

@section('title', "System User Details - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="fas fa-user"></i> System User Details</h1>
            <p class="text-muted">Review the selected user information.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.system-users.edit', $systemUser) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit User
            </a>
            <a href="{{ route('admin.system-users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $systemUser->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $systemUser->email }}</dd>

                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9">{{ $systemUser->role->name ?? 'None' }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $systemUser->created_at->format('F j, Y h:i A') }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $systemUser->updated_at->format('F j, Y h:i A') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
