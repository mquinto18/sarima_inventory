@extends('layouts.app')

@push('styles')
    {{-- Warm the full-resolution logo while the user is typing, so the
         post-login splash (components/login-splash.blade.php) renders it
         instantly instead of flashing an empty box. The dashboard uses the same
         file for its watermark, so this download is used twice. --}}
    <link rel="preload" as="image" href="{{ asset('images/logo-icon.png') }}">
@endpush

@section('content')
<style>
    .auth-shell {
        min-height: 100vh;
        display: flex;
        background: var(--color-surface);
    }

    .auth-brand-panel {
        flex: 1 1 46%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 28px;
        padding: 64px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(150deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: #fff;
    }

    .auth-brand-panel::before,
    .auth-brand-panel::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .auth-brand-panel::before {
        width: 420px;
        height: 420px;
        top: -160px;
        right: -140px;
    }

    .auth-brand-panel::after {
        width: 280px;
        height: 280px;
        bottom: -120px;
        left: -80px;
        background: rgba(255, 255, 255, 0.06);
    }

    .auth-brand-logo {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .auth-brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .auth-brand-title {
        font-size: 2.4rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .auth-brand-subtitle {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.85);
        max-width: 380px;
        line-height: 1.5;
        position: relative;
        z-index: 1;
    }

    .auth-brand-features {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-top: 12px;
        position: relative;
        z-index: 1;
    }

    .auth-brand-feature {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.98rem;
        color: rgba(255, 255, 255, 0.92);
    }

    .auth-brand-feature svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .auth-form-panel {
        flex: 1 1 54%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .auth-form-card {
        width: 100%;
        max-width: 400px;
        animation: authFadeIn 0.5s cubic-bezier(.4, 0, .2, 1);
    }

    @keyframes authFadeIn {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .auth-form-heading {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--color-text);
        margin-bottom: 6px;
    }

    .auth-form-subheading {
        color: var(--color-text-muted);
        margin-bottom: 32px;
        font-size: 0.98rem;
    }

    .auth-field {
        margin-bottom: 20px;
    }

    .auth-field label {
        display: block;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--color-text);
        margin-bottom: 7px;
    }

    .auth-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .auth-input-wrap svg.auth-input-icon {
        position: absolute;
        left: 14px;
        width: 19px;
        height: 19px;
        color: var(--color-text-muted);
        pointer-events: none;
    }

    .auth-input-wrap input {
        width: 100%;
        padding: 12px 14px 12px 44px;
        border-radius: var(--radius-sm);
        border: 1.5px solid #e5e7eb;
        font-size: 0.98rem;
        background: var(--color-page-bg);
        transition: border-color 0.18s, box-shadow 0.18s;
        outline: none;
    }

    .auth-input-wrap input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-soft);
        background: var(--color-surface);
    }

    .auth-input-wrap input.is-invalid {
        border-color: var(--color-danger);
    }

    .auth-toggle-password {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: var(--color-text-muted);
        display: flex;
    }

    .auth-toggle-password svg {
        width: 19px;
        height: 19px;
    }

    .auth-error {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: var(--color-danger-bg);
        color: var(--color-danger-text);
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        font-size: 0.92rem;
        margin-bottom: 20px;
    }

    .auth-error svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .auth-submit-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px;
        border: none;
        border-radius: var(--radius-sm);
        background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .auth-submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .auth-submit-btn svg {
        width: 18px;
        height: 18px;
    }

    @media (max-width: 900px) {
        .auth-brand-panel {
            display: none;
        }

        .auth-form-panel {
            flex: 1 1 100%;
        }
    }
</style>

<div class="auth-shell">
    <div class="auth-brand-panel">
        <div class="auth-brand-logo">
            <img src="{{ asset('images/logo-icon-128.png') }}" alt="Larios Pharmacy">
        </div>
        <h1 class="auth-brand-title">Larios Pharmacy</h1>
        <p class="auth-brand-subtitle">Inventory, sales, and demand forecasting in one place — built to keep your shelves stocked and your reporting effortless.</p>
        <div class="auth-brand-features">
            <div class="auth-brand-feature">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Real-time stock &amp; reorder alerts
            </div>
            <div class="auth-brand-feature">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                SARIMA-powered sales forecasting
            </div>
            <div class="auth-brand-feature">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Role-based access for your whole team
            </div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-card">
            <div class="auth-form-heading">Welcome back</div>
            <div class="auth-form-subheading">Sign in to your Larios Pharmacy account</div>

            @if ($errors->any())
                <div class="auth-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf

                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M3 7l9 6 9-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input type="email" class="@error('email') is-invalid @enderror"
                            id="email" name="email" placeholder="admin@gmail.com"
                            value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="10" width="16" height="10" rx="2" />
                            <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input type="password" class="@error('password') is-invalid @enderror"
                            id="password" name="password" placeholder="••••••••" required style="padding-right: 44px;">
                        <button type="button" class="auth-toggle-password" id="togglePassword" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit-btn">
                    Sign In
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('password');
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
</script>
@endsection
