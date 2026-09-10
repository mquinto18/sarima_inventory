@extends('layouts.app')

@section('content')
<div class="page-shell">
    @include('pages.logs._header', [
        'active' => 'deliveries',
        'subtitle' => 'Every delivery received against a supplier purchase order.',
    ])

    <div class="data-table-container">
        <div style="font-weight: 700; font-size: 1.15rem; color: var(--color-text); padding: 18px 24px 16px;">
            Supplier Deliveries
            <span class="status-badge" style="margin-left: 8px;">{{ number_format($receipts->total()) }}</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Purchase Order</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th style="text-align: right;">Qty Received</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->received_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            <td>
                                @if($receipt->purchaseOrder)
                                    <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder->id) }}"
                                        style="color: var(--color-primary); font-weight: 600;">
                                        {{ $receipt->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    <span class="log-mono">deleted</span>
                                @endif
                            </td>
                            <td>{{ $receipt->purchaseOrder?->supplier?->name ?? '—' }}</td>
                            <td>{{ $receipt->product?->name ?? '—' }}</td>
                            <td style="text-align: right; font-weight: 600;">+{{ number_format($receipt->quantity_received) }}</td>
                            <td>
                                {{ $receipt->receivedBy?->name ?? '—' }}
                                @if($receipt->notes)
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted);">{{ $receipt->notes }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="log-empty">
                                No deliveries{{ $from || $to ? ' in this date range' : ' recorded yet' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding: 0 24px 18px;">
            {{ $receipts->links('components.pagination') }}
        </div>
    </div>
</div>
@endsection
