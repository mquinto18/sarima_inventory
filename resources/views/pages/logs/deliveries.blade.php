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
            <span class="status-badge" style="margin-left: 8px;">{{ number_format($batches->total()) }}</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Purchase Order</th>
                        <th>Supplier</th>
                        <th>Products</th>
                        <th style="text-align: right;">Total Qty</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- One row per delivery batch (everything received together
                         in a single "Receive Delivery" submission), not one row
                         per product - see TransactionLogController::deliveries(). --}}
                    @forelse($batches as $batch)
                        @php
                            $batchLines = $lines->get($batch->purchase_order_id . '|' . $batch->received_at . '|' . $batch->received_by, collect());
                            $first = $batchLines->first();
                            $productsPayload = $batchLines->map(fn ($line) => [
                                'name' => $line->product?->name ?? '—',
                                'qty' => $line->quantity_received,
                                'notes' => $line->notes,
                            ])->values();
                        @endphp
                        <tr>
                            {{-- Stored in UTC; shown in Asia/Manila so it matches the calendar day actually picked in the From/To filter above. --}}
                            <td>{{ $batch->received_at?->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') ?? '—' }}</td>
                            <td>
                                @if($first?->purchaseOrder)
                                    <a href="{{ route('purchase-orders.show', $first->purchaseOrder->id) }}"
                                        style="color: var(--color-primary); font-weight: 600;">
                                        {{ $first->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    <span class="log-mono">deleted</span>
                                @endif
                            </td>
                            <td>{{ $first?->purchaseOrder?->supplier?->name ?? '—' }}</td>
                            <td>
                                <button type="button" class="btn-action ghost view-products-btn" style="padding: 5px 14px; font-size: 0.85rem;"
                                    data-po="{{ $first?->purchaseOrder?->po_number ?? '—' }}"
                                    data-products="{{ json_encode($productsPayload) }}">
                                    View ({{ $batchLines->count() }} {{ $batchLines->count() == 1 ? 'product' : 'products' }})
                                </button>
                            </td>
                            <td style="text-align: right; font-weight: 600;">+{{ number_format($batch->total_quantity) }}</td>
                            <td>{{ $first?->receivedBy?->name ?? '—' }}</td>
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
            {{ $batches->links('components.pagination') }}
        </div>
    </div>
</div>

<div id="productListModal" class="modal-overlay" style="display: none;">
    <div class="modal-card" style="position: relative; margin: 10% auto; width: 90%; max-width: 420px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h5 id="productListModalTitle" style="margin: 0; font-weight: 700; font-size: 1.05rem; color: var(--color-primary);">Products</h5>
            <button type="button" id="closeProductListModal" aria-label="Close"
                style="background: none; border: none; font-size: 26px; cursor: pointer; color: var(--color-primary);">&times;</button>
        </div>
        <div style="padding: 20px 24px;">
            <ul id="productListModalBody" style="margin: 0; padding-left: 18px;"></ul>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('productListModal');
        var body = document.getElementById('productListModalBody');
        var title = document.getElementById('productListModalTitle');

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function openModal(btn) {
            var products = JSON.parse(btn.dataset.products || '[]');
            title.textContent = 'Products — ' + (btn.dataset.po || '');
            body.innerHTML = products.map(function (p) {
                var notes = p.notes
                    ? '<div style="font-size: 0.8rem; color: var(--color-text-muted);">' + escapeHtml(p.notes) + '</div>'
                    : '';
                return '<li style="margin-bottom: 6px;">' + escapeHtml(p.name) +
                    ' <span style="color: var(--color-text-muted);">×' + Number(p.qty).toLocaleString() + '</span>' +
                    notes + '</li>';
            }).join('');
            modal.style.display = 'block';
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.view-products-btn');
            if (btn) {
                openModal(btn);
                return;
            }

            if (e.target === modal || e.target.id === 'closeProductListModal') {
                modal.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') modal.style.display = 'none';
        });
    })();
</script>
@endsection
