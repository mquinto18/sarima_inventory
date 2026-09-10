@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .dashboard-charts {
        display: flex;
        gap: 32px;
        margin-bottom: 32px;
        flex-wrap: wrap;
    }

    .dashboard-chart-card {
        flex: 2 1 320px;
        background: var(--color-surface);
        border-radius: var(--radius-md);
        padding: 24px;
        display: flex;
        flex-direction: column;
        min-width: 320px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 0;
    }

    .dashboard-chart-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 16px;
        color: var(--color-text);
    }

    .dashboard-chart-content {
        position: relative;
        height: 260px;
    }

    .insight-pill {
        border-radius: var(--radius-sm);
        padding: 10px 16px;
        font-size: 0.98rem;
    }

    .insight-pill--info { background: var(--color-primary-soft); color: #3730a3; }
    .insight-pill--success { background: var(--color-success-bg); color: #15803d; }
</style>
<div class="page-shell">
    <div class="dashboard-hero">
        <div>
            <div class="dashboard-hero-greeting">Analytics</div>
            <div class="dashboard-hero-sub">Performance insights based on your actual sales and inventory data.</div>
        </div>
        <div class="dashboard-hero-actions">
            <a href="javascript:void(0)" data-open-report class="btn-action edit">Generate Report</a>
        </div>
    </div>
    <div class="stat-grid">
        <div class="stat-card" tabindex="0">
            <div class="stat-icon stat-icon--success">
                <span class="currency-icon">₱</span>
            </div>
            <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Sales YTD</div>
            <div class="main">₱{{ number_format($salesYtd, 2) }}</div>
            @if($salesYoyChange !== null)
                <div class="{{ $salesYoyChange >= 0 ? 'trend-up' : 'trend-down' }}" style="font-weight:600;">
                    {{ $salesYoyChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($salesYoyChange), 1) }}% vs last year
                </div>
            @else
                <div class="sub">No prior-year data yet</div>
            @endif
        </div>
        <div class="stat-card" tabindex="0">
            <div class="stat-icon stat-icon--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 17 9 11 13 15 21 7" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="21" cy="7" r="1.5" fill="currentColor" stroke="none" />
                </svg>
            </div>
            <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Forecast Accuracy</div>
            <div class="main">
                @if($forecastAccuracy['accuracy_percentage'] > 0)
                    {{ number_format($forecastAccuracy['accuracy_percentage'], 1) }}%
                @else
                    --
                @endif
            </div>
            <div class="sub">{{ $forecastAccuracy['status'] }}</div>
        </div>
        <div class="stat-card" tabindex="0">
            <div class="stat-icon" style="background: #f3e8ff; color: #a21caf;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M7 15l4-5 3 3 5-7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Inventory Turnover</div>
            <div class="main">{{ $inventoryTurnover }}</div>
            <div class="sub">units sold this month per unit on hand</div>
        </div>
    </div>
    <div class="dashboard-charts">
        <div class="dashboard-chart-card">
            <div class="dashboard-chart-title">Revenue Trend (Last 6 Months)</div>
            <div class="dashboard-chart-content">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
        <div class="dashboard-chart-card">
            <div class="dashboard-chart-title">Revenue by Category (This Year)</div>
            <div class="dashboard-chart-content">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Key Metrics Table -->
    <div class="card-panel" style="margin-bottom: 32px;">
        <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: var(--color-text);">Key Metrics</div>
        <div class="data-table-container" style="box-shadow: none; margin-bottom: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>This Month</th>
                        <th>Last Month</th>
                        <th>Change</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Revenue</td>
                        <td>₱{{ number_format($revenueThisMonth, 2) }}</td>
                        <td>₱{{ number_format($revenueLastMonth, 2) }}</td>
                        <td>
                            @if($revenueChange !== null)
                                <span class="{{ $revenueChange >= 0 ? 'trend-up' : 'trend-down' }}">{{ $revenueChange >= 0 ? '+' : '' }}{{ $revenueChange }}%</span>
                            @else
                                <span class="sub">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($revenueChange === null)
                                <span class="status-badge status-badge--info">New</span>
                            @elseif($revenueChange >= 0)
                                <span class="status-badge status-badge--success">Growing</span>
                            @else
                                <span class="status-badge status-badge--warning">Declining</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Forecast Accuracy</td>
                        <td colspan="2">{{ $forecastAccuracy['accuracy_percentage'] > 0 ? number_format($forecastAccuracy['accuracy_percentage'], 1) . '%' : 'Pending data' }}</td>
                        <td class="sub">—</td>
                        <td>
                            @if($forecastAccuracy['accuracy_percentage'] >= 85)
                                <span class="status-badge status-badge--success">{{ $forecastAccuracy['status'] }}</span>
                            @elseif($forecastAccuracy['accuracy_percentage'] > 0)
                                <span class="status-badge status-badge--warning">{{ $forecastAccuracy['status'] }}</span>
                            @else
                                <span class="status-badge status-badge--info">{{ $forecastAccuracy['status'] }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Inventory Turnover</td>
                        <td>{{ $inventoryTurnover }}</td>
                        <td>{{ $inventoryTurnoverLastMonth }}</td>
                        <td>
                            @php $turnoverDelta = $inventoryTurnover - $inventoryTurnoverLastMonth; @endphp
                            <span class="{{ $turnoverDelta >= 0 ? 'trend-up' : 'trend-down' }}">{{ $turnoverDelta >= 0 ? '+' : '' }}{{ round($turnoverDelta, 2) }}</span>
                        </td>
                        <td><span class="status-badge status-badge--info">Tracked</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display: flex; gap: 32px; flex-wrap: wrap;">
        <!-- Insights -->
        <div class="card-panel" style="flex: 1; min-width: 280px;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: var(--color-text);">Insights</div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($insights as $insight)
                    <div class="insight-pill insight-pill--info">{{ $insight }}</div>
                @endforeach
            </div>
        </div>
        <!-- Recommendations -->
        <div class="card-panel" style="flex: 1; min-width: 280px;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: var(--color-text);">Recommendations</div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($recommendations as $recommendation)
                    <div class="insight-pill insight-pill--success">{{ $recommendation }}</div>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 32px; flex-wrap: wrap; margin-top: 32px;">
        <!-- Fast-Moving Products -->
        <div class="card-panel" style="flex: 1; min-width: 320px;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: var(--color-text);">Fast-Moving Products</div>
            @if($fastMovingProducts->isEmpty())
                <div style="color: var(--color-text-muted);">No products currently meet the fast-moving threshold.</div>
            @else
                <div class="data-table-container" style="box-shadow: none; margin-bottom: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Units / Week</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fastMovingProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->units_sold }}</td>
                                    <td><span class="status-badge status-badge--success">{{ $product->units_per_week }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <!-- Slow-Moving Products -->
        <div class="card-panel" style="flex: 1; min-width: 320px;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: var(--color-text);">Slow-Moving Products</div>
            @if($slowMovingProducts->isEmpty())
                <div style="color: var(--color-text-muted);">No slow-moving products detected.</div>
            @else
                <div class="data-table-container" style="box-shadow: none; margin-bottom: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Units / Week</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($slowMovingProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->units_sold }}</td>
                                    <td><span class="status-badge status-badge--warning">{{ $product->units_per_week }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trendCtx = document.getElementById('revenueTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json($trendMonths),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($trendRevenue),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => '₱' + new Intl.NumberFormat('en-PH', { notation: 'compact' }).format(v) } }
                    }
                }
            });
        }

        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($categoryRevenue->pluck('category')),
                    datasets: [{
                        data: @json($categoryRevenue->pluck('total')),
                        backgroundColor: ['#6366f1', '#16a34a', '#f59e0b', '#a21caf', '#2563eb'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    });
</script>
@include('components.report-drawer')

@endsection
