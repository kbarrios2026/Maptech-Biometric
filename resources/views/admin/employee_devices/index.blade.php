@extends('layouts.app')
@section('title','Employee Devices')
@section('content')
<div class="content-wrapper">
  <div class="d-flex justify-content-between mb-3"><h3>Employee Devices</h3><a href="{{ route('admin.employee-devices.create') }}" class="btn btn-primary">Map Device</a></div>
  <div class="card p-3">
    <table class="table table-striped">
      <thead><tr><th>Employee</th><th>Device ID</th><th>Name</th><th>Primary</th><th></th></tr></thead>
      <tbody>
        @foreach($devices as $d)
        <tr>
          <td>{{ $d->employee?->full_name ?? '-' }}</td>
          <td>{{ $d->device_identifier }}</td>
          <td>{{ $d->device_name ?? '-' }}</td>
          <td>{{ $d->is_primary ? 'Yes' : 'No' }}</td>
          <td><a href="{{ route('admin.employee-devices.edit',$d) }}" class="btn btn-sm btn-warning">Edit</a>
          <form action="{{ route('admin.employee-devices.destroy',$d) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="mt-3">{{ $devices->links() }}</div>
  </div>
</div>
@endsection
