<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Employee Management System')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── CSS Variables ── */
        :root {
            --bg-primary: #f1f4f9;
            --bg-secondary: #fff;
            --bg-tertiary: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-tertiary: #94a3b8;
            --border-color: #e2e8f0;
            --navbar-bg: #1e293b;
            --sidebar-bg: #1e293b;
        }

        body.dark-mode {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #e2e8f0;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border-color: #334155;
            --navbar-bg: #0f172a;
            --sidebar-bg: #0f172a;
        }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: .9rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
            transition: background-color .2s, color .2s;
        }

        /* ── Navbar ── */
        .app-navbar {
            height: 60px;
            background: var(--navbar-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 1px 3px rgba(0,0,0,.25);
            border-bottom: 1px solid var(--border-color);
        }
        .app-navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -.01em;
            text-decoration: none;
        }
        .app-navbar .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            color: #fff;
            flex-shrink: 0;
        }
        .app-navbar .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .app-navbar .user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #cbd5e1;
            font-size: .84rem;
        }
        .app-navbar .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            color: #fff;
        }
        .btn-navbar {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            color: #e2e8f0;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: .82rem;
            cursor: pointer;
            transition: background .15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-navbar:hover { background: rgba(255,255,255,.14); color: #fff; }
        .btn-toggle-sidebar {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #94a3b8;
            border-radius: 7px;
            padding: 6px 9px;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-toggle-sidebar:hover { background: rgba(255,255,255,.12); color: #fff; }

        /* ── App shell ── */
        .app-shell {
            display: flex;
            height: calc(100vh - 60px);
        }

        /* ── Sidebar ── */
        .app-sidebar {
            width: 240px;
            flex-shrink: 0;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .2s ease;
            border-right: 1px solid var(--border-color);
        }
        .app-sidebar .sidebar-inner {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px 10px 16px;
        }
        .app-sidebar .sidebar-inner::-webkit-scrollbar { width: 4px; }
        .app-sidebar .sidebar-inner::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }
        .sidebar-section-label {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-tertiary);
            padding: 8px 10px 4px;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-divider {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 8px 0;
        }
        .app-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 0;
            color: var(--text-tertiary);
            padding: 9px 10px;
            border-radius: 8px;
            margin-bottom: 2px;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            transition: background .15s, color .15s;
        }
        .app-sidebar .nav-link i {
            width: 36px;
            min-width: 36px;
            text-align: center;
            font-size: .9rem;
            transition: color .15s;
        }
        .nav-text { opacity: 1; transition: opacity .15s; }
        .app-sidebar .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
        }
        .app-sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(59,130,246,.25), rgba(59,130,246,.1));
            color: #60a5fa;
            border: 1px solid rgba(59,130,246,.2);
        }
        .app-sidebar .nav-link.active i { color: #60a5fa; }

        /* Collapsed sidebar */
        body.sidebar-collapsed .app-sidebar { width: 60px; }
        body.sidebar-collapsed .nav-text { opacity: 0; width: 0; overflow: hidden; }
        body.sidebar-collapsed .sidebar-section-label { opacity: 0; }
        body.sidebar-collapsed .app-sidebar .nav-link { justify-content: center; }

        /* ── Main content ── */
        .app-main {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            padding: 28px 28px 40px;
            background: var(--bg-primary);
        }
        .app-main::-webkit-scrollbar { width: 8px; }
        .app-main::-webkit-scrollbar-track { background: transparent; }
        .app-main::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }
        .app-main::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.2); }
        .content-wrapper { /* compatibility shim */ }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: box-shadow .2s;
        }
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
        }
        body.dark-mode .card {
            border-color: #2a3f5a;
        }
        .card-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            color: var(--text-primary);
        }
        body.dark-mode .card-header {
            border-color: #2a3f5a;
            background: #192238;
        }
        .card-body { 
            padding: 20px;
            color: var(--text-primary);
        }
        .card-footer {
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
            padding: 12px 20px;
        }

        /* Stat cards */
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            transition: box-shadow .2s, transform .15s;
            color: var(--text-primary);
        }
        body.dark-mode .stat-card {
            border-color: #2a3f5a;
            background: #192238;
        }
        .stat-card:hover { 
            box-shadow: 0 6px 16px rgba(0,0,0,.12); 
            transform: translateY(-2px); 
        }
        .stat-card .stat-label { 
            font-size: .78rem; 
            font-weight: 600; 
            color: var(--text-secondary); 
            text-transform: uppercase; 
            letter-spacing: .05em; 
        }
        .stat-card .stat-value { 
            font-size: 1.9rem; 
            font-weight: 700; 
            color: var(--text-primary); 
            line-height: 1.1; 
            margin-top: 2px; 
        }
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        /* ── Page header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .page-title i {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .9rem;
        }
        .page-subtitle { color: var(--text-secondary); font-size: .85rem; margin: 2px 0 0; }

        /* ── Tables ── */
        .table { 
            font-size: .875rem;
            color: var(--text-primary);
            background: var(--bg-secondary);
            margin-bottom: 0;
        }
        .table thead th {
            background: var(--bg-tertiary);
            border-bottom: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 12px 14px;
        }
        body.dark-mode .table.table-light thead th,
        body.dark-mode .table-light th {
            background: #192238;
        }
        .table tbody tr { 
            transition: background .15s ease;
            border-color: var(--border-color);
        }
        body.dark-mode .table tbody tr {
            background: #1e293b;
        }
        body.dark-mode .table tbody tr:hover { 
            background: #263449;
        }
        body:not(.dark-mode) .table tbody tr:hover { 
            background: #f8fafc; 
        }
        .table tbody td {
            background: inherit;
        }
        .table td { 
            padding: 12px 14px; 
            vertical-align: middle; 
            border-color: rgba(255,255,255,.05);
            color: var(--text-primary);
        }
        body.dark-mode .table td {
            border-color: rgba(255,255,255,.08);
        }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #192238;
        }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd):hover {
            background-color: #263449;
        }

        /* ── Buttons ── */
        .btn-primary { background: #3b82f6; border-color: #3b82f6; font-weight: 500; transition: all .15s; }
        .btn-primary:hover { background: #2563eb; border-color: #2563eb; }
        .btn-sm { padding: 5px 11px; font-size: .8rem; border-radius: 6px; }
        .btn { color: var(--text-primary); transition: all .15s ease; }
        .btn-light { background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary); }
        .btn-light:hover { background: var(--border-color); }
        body.dark-mode .btn-light { background: #263449; border-color: #2a3f5a; }
        body.dark-mode .btn-light:hover { background: #334155; }

        /* Action buttons - eye-comfortable colors for dark mode */
        .btn-info { 
            background: #06b6d4; 
            border-color: #06b6d4; 
            color: #fff;
            font-weight: 500;
        }
        .btn-info:hover { background: #0891b2; border-color: #0891b2; }
        body.dark-mode .btn-info {
            background: #0e7490;
            border-color: #0e7490;
            color: #cffafe;
        }
        body.dark-mode .btn-info:hover {
            background: #155e75;
            border-color: #155e75;
        }

        .btn-warning {
            background: #f97316;
            border-color: #f97316;
            color: #fff;
            font-weight: 500;
        }
        .btn-warning:hover { background: #ea580c; border-color: #ea580c; }
        body.dark-mode .btn-warning {
            background: #b45309;
            border-color: #b45309;
            color: #fed7aa;
        }
        body.dark-mode .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
            font-weight: 500;
        }
        .btn-danger:hover { background: #dc2626; border-color: #dc2626; }
        body.dark-mode .btn-danger {
            background: #991b1b;
            border-color: #991b1b;
            color: #fecaca;
        }
        body.dark-mode .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        .btn-success {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
            font-weight: 500;
        }
        .btn-success:hover { background: #16a34a; border-color: #16a34a; }
        body.dark-mode .btn-success {
            background: #166534;
            border-color: #166534;
            color: #bbf7d0;
        }
        body.dark-mode .btn-success:hover {
            background: #15803d;
            border-color: #15803d;
        }

        /* Action button grouping styling */
        .btn-sm + .btn-sm { margin-left: 6px; }
        td > .btn-sm { 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0;
        }
        td > form.d-inline .btn-sm {
            margin-left: 6px;
        }

        /* ── Badges / Status ── */
        .badge { 
            font-weight: 600; 
            font-size: .72rem; 
            padding: 5px 10px; 
            border-radius: 6px;
            display: inline-block;
            transition: all .15s ease;
        }
        .badge-primary { background: #3b82f6; color: #fff; }
        .badge-success { background: #22c55e; color: #fff; }
        .badge-danger { background: #ef4444; color: #fff; }
        .badge-warning { background: #f97316; color: #fff; }
        .badge-info { background: #06b6d4; color: #fff; }
        .badge-light { background: var(--bg-tertiary); color: var(--text-primary); }
        .badge-secondary { background: var(--text-tertiary); color: var(--text-primary); }

        /* Dark mode badge adjustments - eye-comfortable colors */
        body.dark-mode .badge-primary { background: #1e3a8a; color: #bfdbfe; }
        body.dark-mode .badge-success { background: #166534; color: #bbf7d0; }
        body.dark-mode .badge-danger { background: #7f1d1d; color: #fecaca; }
        body.dark-mode .badge-warning { background: #92400e; color: #fed7aa; }
        body.dark-mode .badge-info { background: #0c4a6e; color: #cffafe; }
        body.dark-mode .badge-light { background: #2a3f5a; color: #e2e8f0; }
        body.dark-mode .badge-secondary { background: #475569; color: #cbd5e1; }

        /* Status badge styling */
        .badge.bg-success {
            background: #22c55e !important;
            color: #fff;
        }
        body.dark-mode .badge.bg-success {
            background: #166534 !important;
            color: #bbf7d0;
        }

        .badge.bg-primary {
            background: #3b82f6 !important;
            color: #fff;
        }
        body.dark-mode .badge.bg-primary {
            background: #1e3a8a !important;
            color: #bfdbfe;
        }

        .badge.bg-info {
            background: #06b6d4 !important;
            color: #fff;
        }
        body.dark-mode .badge.bg-info {
            background: #0c4a6e !important;
            color: #cffafe;
        }

        /* ── Forms ── */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: .875rem;
            padding: 9px 12px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        body.dark-mode .form-control, 
        body.dark-mode .form-select {
            background: #192238;
            border-color: #2a3f5a;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background: #0d1520;
            border-color: #60a5fa;
        }
        .form-label { 
            font-weight: 500; 
            font-size: .84rem; 
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .form-text {
            color: var(--text-tertiary);
            font-size: .8rem;
        }

        /* ── Alerts ── */
        .alert { border-radius: 10px; border: none; font-size: .875rem; }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-danger  { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        body.dark-mode .alert-success { background: rgba(34,197,94,.1); color: #86efac; }
        body.dark-mode .alert-danger { background: rgba(239,68,68,.1); color: #fca5a5; }

        /* ── Mobile sidebar overlay ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            top: 60px;
            background: rgba(0,0,0,.45);
            z-index: 1029;
        }
        body.sidebar-open .sidebar-overlay { display: block; }

        @media (max-width: 767.98px) {
            .app-sidebar {
                position: fixed;
                top: 60px;
                left: -240px;
                height: calc(100vh - 60px);
                z-index: 1030;
                width: 240px !important;
                transition: left .22s ease;
            }
            body.sidebar-open .app-sidebar { left: 0; }
            body.sidebar-collapsed .app-sidebar { left: -240px; }
            .app-main { padding: 18px 16px 32px; }
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="app-navbar">
        <div class="d-flex align-items-center gap-2">
            <button id="sidebarToggle" class="btn-toggle-sidebar" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ auth()->check() ? route('admin.dashboard') : '/' }}" class="brand">
                <div class="brand-icon"><i class="fas fa-fingerprint"></i></div>
                <span>Employee Management System</span>
            </a>
        </div>
        <div class="nav-right">
            <button id="darkModeToggle" class="btn-navbar" title="Toggle dark mode" style="width: 36px; padding: 5px; justify-content: center;">
                <i class="fas fa-moon"></i>
            </button>
            @auth
                <div class="user-chip d-none d-sm-flex">
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <span>{{ auth()->user()->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn-navbar">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-navbar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            @endauth
        </div>
    </nav>

    <div class="app-shell">
        {{-- Sidebar --}}
        @auth
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <nav class="app-sidebar" id="sidebar">
            <div class="sidebar-inner">
                <ul class="nav flex-column mb-0">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-gauge-high"></i><span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    @if(auth()->user()->hasRole(['Super Admin', 'HR Admin']))
                    <hr class="sidebar-divider">
                    <div class="sidebar-section-label">People</div>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.system-users.*')) active @endif" href="{{ route('admin.system-users.index') }}">
                            <i class="fas fa-user-shield"></i><span class="nav-text">System Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.employees.*')) active @endif" href="{{ route('admin.employees.index') }}">
                            <i class="fas fa-users"></i><span class="nav-text">Employees</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.departments.*')) active @endif" href="{{ route('admin.departments.index') }}">
                            <i class="fas fa-sitemap"></i><span class="nav-text">Departments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.positions.*')) active @endif" href="{{ route('admin.positions.index') }}">
                            <i class="fas fa-briefcase"></i><span class="nav-text">Positions</span>
                        </a>
                    </li>

                    <hr class="sidebar-divider">
                    <div class="sidebar-section-label">Attendance</div>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.attendance.index') || request()->routeIs('admin.attendance.store')) active @endif" href="{{ route('admin.attendance.index') }}">
                            <i class="fas fa-calendar-check"></i><span class="nav-text">Daily Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.attendance.hr-admin')) active @endif" href="{{ route('admin.attendance.hr-admin') }}">
                            <i class="fas fa-file-lines"></i><span class="nav-text">HR Admin DTR</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.biometric-devices.*')) active @endif" href="{{ route('admin.biometric-devices.index') }}">
                            <i class="fas fa-fingerprint"></i><span class="nav-text">Biometric Devices</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole(['Super Admin', 'HR Admin']))
                    <hr class="sidebar-divider">
                    <div class="sidebar-section-label">Company</div>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.company.*')) active @endif" href="{{ route('admin.company.edit') }}">
                            <i class="fas fa-building"></i><span class="nav-text">Company Profile</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole('Super Admin'))
                    <hr class="sidebar-divider">
                    <div class="sidebar-section-label">System</div>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.settings.*')) active @endif" href="{{ route('admin.settings.index') }}">
                            <i class="fas fa-gear"></i><span class="nav-text">Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.roles.*')) active @endif" href="{{ route('admin.roles.index') }}">
                            <i class="fas fa-shield-halved"></i><span class="nav-text">Roles</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasRole(['Super Admin', 'HR Admin']))
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.activity-logs.*')) active @endif" href="{{ route('admin.activity-logs.index') }}">
                            <i class="fas fa-clock-rotate-left"></i><span class="nav-text">Activity Logs</span>
                        </a>
                    </li>
                    @endif

                    <hr class="sidebar-divider">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('change-password') }}">
                            <i class="fas fa-key"></i><span class="nav-text">Change Password</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        @endauth

        {{-- Main --}}
        <main class="app-main" id="appMain">
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');
            const darkModeToggle = document.getElementById('darkModeToggle');

            // Initialize dark mode from localStorage
            if (localStorage.getItem('dark-mode') === '1') {
                body.classList.add('dark-mode');
                updateDarkModeIcon();
            }

            // Restore collapse state on desktop
            if (window.innerWidth >= 768 && localStorage.getItem('sidebar-collapsed') === '1') {
                body.classList.add('sidebar-collapsed');
            }

            toggle?.addEventListener('click', function () {
                if (window.innerWidth < 768) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
                }
            });

            overlay?.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });

            // Dark mode toggle
            darkModeToggle?.addEventListener('click', function () {
                body.classList.toggle('dark-mode');
                localStorage.setItem('dark-mode', body.classList.contains('dark-mode') ? '1' : '0');
                updateDarkModeIcon();
            });

            function updateDarkModeIcon() {
                const icon = darkModeToggle?.querySelector('i');
                if (icon) {
                    if (body.classList.contains('dark-mode')) {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                    } else {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                    }
                }
            }
        })();
    </script>
    @yield('scripts')
</body>
</html>
