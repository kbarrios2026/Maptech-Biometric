@extends('layouts.app')

@section('title', 'Dashboard - Employee Management System')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-gauge-high"></i> Dashboard</h1>
        <p class="page-subtitle">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ $totalUsers }}</div>
            </div>
            <div class="stat-icon" style="background:#eff6ff; color:#3b82f6;">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-value">{{ $totalEmployees }}</div>
            </div>
            <div class="stat-icon" style="background:#f0fdf4; color:#22c55e;">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Departments</div>
                <div class="stat-value">{{ $totalDepartments }}</div>
            </div>
            <div class="stat-icon" style="background:#faf5ff; color:#a855f7;">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Active Employees</div>
                <div class="stat-value">{{ $activeEmployees }}</div>
            </div>
            <div class="stat-icon" style="background:#fff7ed; color:#f97316;">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>
</div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-clock-rotate-left me-2 text-muted"></i>Recent Activities</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivityLogs as $log)
                            <tr>
                                <td><strong>{{ $log->user?->name ?? 'Unknown' }}</strong></td>
                                <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                <td><small class="text-muted">{{ $log->created_at->diffForHumans() }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No activity logs yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
