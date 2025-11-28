@extends('layouts.app')

@section('content')
<style>
    .login-bg {
        min-height: 100vh;
        background: linear-gradient(135deg, #e0e7ff 0%, #f2f6ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-card {
        border-radius: 20px;
        min-width: 400px;
        max-width: 100%;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        background: #fff;
        animation: fadeIn 0.7s cubic-bezier(.4,0,.2,1);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .login-logo {
        width: 56px;
        height: 56px;
        margin-bottom: 10px;
        filter: drop-shadow(0 2px 8px #a5b4fc33);
    }
    .input-group-text {
        background: #f2f6ff;
        border: none;
    }
    .form-control:focus {
        box-shadow: 0 0 0 2px #6366f1;
        border-color: #6366f1;
    }
    .login-title {
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: 0.5px;
    }
    .login-subtitle {
        font-size: 1rem;
        color: #6b7280;
    }
    .login-btn {
        background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%);
        border: none;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: background 0.2s;
    }
    .login-btn:hover {
        background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);
    }
    .alert-danger {
        font-size: 0.95rem;
        border-radius: 8px;
    }
</style>
<div class="login-bg">
    <div class="login-card p-4">
        <div class="text-center mb-3">
                        <span class="login-logo" style="display:inline-block;">
                                <!-- Crystal Ball + Line Graph SVG -->
                                <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="crystalGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#a5b4fc"/>
                                            <stop offset="1" stop-color="#6366f1"/>
                                        </linearGradient>
                                    </defs>
                                    <circle cx="28" cy="28" r="20" fill="url(#crystalGradient)" stroke="#6366f1" stroke-width="2"/>
                                    <ellipse cx="28" cy="38" rx="12" ry="4" fill="#fff" fill-opacity=".25"/>
                                    <polyline points="16,36 22,28 28,32 34,20 40,26" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="22" cy="28" r="2" fill="#fff"/>
                                    <circle cx="28" cy="32" r="2" fill="#fff"/>
                                    <circle cx="34" cy="20" r="2" fill="#fff"/>
                                    <circle cx="40" cy="26" r="2" fill="#fff"/>
                                    <circle cx="16" cy="36" r="2" fill="#fff"/>
                                    <ellipse cx="28" cy="22" rx="6" ry="2" fill="#fff" fill-opacity=".18"/>
                                </svg>
                        </span>
            <div class="login-title mt-2 mb-0">SARIMA Analytics</div>
            <div class="login-subtitle mb-2">Sales Forecasting &amp; Inventory Management</div>
        </div>
        <form method="POST" action="{{ route('login') }}" autocomplete="off">
            @csrf

            @if ($errors->any())
            <div class="alert alert-danger text-center mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <div class="form-group mb-3">
                <label for="email" class="mb-1">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                        id="email" name="email" placeholder="admin@gmail.com"
                        value="{{ old('email') }}" required autofocus>
                </div>
            </div>
            <div class="form-group mb-4">
                <label for="password" class="mb-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                        id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn login-btn w-100 py-2 mb-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>
    </div>
</div>
@endsection