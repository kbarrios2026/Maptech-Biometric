@extends('layouts.app')

@section('title', "Role Details - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="fas fa-shield-alt"></i> Role Details</h1>
            <p class="text-muted">Review the selected role and permissions.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Role
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $role->name }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $role->description }}</dd>

                <dt class="col-sm-3">Permissions</dt>
                <dd class="col-sm-9">
                    <ul class="list-unstyled mb-0">
                        @foreach($role->permissions ?? [] as $permission)
                        <li><i class="fas fa-check text-success"></i> {{ ucwords(str_replace('_', ' ', $permission)) }}</li>
                        @endforeach
                    </ul>
                </dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $role->created_at->format('F j, Y h:i A') }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $role->updated_at->format('F j, Y h:i A') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
