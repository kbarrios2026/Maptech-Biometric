@extends('layouts.app')

@section('title', "Create Employee - Maptech's Employee System")

@section('content')
<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6"><h1 class="h3">Create Employee</h1></div>
        <div class="col-md-6 text-end"><a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Back</a></div>
    </div>

    <div class="card p-4">
        <form method="POST" action="{{ route('admin.employees.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">User Account</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" required></div>
                <div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}"></div>
                <div class="col-md-6"><label class="form-label">Department</label><select id="department_id" name="department_id" class="form-select"><option value="">Select Department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Position</label><select id="position_id" name="position_id" class="form-select"><option value="">Select Position</option>@foreach($positions as $position)<option value="{{ $position->id }}" data-department-id="{{ $position->department_id }}" @selected(old('position_id') == $position->id)>{{ $position->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                <div class="col-md-6"><label class="form-label">Joining Date</label><input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}" required></div>
                <div class="col-md-6"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select Gender</option><option value="Male" @selected(old('gender') === 'Male')>Male</option><option value="Female" @selected(old('gender') === 'Female')>Female</option><option value="Other" @selected(old('gender') === 'Other')>Other</option></select></div>
                <div class="col-md-6">
                    <label class="form-label">Employment Type</label>
                    <select name="employment_type_id" class="form-select">
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" @selected(old('employment_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee Status</label>
                    <select name="employee_status_id" class="form-select">
                        <option value="">Select Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->id }}" @selected(old('employee_status_id') == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Biometric ID</label><input type="text" name="biometric_id" class="form-control" value="{{ old('biometric_id') }}"></div>
                <div class="col-md-6"><label class="form-label">Monthly Salary</label><input type="number" step="0.01" min="0" name="monthly_salary" class="form-control" value="{{ old('monthly_salary') }}"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea></div>
                <div class="col-12 form-check ms-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked><label for="is_active" class="form-check-label">Active</label></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Save Employee</button></div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    @include('admin.employees.partials.department-position-filter')
@endsection