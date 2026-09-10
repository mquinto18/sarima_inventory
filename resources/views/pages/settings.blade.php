@extends('layouts.app')

@section('content')
<div class="page-shell">
    <div class="dashboard-hero">
        <div>
            <div class="dashboard-hero-greeting">System Settings</div>
            <div class="dashboard-hero-sub">Configure stock alert thresholds and forecasting behavior.</div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        <div style="display: flex; gap: 32px; margin-bottom: 32px; flex-wrap: wrap;">
            <!-- Inventory Alerts -->
            <div class="card-panel" style="flex: 1; min-width: 280px;">
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--color-text);">Inventory Alerts</div>
                <div style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 20px;">Controls the "Critical" / "Low Stock" / "In Stock" status shown across the app.</div>

                <label for="critical_stock_level" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Critical Stock Level</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="0" name="critical_stock_level" id="critical_stock_level"
                        value="{{ old('critical_stock_level', $settings['critical_stock_level']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: -12px; margin-bottom: 18px;">Products at or below this stock quantity are flagged "Critical".</div>

                <label for="low_stock_threshold" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Low Stock Threshold</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="0" name="low_stock_threshold" id="low_stock_threshold"
                        value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 8px;">Products at or below this quantity (but above Critical) are flagged "Low Stock".</div>
            </div>

            <!-- Forecasting Configuration -->
            <div class="card-panel" style="flex: 1; min-width: 280px;">
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--color-text);">Forecasting Configuration</div>
                <div style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 20px;">Controls how many months ahead the SARIMA model forecasts on the Forecasting page.</div>

                <label for="default_forecast_period" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Default Forecast Period (months)</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="1" max="24" name="default_forecast_period" id="default_forecast_period"
                        value="{{ old('default_forecast_period', $settings['default_forecast_period']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 8px;">Between 1 and 24 months.</div>
            </div>
        </div>

        <div style="display: flex; gap: 32px; margin-bottom: 32px; flex-wrap: wrap;">
            <!-- Automation & Alerts -->
            <div class="card-panel" style="flex: 1; min-width: 280px;">
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--color-text);">Automation & Alerts</div>
                <div style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 20px;">Controls the dashboard's expiry / fast-and-slow-moving widgets, and the automated reordering (STP) feature.</div>

                <label for="expiry_alert_days" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Expiry Alert Window (days)</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="1" name="expiry_alert_days" id="expiry_alert_days"
                        value="{{ old('expiry_alert_days', $settings['expiry_alert_days']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: -12px; margin-bottom: 18px;">Products expiring within this many days appear on the "Expiring Soon" dashboard widget.</div>

                <label for="fast_moving_threshold" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Fast-Moving Threshold (units/week)</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 17 9 11 13 15 21 7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="0" step="0.1" name="fast_moving_threshold" id="fast_moving_threshold"
                        value="{{ old('fast_moving_threshold', $settings['fast_moving_threshold']) }}" required>
                </div>

                <label for="slow_moving_threshold" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Slow-Moving Threshold (units/week)</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 7 9 13 13 9 21 17" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="0" step="0.1" name="slow_moving_threshold" id="slow_moving_threshold"
                        value="{{ old('slow_moving_threshold', $settings['slow_moving_threshold']) }}" required>
                </div>

                <label for="velocity_window_days" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Velocity Window (days)</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="7" max="180" name="velocity_window_days" id="velocity_window_days"
                        value="{{ old('velocity_window_days', $settings['velocity_window_days']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 8px;">Trailing period (7–180 days) used to compute product sales velocity.</div>
            </div>

            <!-- Automated Ordering (STP) -->
            <div class="card-panel" style="flex: 1; min-width: 280px;">
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--color-text);">Automated Ordering (STP)</div>
                <div style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 20px;">Straight-through processing limits for automatically generated purchase orders.</div>

                <label style="display:flex; align-items:center; gap: 10px; font-weight:600; margin-bottom:18px; color: var(--color-text); cursor: pointer;">
                    <input type="hidden" name="stp_enabled" value="0">
                    <input type="checkbox" name="stp_enabled" id="stp_enabled" value="1" style="width:18px; height:18px;"
                        {{ old('stp_enabled', $settings['stp_enabled']) ? 'checked' : '' }}>
                    Enable automated ordering
                </label>

                <label for="stp_max_order_value" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Max Auto-Order Value (₱)</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <span class="form-input-icon currency-icon">₱</span>
                    <input type="number" min="0" step="0.01" name="stp_max_order_value" id="stp_max_order_value"
                        value="{{ old('stp_max_order_value', $settings['stp_max_order_value']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: -12px; margin-bottom: 18px;">Purchase orders above this total value require manual approval.</div>

                <label for="stp_max_qty_per_product" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Max Auto-Order Quantity per Product</label>
                <div class="form-input-group" style="margin-bottom: 18px;">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="7" width="18" height="13" rx="2" />
                        <path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="1" name="stp_max_qty_per_product" id="stp_max_qty_per_product"
                        value="{{ old('stp_max_qty_per_product', $settings['stp_max_qty_per_product']) }}" required>
                </div>

                <label for="default_lead_time_days" style="display:block; font-weight:600; margin-bottom:7px; color: var(--color-text);">Default Supplier Lead Time (days)</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="number" min="1" name="default_lead_time_days" id="default_lead_time_days"
                        value="{{ old('default_lead_time_days', $settings['default_lead_time_days']) }}" required>
                </div>
                <div style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 8px;">Used when a product has no supplier-specific lead time configured.</div>
            </div>
        </div>

        <button type="submit" class="btn-action edit" style="padding: 13px 32px; font-size: 1.05rem;">Save Settings</button>
    </form>

    {{-- Archived accounts. Admin-only: managers can open Settings, but user
         administration is gated to admins everywhere else too. --}}
    @if(Auth::user()->role === 'admin')
        <div class="card-panel" style="margin-top: 32px;">
            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--color-text);">
                Archived Users
                @if($archivedUsers->count() > 0)
                    <span class="status-badge" style="margin-left: 8px;">{{ $archivedUsers->count() }}</span>
                @endif
            </div>
            <div style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 20px;">
                Accounts archived from Account Management. They cannot sign in, but their
                edit requests, stock movements and purchase orders are kept intact.
                Restoring re-enables sign-in immediately.
            </div>

            @if($archivedUsers->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Archived</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($archivedUsers as $archived)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 14px;">
                                            <span class="user-avatar">{{ strtoupper(substr($archived->name, 0, 1)) }}</span>
                                            <span style="font-weight: 500; color: var(--color-text);">{{ $archived->name }}</span>
                                        </div>
                                    </td>
                                    <td style="color: var(--color-text-muted);">{{ $archived->email }}</td>
                                    <td><span class="role-{{ $archived->role }}">{{ ucfirst($archived->role) }}</span></td>
                                    <td style="color: var(--color-text-muted);">
                                        {{ $archived->deleted_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="{{ route('users.restore', $archived->id) }}"
                                            style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn-action edit restore-user-btn"
                                                data-name="{{ $archived->name }}">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="color: var(--color-text-muted); padding: 8px 0;">No archived accounts.</div>
            @endif
        </div>
    @endif
</div>

@if(Auth::user()->role === 'admin')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.restore-user-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var form = btn.closest('form');
                    confirmDialog('Restore "' + btn.dataset.name + '"? They will be able to sign in again.', {
                        title: 'Restore account',
                        confirmText: 'Restore',
                        danger: false
                    }).then(function (confirmed) {
                        if (!confirmed) return;
                        setButtonLoading(btn, true, 'Restoring...');
                        form.submit();
                    });
                });
            });
        });
    </script>
@endif
@endsection
