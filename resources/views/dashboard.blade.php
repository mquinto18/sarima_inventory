@extends('layouts.app')

@section('content')
<style>
    .dashboard-card .status-excellent { color: var(--color-success); font-weight: 600; }
    .dashboard-card .status-good { color: var(--color-info); font-weight: 600; }
    .dashboard-card .status-fair { color: var(--color-warning); font-weight: 600; }
    .dashboard-card .status-pending { color: var(--color-text-muted); font-weight: 600; }
    .trend-up { color: var(--color-success); font-weight: 600; font-size: 1.02rem; }
    .trend-down { color: var(--color-danger); font-weight: 600; font-size: 1.02rem; }

    /* Two-column dashboard body: wide main column for tables, narrow aside for ranked lists */
    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
        gap: 28px;
        align-items: start;
    }

    .dashboard-main,
    .dashboard-aside {
        display: flex;
        flex-direction: column;
        gap: 28px;
        min-width: 0;
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--color-text);
    }

    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .section-icon svg { width: 17px; height: 17px; }
    .section-icon--success { background: var(--color-success-bg); color: var(--color-success-text); }
    .section-icon--warning { background: var(--color-warning-bg); color: var(--color-warning-text); }
    .section-icon--info { background: var(--color-info-bg); color: var(--color-info-text); }

    .section-link {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--color-primary);
        text-decoration: none;
        white-space: nowrap;
    }

    .section-link:hover { text-decoration: underline; }

    .section-empty {
        color: var(--color-text-muted);
        font-size: 0.95rem;
        padding: 8px 0 4px;
    }

    .table-scroll { overflow-x: auto; }
    .table-scroll .data-table { margin-bottom: 0; }
    .table-scroll .data-table tbody tr:last-child td { border-bottom: none; }

    /* Ranked mini-list used by the Fast/Slow-Moving widgets */
    .mover-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .mover-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mover-rank {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .mover-rank--success { background: var(--color-success-bg); color: var(--color-success-text); }
    .mover-rank--warning { background: var(--color-warning-bg); color: var(--color-warning-text); }

    .mover-body { flex: 1; min-width: 0; }

    .mover-name {
        font-weight: 600;
        color: var(--color-text);
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 5px;
    }

    .mover-bar-track {
        height: 5px;
        border-radius: var(--radius-pill);
        background: var(--color-neutral-bg);
        overflow: hidden;
    }

    .mover-bar-fill {
        height: 100%;
        border-radius: var(--radius-pill);
        transition: width 0.4s ease;
    }

    .mover-bar-fill--success { background: var(--color-success); }
    .mover-bar-fill--warning { background: var(--color-warning); }

    .mover-metric { flex-shrink: 0; white-space: nowrap; }

    @media (max-width: 1100px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="page-shell" style="position: relative; overflow: hidden;">
    <!-- Large Logo Watermark Background -->
    <div style="position: absolute; right: 32px; bottom: 32px; z-index: 0; pointer-events: none;">
        <img src="{{ asset('images/logo-icon.png') }}" alt="" width="220" height="220" style="opacity: 0.12;">
    </div>
    <div style="position: relative; z-index: 1;">
        <div class="dashboard-hero">
            <div>
                <div class="dashboard-hero-greeting">Welcome back, {{ explode(' ', Auth::user()->name)[0] }} 👋</div>
                <div class="dashboard-hero-sub">Here's what's happening with Larios Pharmacy today.</div>
            </div>
            <div class="dashboard-hero-actions">
                <a href="/inventory" class="btn-action edit">View Inventory</a>
                @if(Auth::user()->role !== 'staff')
                    <a href="/forecasting" class="btn-action ghost">View Forecast</a>
                @endif
            </div>
        </div>
        <div class="page-header">
            <div class="page-title">Dashboard Overview</div>
            <div class="page-subtitle">Key metrics and system status</div>
        </div>
        <div class="stat-grid">
            <!-- Monthly Revenue -->
            <div class="stat-card" tabindex="0">
                <div class="stat-icon stat-icon--success">
                    <span class="currency-icon">₱</span>
                </div>
                <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Monthly Revenue</div>
                <div class="main">&#8369;{{ number_format($monthlyRevenue['current'], 2) }}</div>
                @if($monthlyRevenue['change_direction'] === 'increase')
                    <div class="trend-up">↑ {{ number_format($monthlyRevenue['change_percentage'], 1) }}% vs last month</div>
                @else
                    <div class="trend-down">↓ {{ number_format($monthlyRevenue['change_percentage'], 1) }}% vs last month</div>
                @endif
            </div>
            <!-- Total Products -->
            <div class="stat-card" tabindex="0">
                <div class="stat-icon stat-icon--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="7" width="18" height="13" rx="2" />
                        <path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Products</div>
                <div class="main">{{ $totalProducts }}</div>
                <div class="sub">in inventory</div>
            </div>
            <!-- Reorder Alerts -->
            <div class="stat-card" tabindex="0">
                <div class="stat-icon stat-icon--danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Reorder Alerts</div>
                <div class="main">{{ $dynamicReorderCount }}</div>
                <div class="sub">products need reorder</div>
            </div>
            <!-- Forecast Accuracy -->
            @php
                $statusClass = 'status-pending';
                if ($forecastAccuracy['accuracy_percentage'] >= 95) {
                    $statusClass = 'status-excellent';
                } elseif ($forecastAccuracy['accuracy_percentage'] >= 85) {
                    $statusClass = 'status-good';
                } elseif ($forecastAccuracy['accuracy_percentage'] > 0) {
                    $statusClass = 'status-fair';
                }
            @endphp
            <div class="stat-card" tabindex="0">
                <div class="stat-icon" style="background: #f3e8ff; color: #a21caf;">
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
                <div class="sub {{ $statusClass }}">{{ $forecastAccuracy['status'] }}</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-main">
                <!-- Expiring Soon -->
                <div class="card-panel">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="section-icon section-icon--warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            Expiring Soon
                        </div>
                        @if($expiringCount > 0)
                            <span class="status-badge status-badge--warning">{{ $expiringCount }} product{{ $expiringCount === 1 ? '' : 's' }}</span>
                        @endif
                    </div>
                    @if($expiringProducts->isEmpty())
                        <div class="section-empty">No products are expiring soon.</div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expiringProducts as $product)
                                        @php
                                            $daysUntilExpiry = now()->startOfDay()->diffInDays($product->expiry_date, false);
                                            $isExpired = $daysUntilExpiry < 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->stock }}</td>
                                            <td>{{ $product->expiry_date->format('M d, Y') }}</td>
                                            <td>
                                                @if($isExpired)
                                                    <span class="status-badge critical">Expired</span>
                                                @else
                                                    <span class="status-badge low">Expires in {{ $daysUntilExpiry }} day{{ $daysUntilExpiry === 1 ? '' : 's' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($isExpired && $product->stock > 0)
                                                    <div style="display:flex; gap:6px; align-items:center;">
                                                        <input type="number" class="dispose-qty-input" min="1" max="{{ $product->stock }}" value="{{ $product->stock }}" style="width:64px; padding:6px 8px; border-radius:var(--radius-sm); border:1.5px solid #e5e7eb;">
                                                        <button type="button" class="btn-action reject dispose-expired-btn" data-id="{{ $product->id }}" data-name="{{ $product->name }}">Write Off</button>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Upcoming Deliveries -->
                <div class="card-panel">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="section-icon section-icon--info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="7" width="13" height="10" rx="1" />
                                    <path d="M14 10h4l3 3v4h-2" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="6" cy="19" r="2" />
                                    <circle cx="17" cy="19" r="2" />
                                </svg>
                            </span>
                            Upcoming Deliveries
                        </div>
                    </div>
                    @if($upcomingDeliveries->isEmpty())
                        <div class="section-empty">No deliveries currently expected.</div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Supplier</th>
                                        <th>Expected Date</th>
                                        <th>Status</th>
                                        <th>Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingDeliveries as $po)
                                        <tr>
                                            <td>
                                                {{ $po->po_number }}
                                                @if($po->is_auto_generated)
                                                    <span class="status-badge status-badge--info">Auto</span>
                                                @endif
                                            </td>
                                            <td>{{ $po->supplier->name ?? '—' }}</td>
                                            <td>{{ optional($po->expected_delivery_date)->format('M d, Y') }}</td>
                                            <td>
                                                @if($po->status === 'confirmed')
                                                    <span class="status-badge status-badge--success">Confirmed</span>
                                                @else
                                                    <span class="status-badge status-badge--info">{{ ucfirst($po->status) }}</span>
                                                @endif
                                            </td>
                                            <td>₱{{ number_format($po->total_value, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="dashboard-aside">
                <!-- Fast-Moving Products -->
                <div class="card-panel">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="section-icon section-icon--success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 17 9 11 13 15 21 7" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="21" cy="7" r="1.5" fill="currentColor" stroke="none" />
                                </svg>
                            </span>
                            Fast-Moving
                        </div>
                        <a href="/analytics" class="section-link">View all &rarr;</a>
                    </div>
                    @if($fastMovingProducts->isEmpty())
                        <div class="section-empty">No products currently meet the fast-moving threshold.</div>
                    @else
                        @php $maxFast = $fastMovingProducts->max('units_per_week') ?: 1; @endphp
                        <div class="mover-list">
                            @foreach($fastMovingProducts as $i => $product)
                                <div class="mover-item">
                                    <div class="mover-rank mover-rank--success">{{ $i + 1 }}</div>
                                    <div class="mover-body">
                                        <div class="mover-name">{{ $product->name }}</div>
                                        <div class="mover-bar-track">
                                            <div class="mover-bar-fill mover-bar-fill--success" style="width: {{ min(100, round($product->units_per_week / $maxFast * 100)) }}%;"></div>
                                        </div>
                                    </div>
                                    <span class="mover-metric status-badge status-badge--success">{{ $product->units_per_week }}/wk</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Slow-Moving Products -->
                <div class="card-panel">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="section-icon section-icon--warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 7 9 13 13 9 21 17" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="21" cy="17" r="1.5" fill="currentColor" stroke="none" />
                                </svg>
                            </span>
                            Slow-Moving
                        </div>
                        <a href="/analytics" class="section-link">View all &rarr;</a>
                    </div>
                    @if($slowMovingProducts->isEmpty())
                        <div class="section-empty">No slow-moving products detected.</div>
                    @else
                        @php $maxSlow = $slowMovingProducts->max('units_per_week') ?: 1; @endphp
                        <div class="mover-list">
                            @foreach($slowMovingProducts as $i => $product)
                                <div class="mover-item">
                                    <div class="mover-rank mover-rank--warning">{{ $i + 1 }}</div>
                                    <div class="mover-body">
                                        <div class="mover-name">{{ $product->name }}</div>
                                        <div class="mover-bar-track">
                                            <div class="mover-bar-fill mover-bar-fill--warning" style="width: {{ min(100, round($product->units_per_week / $maxSlow * 100)) }}%;"></div>
                                        </div>
                                    </div>
                                    <span class="mover-metric status-badge status-badge--warning">{{ $product->units_per_week }}/wk</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (e) {
        var button = e.target.closest('.dispose-expired-btn');
        if (!button) return;

        var name = button.dataset.name;
        var id = button.dataset.id;
        var input = button.parentElement.querySelector('.dispose-qty-input');
        var quantity = parseInt(input.value, 10);

        if (!quantity || quantity < 1) {
            showToast('Enter a valid quantity to write off.', 'error');
            return;
        }

        confirmDialog('Write off ' + quantity + ' unit(s) of "' + name + '" as expired? Stock will be removed immediately.', {
            title: 'Write off expired stock',
            confirmText: 'Write Off'
        }).then(function (confirmed) {
            if (!confirmed) return;
            setButtonLoading(button, true, 'Writing off...');

            fetch('/products/' + id + '/dispose-expired', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ quantity: quantity })
            })
                .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                .then(function (result) {
                    if (result.data.success) {
                        showToast(result.data.message || 'Expired stock written off successfully!', 'success');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        setButtonLoading(button, false);
                        showToast(result.data.message || 'Could not write off this stock.', 'error');
                    }
                })
                .catch(function () {
                    setButtonLoading(button, false);
                    showToast('Could not write off this stock.', 'error');
                });
        });
    });
</script>
@endsection
