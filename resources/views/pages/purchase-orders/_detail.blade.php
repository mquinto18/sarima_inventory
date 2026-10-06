@php
    $statusClass = match($purchaseOrder->status) {
        'received' => 'status-badge active',
        'cancelled' => 'status-badge inactive',
        'draft' => 'status-badge--info',
        'sent' => 'status-badge pending',
        'confirmed' => 'status-badge--info',
        'partially_received' => 'status-badge pending',
        default => 'status-badge',
    };
    $statusLabel = ucwords(str_replace('_', ' ', $purchaseOrder->status));
    $canReceive = in_array($purchaseOrder->status, ['sent', 'confirmed', 'partially_received']);
@endphp

<div style="margin-bottom: 20px;">
    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
    @if($purchaseOrder->is_auto_generated)
        <span class="status-badge--info status-badge" style="margin-left: 8px;">Auto</span>
    @endif
</div>

<div style="display: flex; gap: 24px; margin-bottom: 24px; flex-wrap: wrap;">
    <div class="card-panel" style="flex: 1; min-width: 260px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 12px; color: var(--color-text);">Supplier</div>
        <div style="margin-bottom: 6px;"><strong>{{ $purchaseOrder->supplier->name ?? '—' }}</strong></div>
        <div style="color: var(--color-text-muted); margin-bottom: 4px;">{{ $purchaseOrder->supplier->email ?? '' }}</div>
        <div style="color: var(--color-text-muted); margin-bottom: 4px;">{{ $purchaseOrder->supplier->phone ?? '' }}</div>
        <div style="color: var(--color-text-muted);">{{ $purchaseOrder->supplier->address ?? '' }}</div>
    </div>
    <div class="card-panel" style="flex: 1; min-width: 260px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 12px; color: var(--color-text);">Order Details</div>
        <div style="margin-bottom: 6px;"><span style="color: var(--color-text-muted);">Total Value:</span> <strong>₱{{ number_format($purchaseOrder->total_value, 2) }}</strong></div>
        <div style="margin-bottom: 6px;"><span style="color: var(--color-text-muted);">Expected Delivery:</span> {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '—' }}</div>
        <div style="margin-bottom: 6px;"><span style="color: var(--color-text-muted);">Sent At:</span> {{ $purchaseOrder->sent_at ? $purchaseOrder->sent_at->format('Y-m-d H:i') : '—' }}</div>
        <div style="margin-bottom: 6px;"><span style="color: var(--color-text-muted);">Delivered At:</span> {{ $purchaseOrder->delivered_at ? $purchaseOrder->delivered_at->format('Y-m-d H:i') : '—' }}</div>
        <div><span style="color: var(--color-text-muted);">Created By:</span> {{ $purchaseOrder->creator->name ?? '—' }}</div>
    </div>
</div>

@if($purchaseOrder->status === 'draft')
    <div class="card-panel" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div style="color: var(--color-text-muted);">This purchase order hasn't been emailed to the supplier yet.</div>
        <form id="sendToSupplierForm" method="POST" action="{{ route('purchase-orders.send', $purchaseOrder->id) }}">
            @csrf
            <button type="submit" class="btn-action edit">Send to Supplier</button>
        </form>
    </div>
@endif

@if(in_array($purchaseOrder->status, ['draft', 'sent', 'confirmed']))
    <div class="card-panel" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div style="color: var(--color-text-muted);">
            Supplier responded by phone or in person, or their email link isn't reachable? Record it manually instead.
        </div>
        <div style="display: flex; gap: 10px;">
            @if($purchaseOrder->status === 'sent')
                <form id="markConfirmedForm" method="POST" action="{{ route('purchase-orders.mark-confirmed', $purchaseOrder->id) }}">
                    @csrf
                    <input type="hidden" name="note" class="mark-confirmed-note">
                    <button type="submit" class="btn-action approve">Mark as Confirmed</button>
                </form>
            @endif
            <form id="markCancelledForm" method="POST" action="{{ route('purchase-orders.mark-cancelled', $purchaseOrder->id) }}">
                @csrf
                <input type="hidden" name="note" class="mark-cancelled-note">
                <button type="submit" class="btn-action reject">Mark as Cancelled</button>
            </form>
        </div>
    </div>
@endif

@if($purchaseOrder->confirmation_note || $purchaseOrder->confirmed_at)
    <div class="card-panel" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 8px; color: var(--color-text);">
            {{ $purchaseOrder->status === 'cancelled' ? 'Supplier Response — Declined' : 'Supplier Confirmation' }}
            @if($purchaseOrder->confirmed_at)
                <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.9rem;">
                    &mdash; {{ $purchaseOrder->confirmed_at->format('M d, Y g:i A') }}
                </span>
            @endif
        </div>
        <div style="color: var(--color-text-muted); white-space: pre-line;">{{ $purchaseOrder->confirmation_note }}</div>
    </div>
@endif

@if($purchaseOrder->notes)
    <div class="card-panel" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 8px; color: var(--color-text);">Notes</div>
        <div style="color: var(--color-text-muted);">{{ $purchaseOrder->notes }}</div>
    </div>
@endif

{{-- Individual deliveries. quantity_received on the line below is only a
     running total, so this is the only place a partial delivery is visible. --}}
@php $receipts = $purchaseOrder->receipts()->with(['product', 'receivedBy'])->get(); @endphp
@if($receipts->count())
    <div class="card-panel" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 12px; color: var(--color-text);">
            Delivery History
            <span class="status-badge" style="margin-left: 6px;">{{ $receipts->count() }}</span>
        </div>
        <table class="data-table" style="margin: 0;">
            <thead>
                <tr>
                    <th>Received</th>
                    <th>Product</th>
                    <th style="text-align: right;">Qty</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipts as $receipt)
                    <tr>
                        <td>{{ $receipt->received_at?->format('M d, Y g:i A') ?? '—' }}</td>
                        <td>{{ $receipt->product?->name ?? '—' }}</td>
                        <td style="text-align: right; font-weight: 600;">+{{ number_format($receipt->quantity_received) }}</td>
                        <td>{{ $receipt->receivedBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<form id="receiveDeliveryForm" method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder->id) }}">
    @csrf
    <div class="data-table-container" style="margin-bottom: 0;">
        <div style="font-weight: 700; font-size: 1.15rem; margin-bottom: 16px; color: var(--color-text); padding-left: 24px; padding-top: 18px;">
            Line Items
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity Ordered</th>
                        <th>Quantity Received</th>
                        <th>Remaining</th>
                        <th>Unit Cost</th>
                        <th>Subtotal</th>
                        @if($canReceive)
                            <th>Receive Now</th>
                            <th>New Expiry Date</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrder->items as $item)
                        @php $remaining = $item->quantity_ordered - $item->quantity_received; @endphp
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text);">{{ $item->product->name ?? '—' }}</td>
                            <td>{{ $item->quantity_ordered }}</td>
                            <td>{{ $item->quantity_received }}</td>
                            <td>{{ $remaining }}</td>
                            <td>₱{{ number_format($item->unit_cost, 2) }}</td>
                            <td>₱{{ number_format($item->subtotal, 2) }}</td>
                            @if($canReceive)
                                <td>
                                    @if($remaining > 0)
                                        <input type="number" class="receive-qty-input" name="items[{{ $item->id }}][quantity_received]"
                                            min="0" max="{{ $remaining }}" value="{{ $remaining }}"
                                            oninput="var exp=this.closest('tr').querySelector('.receive-expiry-input'); if(exp){ exp.required = (parseInt(this.value || '0', 10) > 0); }"
                                            style="width: 90px; padding: 8px 10px; border-radius: var(--radius-sm); border: 1.5px solid #e5e7eb;">
                                    @else
                                        <span style="color: var(--color-text-muted);">Fully received</span>
                                    @endif
                                </td>
                                <td>
                                    @if($remaining > 0)
                                        <input type="date" class="receive-expiry-input" name="items[{{ $item->id }}][expiry_date]"
                                            data-item-id="{{ $item->id }}" required
                                            style="padding: 8px 10px; border-radius: var(--radius-sm); border: 1.5px solid #e5e7eb;">
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canReceive ? 8 : 6 }}" style="text-align: center; color: #aaa;">No line items on this purchase order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($canReceive)
            <div style="padding: 18px 24px; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-action edit">Receive Delivery</button>
            </div>
        @endif
    </div>
</form>
