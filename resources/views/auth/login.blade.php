<!-- Simple Login Page -->
@extends('layouts.app')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: #f2f6ff;">
    <div class="card shadow-sm p-4" style="border-radius: 16px; min-width: 400px; max-width: 100%;">
        <div class="text-center mb-3">
            <h5 class="mt-2 mb-0">SARIMA Analytics</h5>
            <div class="text-muted" style="font-size: 1rem;">Sales Forecasting &amp; Inventory Management</div>
        </div>
        <form method="POST" action="/llogin">
            @csrf
            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="admin@company.com" required>
            </div>
            <div class="form-group mb-4">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-dark w-100" style="font-weight: 500;">
                Sign In
            </button>
            <div class="text-center mt-3">
                <span class="text-muted">Don't have an account?</span>
                <a href="/register" class="ml-1">Register</a>
            </div>
        </form>
    </div>
</div>
@endsection