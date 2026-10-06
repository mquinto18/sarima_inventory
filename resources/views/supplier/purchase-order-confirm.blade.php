<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order Confirmation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f4f5f7;
            margin: 0;
            padding: 32px 16px;
            color: #1f2430;
        }
        .card {
            max-width: 520px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 32px;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 4px;
        }
        .muted {
            color: #6b7280;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .row:last-child {
            border-bottom: none;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        form {
            flex: 1;
        }
        button {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-approve {
            background: #16a34a;
            color: #fff;
        }
        .btn-decline {
            background: #fff;
            color: #dc2626;
            border: 1.5px solid #dc2626;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        @if($state === 'invalid')
            <h1>Link not found</h1>
            <p class="muted">This confirmation link is invalid or has expired. Please contact Larios Pharmacy directly if you believe this is a mistake.</p>

        @elseif($state === 'approved')
            <span class="badge badge-success">Approved</span>
            <h1 style="margin-top: 12px;">Thank you</h1>
            <p class="muted">You've confirmed PO #{{ $purchaseOrder->po_number }}. We've recorded this and the admin team has been notified.</p>

        @elseif($state === 'declined')
            <span class="badge badge-danger">Declined</span>
            <h1 style="margin-top: 12px;">Order declined</h1>
            <p class="muted">You've declined PO #{{ $purchaseOrder->po_number }}. The admin team has been notified.</p>

        @elseif($state === 'already_processed')
            @php
                $badgeClass = match($purchaseOrder->status) {
                    'confirmed', 'received', 'partially_received' => 'badge-success',
                    'cancelled' => 'badge-danger',
                    default => 'badge-neutral',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</span>
            <h1 style="margin-top: 12px;">Already processed</h1>
            <p class="muted">PO #{{ $purchaseOrder->po_number }} has already been {{ $purchaseOrder->status === 'cancelled' ? 'declined' : 'responded to' }} — no further action is needed.</p>

        @else
            @php
                // A bare GET link must only ever render this page, never
                // mutate anything - that's what keeps it safe for email
                // "Safe Links" scanners to prefetch. So clicking Approve/
                // Decline in the email always lands here first either way;
                // $action just decides whether this page asks the supplier
                // to choose again (confusing - they already chose) or asks
                // them to confirm the one choice they already made.
                $chosen = in_array($action ?? null, ['approve', 'decline']) ? $action : null;
            @endphp
            <h1>Purchase Order #{{ $purchaseOrder->po_number }}</h1>
            <p class="muted">From Larios Pharmacy{{ $chosen ? ' — one more click to confirm.' : ' — please review and confirm.' }}</p>

            <div style="margin: 20px 0;">
                @foreach($purchaseOrder->items as $item)
                    <div class="row">
                        <span>{{ $item->product->name ?? 'Item' }}</span>
                        <span>{{ $item->quantity_ordered }} × ₱{{ number_format($item->unit_cost, 2) }}</span>
                    </div>
                @endforeach
                <div class="row" style="font-weight: 700;">
                    <span>Total</span>
                    <span>₱{{ number_format($purchaseOrder->total_value, 2) }}</span>
                </div>
                <div class="row">
                    <span class="muted">Expected delivery</span>
                    <span>{{ $purchaseOrder->expected_delivery_date?->format('Y-m-d') ?? '—' }}</span>
                </div>
            </div>

            @if($chosen === 'approve')
                <form method="POST" action="{{ route('purchase-orders.confirm.approve', $purchaseOrder->confirmation_token) }}">
                    @csrf
                    <button type="submit" class="btn-approve">✅ Confirm Approval</button>
                </form>
                <p class="muted" style="text-align: center; margin-top: 14px; font-size: 0.85rem;">
                    Didn't mean to? <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'decline']) }}">Decline instead</a>
                </p>
            @elseif($chosen === 'decline')
                <form method="POST" action="{{ route('purchase-orders.confirm.decline', $purchaseOrder->confirmation_token) }}">
                    @csrf
                    <button type="submit" class="btn-decline">❌ Confirm Decline</button>
                </form>
                <p class="muted" style="text-align: center; margin-top: 14px; font-size: 0.85rem;">
                    Didn't mean to? <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'approve']) }}">Approve instead</a>
                </p>
            @else
                <div class="actions">
                    <form method="POST" action="{{ route('purchase-orders.confirm.approve', $purchaseOrder->confirmation_token) }}">
                        @csrf
                        <button type="submit" class="btn-approve">✅ Approve</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-orders.confirm.decline', $purchaseOrder->confirmation_token) }}">
                        @csrf
                        <button type="submit" class="btn-decline">❌ Decline</button>
                    </form>
                </div>
            @endif
        @endif
    </div>
</body>
</html>
