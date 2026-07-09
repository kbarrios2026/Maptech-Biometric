<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Maptech's Employee System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #112d5f 0%, #0b1637 45%, #081126 100%);
            padding: 20px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
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
        .login-logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            margin: 0 auto 20px;
        }
        .login-card h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #f1f5f9;
            text-align: center;
            margin-bottom: 6px;
        }
        .subtitle {
            font-size: .84rem;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 28px;
        }
        .form-label {
            color: #cbd5e1;
            font-size: .82rem;
            font-weight: 500;
        }
        .form-control {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,.14);
            color: #e2e8f0;
            border-radius: 9px;
            padding: 10px 14px;
            transition: border-color .15s, box-shadow .15s, background-color .15s;
        }
        .form-control:focus {
            background: #0f172a;
            border-color: #60a5fa;
            color: #e2e8f0;
            box-shadow: 0 0 0 2px rgba(96,165,250,.18);
            outline: none;
        }
        .form-control::placeholder { color: #475569; }

        /* Keep browser autofill from turning fields light when clicked/focused */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus,
        .form-control:-webkit-autofill:active {
            -webkit-text-fill-color: #e2e8f0;
            box-shadow: 0 0 0 1000px #0f172a inset;
            -webkit-box-shadow: 0 0 0 1000px #0f172a inset;
            transition: background-color 9999s ease-in-out 0s;
            caret-color: #e2e8f0;
        }
        .form-check-label {
            color: #cbd5e1;
            font-size: .84rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border: none;
            border-radius: 9px;
            font-weight: 600;
            font-size: .9rem;
            padding: 11px;
            letter-spacing: .01em;
            color: #fff;
            width: 100%;
            cursor: pointer;
            transition: opacity .15s;
        }
        .btn-login:hover { opacity: .9; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo"><i class="fas fa-fingerprint"></i></div>
        <h1>Welcome Back</h1>
        <p class="subtitle">Maptech's Employee System</p>

        @if($errors->any())
        <div class="alert alert-danger mb-3" role="alert">
            @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" placeholder="********" required>
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket me-2"></i> Sign In
            </button>
        </form>
    </div>
</body>
</html>
