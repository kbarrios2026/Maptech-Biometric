@extends('layouts.app')
@section('title','Edit Employment Type')
@section('content')
<div class="content-wrapper">
  <h3>Edit Employment Type</h3>
  <div class="card p-3">
    <form method="POST" action="{{ route('admin.employment-types.update',$type) }}">@csrf @method('PUT')
      <div class="mb-3"><label>Name</label><input name="name" class="form-control" value="{{ old('name',$type->name) }}" required></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control">{{ old('description',$type->description) }}</textarea></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection
