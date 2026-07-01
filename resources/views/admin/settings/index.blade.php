@extends('layouts.app')

@section('title', 'System Settings - Employee Management System')

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">System Settings</h1></div>
    </div>

    <div class="card p-3">
        <table class="table">
            <thead><tr><th>Key</th><th>Value</th><th></th></tr></thead>
            <tbody>
                @foreach($settings as $setting)
                <tr>
                    <td>{{ $setting->key }}</td>
                    <td>{{ Str::limit($setting->value, 100) }}</td>
                    <td><a href="{{ route('admin.settings.edit', $setting->id) }}" class="btn btn-sm btn-primary">Edit</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-center">{{ $settings->links() }}</div>
    </div>
</div>
@endsection
