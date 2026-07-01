@extends('layouts.app')

@section('title', 'Login - Employee Management System')

@section('styles')
<style>
    body { background: #0f172a; }
    .app-navbar { background: rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.08); }
    .app-shell { background: transparent; align-items: center; justify-content: center; }
    .app-main { display: flex; align-items: center; justify-content: center; background: transparent; padding: 20px; }
    .login-card {
        width: 100%;
        max-width: 420px;
        background: #1e293b;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(0,0,0,.5);
        padding: 40px;
        color: #e2e8f0;
    }
    .login-card .login-logo {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff; margin: 0 auto 20px;
    }
    .login-card h1 { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; text-align: center; }
    .login-card .subtitle { font-size: .84rem; color: #64748b; text-align: center; margin-bottom: 28px; }
    .login-card .form-label { color: #94a3b8; font-size: .82rem; font-weight: 500; }
    .login-card .form-control {
        background: #0f172a; border: 1px solid rgba(255,255,255,.1);
        color: #e2e8f0; border-radius: 9px; padding: 10px 14px;
    }
    .login-card .form-control:focus {
        background: #0f172a; border-color: #3b82f6;
        color: #e2e8f0; box-shadow: 0 0 0 3px rgba(59,130,246,.2);
    }
    .login-card .form-control::placeholder { color: #475569; }
    .login-card .form-check-label { color: #94a3b8; font-size: .84rem; }
    .login-card .btn-login {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border: none; border-radius: 9px; font-weight: 600;
        font-size: .9rem; padding: 11px; letter-spacing: .01em;
        color: #fff; width: 100%; cursor: pointer; transition: opacity .15s;
    }
    .login-card .btn-login:hover { opacity: .9; }
    .demo-hint {
        margin-top: 20px; padding: 12px 14px;
        background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.2);
        border-radius: 9px; font-size: .78rem; color: #94a3b8;
    }
    .demo-hint strong { color: #60a5fa; }
</style>
@endsection

@section('content')
<div class="login-card">
    <div class="login-logo"><i class="fas fa-fingerprint"></i></div>
    <h1>Welcome Back</h1>
    <p class="subtitle">Employee Management System</p>

    @if($errors->any())
    <div class="alert alert-danger mb-3" role="alert">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" placeholder="••••••••" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-arrow-right-to-bracket me-2"></i> Sign In
        </button>
    </form>

    <div class="demo-hint">
        <strong>Demo:</strong> admin@example.com / password
    </div>
</div>
@endsection
