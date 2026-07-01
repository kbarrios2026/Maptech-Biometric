@extends('layouts.app')
@section('title','Employee Statuses')
@section('content')
<div class="content-wrapper">
  <div class="d-flex justify-content-between mb-3"><h3>Employee Statuses</h3><a href="{{ route('admin.employee-statuses.create') }}" class="btn btn-primary">Add</a></div>
  <div class="card p-3">
    <ul class="list-group">
      @foreach($items as $it)
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><strong>{{ $it->name }}</strong></div>
          <div>
            <a href="{{ route('admin.employee-statuses.edit',$it) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('admin.employee-statuses.destroy',$it) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form>
          </div>
        </li>
      @endforeach
    </ul>
    <div class="mt-3">{{ $items->links() }}</div>
  </div>
</div>
@endsection
