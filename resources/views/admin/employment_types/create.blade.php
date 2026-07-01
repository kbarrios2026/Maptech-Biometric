@extends('layouts.app')
@section('title','Create Employment Type')
@section('content')
<div class="content-wrapper">
  <h3>Create Employment Type</h3>
  <div class="card p-3">
    <form method="POST" action="{{ route('admin.employment-types.store') }}">@csrf
      <div class="mb-3"><label>Name</label><input name="name" class="form-control" required></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection
