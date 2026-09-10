@extends('layouts.app')

@section('content')
<div class="page-shell">
    @php
        $pendingCount = $editRequests->where('status', 'pending')->count();
    @endphp
    <div class="dashboard-hero">
        <div>
            <div class="dashboard-hero-greeting">Approval Requests</div>
            <div class="dashboard-hero-sub">
                {{ $editRequests->count() }} request{{ $editRequests->count() === 1 ? '' : 's' }}
                @if($pendingCount > 0)
                    — {{ $pendingCount }} pending your review.
                @else
                    — all caught up, nothing pending.
                @endif
            </div>
        </div>
    </div>

    <div class="data-table-container">
        <div style="font-weight: 700; font-size: 1.15rem; color: var(--color-text); padding: 0 24px 16px;">
            Edit Requests
        </div>

        <table class="data-table">
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
                                <span style="font-weight:500; color: var(--color-text);">
                                    {{ $request->user->name ?? 'Unknown' }}
                                </span>
                            </div>
                        </td>

                        <td style="color: var(--color-text-muted);">
                            {{ $request->created_at->format('M d, Y - H:i') }}
                        </td>

                        <td>
                            <span class="status-badge {{ $request->status }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>

                        <td style="text-align:center; white-space:nowrap;">
                            @if($request->status === 'pending')
                                <form action="{{ route('edit-requests.approve', $request->id) }}" method="POST"
                                    style="display: inline-block;">
                                    @csrf
                                    <button class="btn-action approve" type="submit">Approve</button>
                                </form>
                                <form action="{{ route('edit-requests.reject', $request->id) }}" method="POST"
                                    style="display: inline-block;">
                                    @csrf
                                    <button class="btn-action reject" type="submit">Reject</button>
                                </form>
                            @else
                                <span style="color: var(--color-text-muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding:20px; color: var(--color-text-muted);">
                            No requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>
@endsection
