@extends('layouts.app')

@section('content')
<div class="page-shell">
    @include('pages.logs._header', [
        'active' => 'pos',
        'subtitle' => 'Completed point-of-sale transactions, newest first.',
    ])

    <div class="data-table-container">
        <div style="font-weight: 700; font-size: 1.15rem; color: var(--color-text); padding: 18px 24px 16px;">
            POS Sales
            <span class="status-badge" style="margin-left: 8px;">{{ number_format($transactions->total()) }}</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Transaction</th>
                        <th>Cashier</th>
                        <th style="text-align: right;">Items</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Received</th>
                        <th style="text-align: right;">Change</th>
                        <th style="text-align: center;">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            {{-- occurred_at is stored in UTC; shown in Asia/Manila so it matches the calendar day actually picked in the From/To filter above. --}}
                            <td>{{ \Carbon\Carbon::parse($txn->occurred_at, 'UTC')->setTimezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                            <td class="log-mono">{{ strtoupper(substr($txn->pos_transaction_id, 0, 8)) }}</td>
                            <td>
                                {{-- Null for the transactions recorded before the
                                     POS captured a cashier. --}}
                                {{ $cashiers[$txn->user_id]->name ?? '—' }}
                            </td>
                            <td style="text-align: right;">
                                {{ number_format($txn->units) }}
                                <span style="color: var(--color-text-muted); font-size: 0.85rem;">
                                    ({{ $txn->line_count }} {{ $txn->line_count == 1 ? 'line' : 'lines' }})
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 600;">&#8369;{{ number_format($txn->total_amount, 2) }}</td>
                            <td style="text-align: right;">
                                {{ is_null($txn->amount_tendered) ? '—' : '₱' . number_format($txn->amount_tendered, 2) }}
                            </td>
                            <td style="text-align: right;">
                                {{ is_null($txn->change_due) ? '—' : '₱' . number_format($txn->change_due, 2) }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('pos.receipt', $txn->pos_transaction_id) }}" target="_blank"
                                    rel="noopener" class="btn-action ghost" style="padding: 5px 14px; font-size: 0.85rem;">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="log-empty">
                                No POS transactions{{ $from || $to ? ' in this date range' : ' recorded yet' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding: 0 24px 18px;">
            {{ $transactions->links('components.pagination') }}
        </div>
    </div>
</div>
@endsection
