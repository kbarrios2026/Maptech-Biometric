@extends('layouts.app')

@section('title', "Activity Log Details - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="fas fa-history"></i> Activity Log Details</h1>
            <p class="text-muted">Review the activity entry in detail.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Logs
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">User</dt>
                <dd class="col-sm-9">{{ $activityLog->user?->name ?? 'Unknown' }}</dd>

                <dt class="col-sm-3">Action</dt>
                <dd class="col-sm-9">{{ $activityLog->action }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $activityLog->description }}</dd>

                <dt class="col-sm-3">IP Address</dt>
                <dd class="col-sm-9">{{ $activityLog->ip_address }}</dd>

                <dt class="col-sm-3">User Agent</dt>
                <dd class="col-sm-9">{{ $activityLog->user_agent }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $activityLog->created_at->format('F j, Y h:i A') }}</dd>
            </dl>

            @if($activityLog->old_values || $activityLog->new_values)
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h5>Old Values</h5>
                    <pre class="bg-light p-3 rounded">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
                <div class="col-md-6">
                    <h5>New Values</h5>
                    <pre class="bg-light p-3 rounded">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
