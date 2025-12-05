@extends('layouts.app')
@include('components.topheader')
@include('components.sidebar')
@section('content')


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

/* STATUS BADGES */
.status-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.90rem;
    font-weight: 600;
}
.status-badge.pending { background: #fef9c3; color: #92400e; }
.status-badge.approved { background: #e0fce6; color: #166534; }
.status-badge.rejected { background: #fee2e2; color: #991b1b; }

/* ACTION BUTTONS — same as Account Management */
.action-btn {
    font-weight: 600;
    border-radius: 12px;
    padding: 8px 20px;
    font-size: 1rem;
    margin: 0 4px;
    border: none;
    box-shadow: 0 2px 8px rgba(99,102,241,0.10);
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

/* Avatar (same as Account Management Users) */
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
    box-shadow: 0 2px 8px rgba(99,102,241,0.08);
}
</style>

<div class="dashboard-gradient" style="padding: 40px;">
    <div class="dashboard-title">Approval Requests</div>
    <div class="dashboard-subtitle">Staff edit requests pending admin review</div>

    <div class="modern-table-container">
        <div class="modern-table-header" style="font-weight: 700; font-size: 1.15rem; color: #23272f;">
            Edit Requests
        </div>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Staff</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($editRequests as $request)
                <tr>
                    <td>{{ $loop->iteration }}</td>

                    <td>
                        <div style="display:flex; align-items:center; gap:14px;">
                            <span class="user-avatar">
                                {{ strtoupper(substr($request->user->name ?? 'U', 0, 1)) }}
                            </span>
                            <span style="font-weight:500; color:#23272f;">
                                {{ $request->user->name ?? 'Unknown' }}
                            </span>
                        </div>
                    </td>

                    <td style="color:#6b7280;">
                        {{ $request->created_at->format('M d, Y - H:i') }}
                    </td>

                    <td>
                        <span class="status-badge {{ $request->status }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>

                    <td style="text-align:center; white-space:nowrap;">
                        @if($request->status === 'pending')
                        <form action="{{ route('edit-requests.approve', $request->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button class="action-btn approve" type="submit">Approve</button>
                        </form>
                        <form action="{{ route('edit-requests.reject', $request->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button class="action-btn reject" type="submit">Reject</button>
                        </form>
                        @else
                            <span style="color:#6b7280;">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding:20px; color:#6b7280;">
                        No requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>

@endsection
