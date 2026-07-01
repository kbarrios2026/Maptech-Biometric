@extends('layouts.app')
@section('title','Create Employee Status')
@section('content')
<div class="content-wrapper">
  <h3>Create Status</h3>
  <div class="card p-3">
    <form method="POST" action="{{ route('admin.employee-statuses.store') }}">@csrf
      <div class="mb-3"><label>Name</label><input name="name" class="form-control" required></div>
      <div class="mb-3"><label>Color (hex)</label><input name="color" class="form-control"></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection
