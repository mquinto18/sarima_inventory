@php
    // dompdf has no JS and limited CSS, so everything here is plain tables and
    // inline-ish styles. Helpers keep the markup readable.
    $peso = fn ($v) => '₱' . number_format((float) $v, 2);
    $pct = fn ($v) => (($v ?? 0) >= 0 ? '+' : '') . number_format((float) ($v ?? 0), 1) . '%';
    $chart = fn ($key) => $charts[$key] ?? null;
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 32px 48px; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #18181b; }

        .cover-title { font-size: 22px; font-weight: bold; color: #4338ca; margin: 0 0 4px; }
        .cover-sub { color: #6b7280; font-size: 11px; margin: 0 0 2px; }

        .rule { border-bottom: 2px solid #6366f1; margin: 10px 0 16px; }

        h2 {
            font-size: 14px; color: #4338ca; margin: 0 0 10px;
            border-bottom: 1px solid #e0e7ff; padding-bottom: 5px;
        }

        h3 { font-size: 11px; color: #18181b; margin: 14px 0 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }

        th {
            background: #eef2ff; color: #4338ca; text-align: left;
            padding: 6px 8px; font-size: 9.5px; border-bottom: 1px solid #c7d2fe;
        }

        td { padding: 5px 8px; border-bottom: 1px solid #f1f1f4; font-size: 9.5px; }

        .num { text-align: right; }

        .kpi { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 14px; }
        .kpi td {
            background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px;
            padding: 9px 11px; width: 25%; vertical-align: top;
        }
        .kpi .label { color: #6b7280; font-size: 8.5px; text-transform: uppercase; }
        .kpi .value { font-size: 14px; font-weight: bold; color: #18181b; padding-top: 3px; }

        .up { color: #16a34a; }
        .down { color: #dc2626; }
        .muted { color: #6b7280; }

        .chart { width: 100%; margin: 4px 0 14px; }
        .chart-narrow { width: 58%; margin: 4px 0 14px; }

        .missing {
            background: #f8fafc; border: 1px dashed #c7d2fe; color: #6b7280;
            padding: 14px; text-align: center; font-style: italic; margin-bottom: 14px;
        }

        .note { color: #6b7280; font-size: 8.5px; margin: -6px 0 12px; }

        .page-break { page-break-before: always; }

        .footer {
            position: fixed; bottom: -30px; left: 0; right: 0;
            color: #9ca3af; font-size: 8px; border-top: 1px solid #e5e7eb; padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="footer">
        Larios Pharmacy — Sales Forecasting &amp; Inventory Report ·
        Generated {{ $generatedAt->format('M d, Y H:i') }} by {{ $generatedBy->name }}
    </div>

    {{-- ==================== COVER / SUMMARY ==================== --}}
    <div class="cover-title">Sales Forecasting &amp; Inventory Report</div>
    <div class="cover-sub">Larios Pharmacy — combined Forecasting and Analytics report</div>
    <div class="cover-sub">
        Generated {{ $generatedAt->format('F d, Y \a\t H:i') }} by {{ $generatedBy->name }} ({{ ucfirst($generatedBy->role) }})
    </div>
    <div class="rule"></div>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">Revenue YTD</div>
                <div class="value">{{ $peso($a['salesYtd'] ?? 0) }}</div>
                <div class="{{ ($a['salesYoyChange'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ $pct($a['salesYoyChange'] ?? 0) }} YoY</div>
            </td>
            <td>
                <div class="label">Revenue This Month</div>
                <div class="value">{{ $peso($a['revenueThisMonth'] ?? 0) }}</div>
                <div class="{{ ($a['revenueChange'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ $pct($a['revenueChange'] ?? 0) }} vs last</div>
            </td>
            <td>
                <div class="label">Forecast Accuracy</div>
                <div class="value">{{ number_format($f['forecastAccuracy']['accuracy_percentage'] ?? 0, 1) }}%</div>
                <div class="muted">{{ $f['forecastAccuracy']['status'] ?? '—' }}</div>
            </td>
            <td>
                <div class="label">Items To Reorder</div>
                <div class="value">{{ $f['reorderCount'] ?? 0 }}</div>
                <div class="muted">at/below reorder point</div>
            </td>
        </tr>
    </table>

    @if(!empty($a['insights']))
        <h3>Key Insights</h3>
        <table>
            @foreach($a['insights'] as $insight)
                <tr><td>• {{ $insight }}</td></tr>
            @endforeach
        </table>
    @endif

    @if(!empty($a['recommendations']))
        <h3>Recommendations</h3>
        <table>
            @foreach($a['recommendations'] as $rec)
                <tr><td>• {{ $rec }}</td></tr>
            @endforeach
        </table>
    @endif

    {{-- ==================== PART 1 — FORECASTING ==================== --}}
    <div class="page-break"></div>
    <h2>Part 1 — Forecasting</h2>

    @php $stats = $f['salesStats'] ?? []; @endphp
    <table class="kpi">
        <tr>
            <td>
                <div class="label">Current Month Revenue</div>
                <div class="value">{{ $peso($stats['current_month_revenue'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Last Month Revenue</div>
                <div class="value">{{ $peso($stats['last_month_revenue'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Growth</div>
                <div class="value {{ ($stats['growth_percentage'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    {{ $pct($stats['growth_percentage'] ?? 0) }}
                </div>
            </td>
            <td>
                <div class="label">Total Sales Records</div>
                <div class="value">{{ number_format($stats['total_sales_count'] ?? 0) }}</div>
            </td>
        </tr>
    </table>

    <h3>Actual Sales (Historical)</h3>
    @if($chart('actual_sales'))
        <img class="chart" src="{{ $chart('actual_sales') }}">
    @else
        <div class="missing">No historical sales chart available for this period.</div>
    @endif

    <h3>SARIMA Forecast</h3>
    @if($chart('forecast'))
        <img class="chart" src="{{ $chart('forecast') }}">
    @else
        <div class="missing">No forecast chart available.</div>
    @endif

    @php
        $params = $f['forecast']['model_parameters'] ?? [];
        $source = $f['forecast']['source'] ?? null;
    @endphp
    @if($params)
        <div class="note">
            Model: SARIMA({{ $params['p'] ?? '?' }},{{ $params['d'] ?? '?' }},{{ $params['q'] ?? '?' }})({{ $params['P'] ?? '?' }},{{ $params['D'] ?? '?' }},{{ $params['Q'] ?? '?' }})<sub>{{ $params['s'] ?? '?' }}</sub>
            @if($source)
                · computed by {{ $source === 'python_sarimax' ? 'Python statsmodels SARIMAX' : 'PHP fallback (trend + seasonal index)' }}
            @endif
        </div>
    @endif

    @php $predicted = $f['forecast']['predicted'] ?? []; $ci = $f['forecast']['confidence_intervals'] ?? []; @endphp
    @if(count($predicted))
        <h3>Forecast Detail</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="num">Predicted Revenue</th>
                    <th class="num">Lower Bound</th>
                    <th class="num">Upper Bound</th>
                </tr>
            </thead>
            <tbody>
                @foreach($predicted as $month => $value)
                    <tr>
                        <td>{{ $month }}</td>
                        <td class="num">{{ $peso($value) }}</td>
                        <td class="num">{{ isset($ci[$month]['lower']) ? $peso($ci[$month]['lower']) : '—' }}</td>
                        <td class="num">{{ isset($ci[$month]['upper']) ? $peso($ci[$month]['upper']) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php $acc = $f['forecastAccuracy'] ?? []; @endphp
    @if($acc)
        <h3>Forecast Accuracy</h3>
        <table>
            <thead>
                <tr><th>MAPE</th><th>MAE</th><th>RMSE</th><th>MASE</th><th>Accuracy</th><th>Products Analyzed</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($acc['mape'] ?? 0, 2) }}%</td>
                    <td>{{ number_format($acc['mae'] ?? 0, 2) }}</td>
                    <td>{{ number_format($acc['rmse'] ?? 0, 2) }}</td>
                    <td>{{ number_format($acc['mase'] ?? 0, 2) }}</td>
                    <td>{{ number_format($acc['accuracy_percentage'] ?? 0, 1) }}% ({{ $acc['status'] ?? '—' }})</td>
                    <td>{{ $acc['products_analyzed'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @php $season = $f['seasonalityAnalysis'] ?? []; @endphp
    @if($season)
        <h3>Seasonality Analysis</h3>
        <table>
            <tbody>
                <tr><td style="width:38%">Trend direction</td><td>{{ ucfirst($season['trend_direction'] ?? '—') }}</td></tr>
                <tr><td>Seasonality strength</td><td>{{ number_format($season['seasonality_strength'] ?? 0, 3) }}</td></tr>
                <tr><td>Yearly growth rate</td><td>{{ $pct($season['yearly_growth_rate'] ?? 0) }}</td></tr>
                <tr><td>Peak months</td><td>{{ implode(', ', (array) ($season['peak_months'] ?? [])) ?: '—' }}</td></tr>
                <tr><td>Low months</td><td>{{ implode(', ', (array) ($season['low_months'] ?? [])) ?: '—' }}</td></tr>
            </tbody>
        </table>
    @endif

    @php $pre = $f['preprocessedData'] ?? []; @endphp
    @if(count($pre))
        <h3>Monthly Sales History ({{ count($pre) }} months)</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="num">Revenue</th>
                    <th class="num">Units</th>
                    <th class="num">Moving Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pre as $row)
                    <tr>
                        <td>{{ $row['month'] ?? '—' }}</td>
                        <td class="num">{{ $peso($row['revenue'] ?? 0) }}</td>
                        <td class="num">{{ number_format($row['quantity'] ?? 0) }}</td>
                        <td class="num">{{ $peso($row['moving_average'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php $restock = $f['restockingRecommendations'] ?? []; @endphp
    @if(collect($restock)->flatten(1)->count())
        <h3>Restocking Recommendations</h3>
        <table>
            <thead>
                <tr><th>Category</th><th>Product</th><th class="num">Current Stock</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach(['urgent_restock' => 'Urgent', 'monitor_closely' => 'Monitor', 'overstock_risk' => 'Overstock risk', 'normal_stock' => 'Normal'] as $key => $label)
                    @foreach($restock[$key] ?? [] as $item)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $item['product_name'] ?? $item['name'] ?? '—' }}</td>
                            <td class="num">{{ $item['current_stock'] ?? '—' }}</td>
                            <td>{{ $item['recommended_action'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    @php $reorders = $f['reorderNotifications'] ?? collect(); @endphp
    @if($reorders->count())
        <h3>Products At or Below Reorder Point</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="num">Stock</th>
                    <th class="num">Reorder Point</th>
                    <th class="num">Suggested Qty</th>
                    <th>Priority</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reorders as $r)
                    <tr>
                        <td>{{ $r['name'] }}</td>
                        <td class="num">{{ number_format($r['current_stock']) }}</td>
                        <td class="num">{{ number_format($r['dynamic_reorder_level'] ?? $r['reorder_level'] ?? 0) }}</td>
                        <td class="num">{{ number_format($r['recommended_quantity'] ?? 0) }}</td>
                        <td>{{ $r['priority'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="note">
            Reorder points are computed from average demand plus a 1.65σ safety-stock
            margin over a 6-month window — not from the SARIMA model.
        </div>
    @endif

    {{-- ==================== PART 2 — ANALYTICS ==================== --}}
    <div class="page-break"></div>
    <h2>Part 2 — Analytics</h2>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">Units Sold This Month</div>
                <div class="value">{{ number_format($a['unitsSoldThisMonth'] ?? 0) }}</div>
                <div class="muted">last month {{ number_format($a['unitsSoldLastMonth'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Inventory Turnover</div>
                <div class="value">{{ number_format($a['inventoryTurnover'] ?? 0, 2) }}</div>
                <div class="muted">last month {{ number_format($a['inventoryTurnoverLastMonth'] ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="label">Revenue Last Month</div>
                <div class="value">{{ $peso($a['revenueLastMonth'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Revenue YoY Change</div>
                <div class="value {{ ($a['salesYoyChange'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ $pct($a['salesYoyChange'] ?? 0) }}</div>
            </td>
        </tr>
    </table>

    <h3>Revenue Trend</h3>
    @if($chart('revenue_trend'))
        <img class="chart" src="{{ $chart('revenue_trend') }}">
    @else
        <div class="missing">No revenue trend chart available.</div>
    @endif

    <h3>Revenue by Category</h3>
    @if($chart('category'))
        <img class="chart-narrow" src="{{ $chart('category') }}">
    @else
        <div class="missing">No category breakdown chart available.</div>
    @endif

    @php $cats = $a['categoryRevenue'] ?? collect(); @endphp
    @if($cats->count())
        <table>
            <thead>
                <tr><th>Category</th><th class="num">Revenue</th><th class="num">Share</th></tr>
            </thead>
            <tbody>
                @php $catTotal = $cats->sum('total') ?: 1; @endphp
                @foreach($cats as $c)
                    <tr>
                        <td>{{ $c->category }}</td>
                        <td class="num">{{ $peso($c->total) }}</td>
                        <td class="num">{{ number_format($c->total / $catTotal * 100, 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Month-on-Month Comparison</h3>
    <table>
        <thead>
            <tr><th>Metric</th><th class="num">This Month</th><th class="num">Last Month</th><th class="num">Change</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Revenue</td>
                <td class="num">{{ $peso($a['revenueThisMonth'] ?? 0) }}</td>
                <td class="num">{{ $peso($a['revenueLastMonth'] ?? 0) }}</td>
                <td class="num {{ ($a['revenueChange'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ $pct($a['revenueChange'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Units sold</td>
                <td class="num">{{ number_format($a['unitsSoldThisMonth'] ?? 0) }}</td>
                <td class="num">{{ number_format($a['unitsSoldLastMonth'] ?? 0) }}</td>
                <td class="num">—</td>
            </tr>
            <tr>
                <td>Inventory turnover</td>
                <td class="num">{{ number_format($a['inventoryTurnover'] ?? 0, 2) }}</td>
                <td class="num">{{ number_format($a['inventoryTurnoverLastMonth'] ?? 0, 2) }}</td>
                <td class="num">—</td>
            </tr>
        </tbody>
    </table>

    @php $fast = $a['fastMovingProducts'] ?? collect(); @endphp
    <h3>Fast-Moving Products</h3>
    @if($fast->count())
        <table>
            <thead><tr><th>Product</th><th class="num">Units Sold</th><th class="num">Units / Week</th></tr></thead>
            <tbody>
                @foreach($fast as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td class="num">{{ number_format($p->units_sold) }}</td>
                        <td class="num">{{ number_format($p->units_per_week, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="missing">No products met the fast-moving threshold in this window.</div>
    @endif

    @php $slow = $a['slowMovingProducts'] ?? collect(); @endphp
    <h3>Slow-Moving &amp; Dead Stock</h3>
    @if($slow->count())
        <table>
            <thead><tr><th>Product</th><th class="num">Units Sold</th><th class="num">Units / Week</th></tr></thead>
            <tbody>
                @foreach($slow as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td class="num">{{ number_format($p->units_sold) }}</td>
                        <td class="num">{{ number_format($p->units_per_week, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="missing">No slow-moving products in this window.</div>
    @endif
</body>

</html>
