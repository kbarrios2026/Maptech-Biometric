@extends('layouts.app')
@section('title','Edit Employee Status')
@section('content')
<div class="content-wrapper">
  <h3>Edit Status</h3>
  <div class="card p-3">
    <form method="POST" action="{{ route('admin.employee-statuses.update',$item) }}">@csrf @method('PUT')
      <div class="mb-3"><label>Name</label><input name="name" class="form-control" value="{{ old('name',$item->name) }}" required></div>
      <div class="mb-3"><label>Color (hex)</label><input name="color" class="form-control" value="{{ old('color',$item->color) }}"></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection
