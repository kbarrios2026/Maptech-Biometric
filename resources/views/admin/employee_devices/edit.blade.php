@extends('layouts.app')
@section('title','Edit Device Mapping')
@section('content')
<div class="content-wrapper">
  <h3>Edit Device</h3>
  <div class="card p-3">
    <form method="POST" action="{{ route('admin.employee-devices.update',$device) }}">@csrf @method('PUT')
      <div class="mb-3"><label>Employee</label><select name="employee_id" class="form-select">@foreach($employees as $e)<option value="{{ $e->id }}" @selected($e->id==$device->employee_id)>{{ $e->full_name }}</option>@endforeach</select></div>
      <div class="mb-3"><label>Device Identifier</label><input name="device_identifier" class="form-control" value="{{ $device->device_identifier }}" required></div>
      <div class="mb-3"><label>Device Name</label><input name="device_name" class="form-control" value="{{ $device->device_name }}"></div>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_primary" id="is_primary" @checked($device->is_primary)><label class="form-check-label" for="is_primary">Primary</label></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection
