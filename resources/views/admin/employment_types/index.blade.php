@extends('layouts.app')
@section('title','Employment Types')
@section('content')
<div class="content-wrapper">
  <div class="d-flex justify-content-between mb-3"><h3>Employment Types</h3><a href="{{ route('admin.employment-types.create') }}" class="btn btn-primary">Add</a></div>
  <div class="card p-3">
    <ul class="list-group">
      @foreach($types as $t)
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><strong>{{ $t->name }}</strong><div class="text-muted small">{{ $t->description }}</div></div>
          <div>
            <a href="{{ route('admin.employment-types.edit',$t) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('admin.employment-types.destroy',$t) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form>
          </div>
        </li>
      @endforeach
    </ul>
    <div class="mt-3">{{ $types->links() }}</div>
  </div>
</div>
@endsection
