<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SARIMA Inventory')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
    .dashboard-gradient {
        background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
        min-height: 100vh;
    }
    .dashboard-title {
        font-size: 2.3rem;
        font-weight: 800;
        margin-bottom: 0.2em;
        letter-spacing: -1px;
        color: #18181b;
    }
    .dashboard-subtitle {
        color: #6366f1;
        font-size: 1.1rem;
        margin-bottom: 2.5rem;
        font-weight: 500;
    }
    .modern-table-container {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 12px 0 rgba(99,102,241,0.08);
        padding: 24px 0 0 0;
        margin-bottom: 32px;
        overflow: hidden;
    }
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 12px 0 rgba(99,102,241,0.06);
    }
    .modern-table th, .modern-table td {
        padding: 16px 18px;
        font-size: 1.08rem;
        text-align: left;
        border-bottom: 1.5px solid #f3f4f6;
    }
    .modern-table thead th {
        background: #f3f4f6;
        color: #6366f1;
        font-weight: 700;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .modern-table tbody tr:hover {
        background: #eef2ff;
        box-shadow: 0 4px 18px 0 rgba(99,102,241,0.10);
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.90rem;
        font-weight: 600;
    }
    .status-badge.pending { background: #fef9c3; color: #92400e; }
    .status-badge.approved { background: #e0fce6; color: #166534; }
    .status-badge.rejected { background: #fee2e2; color: #991b1b; }
    .action-btn {
        font-weight: 600;
        border-radius: 12px;
        padding: 8px 20px;
        font-size: 1rem;
        margin: 0 4px;
        border: none;
        box-shadow: 0 2px 8px 0 rgba(99,102,241,0.10);
        transition: background 0.2s, box-shadow 0.2s;
    }
    .action-btn.approve {
        background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%);
        color: #fff;
    }
    .action-btn.approve:hover {
        background: linear-gradient(90deg, #4338ca 0%, #6366f1 100%);
    }
    .action-btn.reject {
        background: linear-gradient(90deg, #ef4444 0%, #dc3545 100%);
        color: #fff;
    }
    .action-btn.reject:hover {
        background: linear-gradient(90deg, #b91c1c 0%, #ef4444 100%);
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e0e7ff 60%, #6366f1 100%);
        color: #4338ca;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.25rem;
        box-shadow: 0 2px 8px 0 rgba(99,102,241,0.08);
    }
    </style>
</head>

<body>
    @if (!Request::is('login'))
        @include('components.sidebar')
        <main class="py-4" style="margin-left:220px; margin-top:70px;">
            @yield('content')
        </main>
    @else
        <main class="py-4">
            @yield('content')
        </main>
    @endif
</body>

</html>