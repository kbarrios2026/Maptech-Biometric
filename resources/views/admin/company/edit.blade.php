@extends('layouts.app')

@section('title', "Company Profile - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Company Profile</h1></div>
    </div>

    <div class="card p-4">
        <form method="POST" action="{{ route('admin.company.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $profile->name ?? '') }}" required></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $profile->phone ?? '') }}"></div>
                <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $profile->address ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input name="email" class="form-control" value="{{ old('email', $profile->email ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label">Website</label><input name="website" class="form-control" value="{{ old('website', $profile->website ?? '') }}"></div>
                <div class="col-12"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control"></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Save Company Profile</button></div>
        </form>
    </div>
</div>
@endsection
