@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
	.stats-card {
		background: var(--color-surface);
		border-radius: var(--radius-lg);
		padding: 24px;
		box-shadow: var(--shadow-sm);
		margin-bottom: 24px;
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.stats-card:hover {
		transform: translateY(-2px);
		box-shadow: var(--shadow-md);
	}

	.stats-card h4 {
		font-size: 1.1rem;
		font-weight: 600;
		color: var(--color-text);
		margin-bottom: 20px;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.growth-positive {
		color: var(--color-success);
		font-weight: 600;
	}

	.growth-negative {
		color: var(--color-danger);
		font-weight: 600;
	}

	.chart-box {
		background: var(--color-surface);
		border-radius: var(--radius-lg);
		padding: 24px;
		box-shadow: var(--shadow-sm);
		margin-bottom: 24px;
	}

	.chart-box h5 {
		font-size: 1.1rem;
		font-weight: 600;
		color: var(--color-text);
		margin-bottom: 20px;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.chart-container {
		position: relative;
		height: 320px;
	}

	.sales-form {
		background: var(--color-surface);
		border-radius: var(--radius-lg);
		padding: 28px;
		box-shadow: var(--shadow-sm);
	}

	.sales-form h4 {
		font-size: 1.1rem;
		font-weight: 600;
		color: var(--color-text);
		margin-bottom: 24px;
	}

	.form-group label {
		font-weight: 500;
		color: var(--color-neutral-text);
		margin-bottom: 8px;
		font-size: 0.9rem;
	}

	.form-control {
		border-radius: var(--radius-sm);
		border: 1px solid #dee2e6;
		padding: 10px 14px;
		font-size: 0.9rem;
	}

	.form-control:focus {
		border-color: var(--color-primary);
		box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
	}

	select.form-control {
		cursor: pointer;
		appearance: none;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right 12px center;
		padding-right: 35px;
		white-space: normal;
		height: auto;
		min-height: 44px;
	}

	.btn-primary {
		padding: 12px 28px;
		font-weight: 600;
		border-radius: var(--radius-sm);
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.btn-primary:hover {
		transform: translateY(-2px);
	}

	.table {
		font-size: 0.9rem;
	}

	.table thead th {
		border-top: none;
		border-bottom: 2px solid #dee2e6;
		font-weight: 700;
		text-transform: uppercase;
		font-size: 0.8rem;
		letter-spacing: 0.5px;
		padding: 12px;
	}

	.table tbody td {
		padding: 14px 12px;
		vertical-align: middle;
	}

	.table-hover tbody tr:hover {
		background-color: var(--color-primary-soft);
	}

	.charts-row {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
		gap: 24px;
		margin-bottom: 32px;
	}

	@media (max-width: 992px) {
		.charts-row {
			grid-template-columns: 1fr;
		}
	}
</style>

<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">SARIMA Forecasting &amp; Analytics</div>
			<div class="dashboard-hero-sub">
				This month's revenue is ₱{{ number_format($salesStats['current_month_revenue'], 2) }}
				@if($salesStats['growth_percentage'] != 0)
					— {{ $salesStats['growth_percentage'] > 0 ? 'up' : 'down' }} {{ number_format(abs($salesStats['growth_percentage']), 1) }}% vs last month.
				@else
					— powered by SARIMA-driven predictions.
				@endif
			</div>
		</div>
		<div class="dashboard-hero-actions">
			@if(isset($forecastAccuracy) && $forecastAccuracy['products_analyzed'] > 0)
				<a href="javascript:void(0)" data-modal-open="accuracyMetricsModal" class="btn-action ghost">Model Accuracy</a>
			@endif
			@if(isset($restockingRecommendations))
				<a href="javascript:void(0)" data-modal-open="restockingModal" class="btn-action ghost">Restocking</a>
			@endif
			@if(isset($seasonalityAnalysis))
				<a href="javascript:void(0)" data-modal-open="seasonalityModal" class="btn-action ghost">Seasonal Patterns</a>
				<a href="javascript:void(0)" data-modal-open="modelParamsModal" class="btn-action ghost">Model Parameters</a>
			@endif
			<a href="javascript:void(0)" data-open-report class="btn-action ghost">Generate Report</a>
			<a href="/inventory" class="btn-action ghost">View Inventory</a>
		</div>
	</div>
	<div class="stat-grid">
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--success">
				<span class="currency-icon">₱</span>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">This Month Revenue</div>
			<div class="main">₱{{ number_format($salesStats['current_month_revenue'], 2) }}</div>
			@if($salesStats['growth_percentage'] != 0)
				<div class="{{ $salesStats['growth_percentage'] > 0 ? 'trend-up' : 'trend-down' }}">
					{{ $salesStats['growth_percentage'] > 0 ? '+' : '' }}{{ $salesStats['growth_percentage'] }}% from
					last month
				</div>
			@endif
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="7" width="18" height="13" rx="2" />
					<path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Sales This Month</div>
			<div class="main">{{ $salesStats['total_sales_count'] }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="3 17 9 11 13 15 21 7" stroke-linecap="round" stroke-linejoin="round" />
					<circle cx="21" cy="7" r="1.5" fill="currentColor" stroke="none" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Market Trend</div>
			<div class="main">{{ isset($seasonalityAnalysis) ? $seasonalityAnalysis['trend_direction'] : 'stable' }}
			</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon" style="background: #f3e8ff; color: #a21caf;">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="3 17 9 11 13 15 21 7" stroke-linecap="round" stroke-linejoin="round" />
					<circle cx="21" cy="7" r="1.5" fill="currentColor" stroke="none" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Annual Growth Rate</div>
			<div class="main">
				{{ isset($seasonalityAnalysis) ? number_format($seasonalityAnalysis['yearly_growth_rate'], 1) : '0' }}%
			</div>
		</div>
	</div>

	{{-- Model Accuracy Metrics now lives in #accuracyMetricsModal, opened from
	     the hero actions — see the bottom of this file. --}}

	{{-- Restocking, Seasonal Patterns and Model Parameters now live in their
	     own modals, opened from the hero actions — see the bottom of this file. --}}

	<!-- Actual Sales and Forecast Charts -->
	<div class="charts-row">
		<!-- Actual Sales Chart -->
		<div class="chart-box">
			<h5>📊 Actual Sales History</h5>
			<div class="chart-container">
				<canvas id="actualSalesChart"></canvas>
			</div>
		</div>

		<!-- Sales Forecast Chart -->
		<div class="chart-box">
			<h5>🔮 SARIMA Forecast</h5>
			<div class="chart-container">
				<canvas id="forecastChart"></canvas>
			</div>
		</div>
	</div>

	<!-- Historical Sales Summary -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="stats-card">
				<h4>📅 Historical Sales Summary - Previous Months</h4>
				<div class="table-responsive">
					<table class="table table-hover">
						<thead class="thead-light">
							<tr>
								<th>Month</th>
								<th>Total Revenue</th>
								<th>Total Quantity Sold</th>
								<th>Growth vs Previous Month</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							@if(isset($forecast['historical']) && isset($forecast['months']))
								@php
									$previousRevenue = null;
									$displayedMonths = [];
									$totalRevenue = 0;
									$totalQuantity = 0;
								@endphp
								@foreach($forecast['months'] as $index => $month)
									@php
										$revenue = $forecast['historical'][$index] ?? 0;

										// Only show months with actual sales data
										if ($revenue <= 0) {
											continue;
										}

										$growth = null;
										$growthPercentage = 0;
										$currentRevenue = ($month == \Carbon\Carbon::now()->format('Y-m')) ? $salesStats['current_month_revenue'] : $revenue;
										if ($previousRevenue !== null && $previousRevenue > 0) {
											$growth = $currentRevenue - $previousRevenue;
											$growthPercentage = ($growth / $previousRevenue) * 100;
										}

										$salesData = \App\Models\Sale::where('month_year', $month)->sum('quantity_sold');
										if ($month == \Carbon\Carbon::now()->format('Y-m')) {
											$totalRevenue += $salesStats['current_month_revenue'];
										} else {
											$totalRevenue += $revenue;
										}
										$totalQuantity += $salesData;
										$displayedMonths[] = $month;
									@endphp
									<tr>
										<td><strong>{{ Carbon\Carbon::parse($month)->format('M Y') }}</strong></td>
										@if($month == \Carbon\Carbon::now()->format('Y-m'))
											<td><strong>₱{{ number_format($salesStats['current_month_revenue'], 2) }}</strong>
											</td>
										@else
											<td><strong>₱{{ number_format($revenue, 2) }}</strong></td>
										@endif
										<td>{{ number_format($salesData) }} items</td>
										<td>
											@if($growth !== null)
												@if($growth > 0)
													<span class="text-success" style="display:inline-flex; align-items:center; gap:4px;">
														<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
														₱{{ number_format(abs($growth), 2) }}
														(+{{ number_format($growthPercentage, 1) }}%)
													</span>
												@elseif($growth < 0)
													<span class="text-danger" style="display:inline-flex; align-items:center; gap:4px;">
														<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7 7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
														₱{{ number_format(abs($growth), 2) }}
														({{ number_format($growthPercentage, 1) }}%)
													</span>
												@else
													<span class="text-muted">No change</span>
												@endif
											@else
												<span class="text-muted">-</span>
											@endif
										</td>
										<td>
											@if($currentRevenue > 100000)
												<span class="badge badge-success">High Sales</span>
											@elseif($currentRevenue > 50000)
												<span class="badge badge-info">Moderate Sales</span>
											@else
												<span class="badge badge-warning">Low Sales</span>
											@endif
										</td>
									</tr>
									@php
										$previousRevenue = $revenue;
									@endphp
								@endforeach

								@if(count($displayedMonths) == 0)
									<tr>
										<td colspan="5" class="text-center text-muted">
											<em>No historical sales data available. Please generate sales data using the
												seeder.</em>
										</td>
									</tr>
								@endif
							@endif
						</tbody>
						@if(isset($displayedMonths) && count($displayedMonths) > 0)
							<tfoot class="thead-light">
								<tr>
									<th>Total</th>
									<th><strong>₱{{ number_format($totalRevenue, 2) }}</strong></th>
									<th>{{ number_format($totalQuantity) }} items</th>
									<th colspan="2">
										<span class="text-muted">
											Avg:
											₱{{ number_format(count($displayedMonths) > 0 ? $totalRevenue / count($displayedMonths) : 0, 2) }}
											/ month
										</span>
									</th>
								</tr>
							</tfoot>
						@endif
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Detailed Forecast Analysis Table -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="stats-card">
				<h4>📋 Detailed SARIMA Forecast Analysis</h4>
				<div class="table-responsive">
					<table class="table table-hover">
						<thead class="thead-light">
							<tr>
								<th>Month</th>
								<th>Predicted Revenue</th>
								<th>Confidence Range (95%)</th>

								<th>Recommendation</th>
							</tr>
						</thead>
						<tbody>
							@if(isset($forecast['predicted']))
								@php
									// Baseline for judging each forecasted month: the recent
									// actual average, not "how many months away is this" - a
									// month 5 months out with a predicted spike and one with a
									// predicted crash used to get the identical "Long-term
									// Planning" label. Last 6 non-zero historical months, same
									// window used elsewhere in this app (reorder point, demand
									// forecast, accuracy check).
									$recentHistory = array_filter(array_slice($forecast['historical'] ?? [], -6));
									$recentAverage = count($recentHistory) > 0 ? array_sum($recentHistory) / count($recentHistory) : null;
								@endphp
								@foreach($forecast['predicted'] as $month => $prediction)
									<tr>
										<td><strong>{{ Carbon\Carbon::parse($month)->format('M Y') }}</strong></td>
										<td>₱{{ number_format($prediction, 2) }}</td>
										<td>
											@if(isset($forecast['confidence_intervals'][$month]))
												<span class="text-muted">
													₱{{ number_format($forecast['confidence_intervals'][$month]['lower'], 0) }}
													-
													₱{{ number_format($forecast['confidence_intervals'][$month]['upper'], 0) }}
												</span>
											@endif
										</td>

										<td>
											@php
												$deviationPercent = ($recentAverage && $recentAverage > 0)
													? (($prediction - $recentAverage) / $recentAverage) * 100
													: null;

												if ($deviationPercent === null) {
													$recLabel = 'Insufficient Data';
													$recClass = 'badge-secondary';
												} elseif ($deviationPercent >= 15) {
													$recLabel = 'Increase Stock';
													$recClass = 'badge-danger';
												} elseif ($deviationPercent <= -15) {
													$recLabel = 'Reduce Orders';
													$recClass = 'badge-info';
												} else {
													$recLabel = 'Maintain Levels';
													$recClass = 'badge-warning';
												}

												// A wide confidence band relative to the prediction
												// means the model itself isn't sure - flagged
												// separately rather than folded into the main label,
												// since "increase stock, but we're not confident" is a
												// different message than "increase stock".
												$ci = $forecast['confidence_intervals'][$month] ?? null;
												$isLowConfidence = $ci && $prediction > 0
													&& (($ci['upper'] - $ci['lower']) / $prediction) > 0.5;
											@endphp
											<span class="badge {{ $recClass }}">{{ $recLabel }}</span>
											@if($isLowConfidence)
												<span class="badge badge-light" title="The confidence range for this month is wide relative to the prediction">Low Confidence</span>
											@endif
										</td>
									</tr>
								@endforeach
							@endif
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Product-Specific Demand Forecasting -->
	@if(isset($demandForecast))
		<div class="row mb-4">
			<div class="col-12">
				<div class="stats-card">
					<h4>📦 Product-Specific Demand Forecasting & Inventory Optimization</h4>
					<div class="table-responsive">
						<table class="table table-sm" id="demandForecastTable">
							<thead class="thead-dark">
								<tr>
									<th>Product</th>
									<th>Current Stock</th>
									<th>Forecasted Demand</th>
									<th>Risk Level</th>
									<th>Days Until Stockout</th>
									<th>Recommended Action</th>
								</tr>
							</thead>
							<tbody>
								{{-- All products render into the DOM; the script below shows
									 10 rows at a time via data-df-page, so the whole catalog
									 is reachable without a server round-trip per page. --}}
								@foreach($demandForecast as $productId => $productForecast)
									<tr class="demand-forecast-row" data-df-page="{{ intdiv($loop->index, 10) + 1 }}">
										<td><strong>{{ $productForecast['product_name'] }}</strong></td>
										<td>{{ $productForecast['current_stock'] }}</td>
										<td>{{ number_format($productForecast['forecasted_demand'], 1) }}/month</td>
										<td>
											@if($productForecast['risk_level'] === 'HIGH')
												<span class="badge badge-danger">HIGH</span>
											@elseif($productForecast['risk_level'] === 'MEDIUM')
												<span class="badge badge-warning">MEDIUM</span>
											@else
												<span class="badge badge-success">LOW</span>
											@endif
										</td>
										<td>{{ $productForecast['days_until_stockout'] > 365 ? '365+' : $productForecast['days_until_stockout'] }}
											days</td>
										<td>
											<small>
												@if($productForecast['recommended_order_quantity'] > 0)
													Order {{ $productForecast['recommended_order_quantity'] }} units
												@else
													Adequate stock
												@endif
											</small>
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
					<div id="demandForecastPagination" style="display: none; align-items: center; justify-content: center; gap: 14px; margin-top: 14px;">
						<button type="button" id="dfPrevBtn" class="btn-action ghost" style="padding: 6px 14px; font-size: 0.85rem;">‹ Prev</button>
						<span id="dfPageIndicator" style="color: var(--color-text-muted); font-size: 0.85rem;"></span>
						<button type="button" id="dfNextBtn" class="btn-action ghost" style="padding: 6px 14px; font-size: 0.85rem;">Next ›</button>
					</div>
					<script>
						(function () {
							var rows = Array.from(document.querySelectorAll('#demandForecastTable .demand-forecast-row'));
							if (!rows.length) return;

							var totalPages = Math.max.apply(null, rows.map(function (r) { return parseInt(r.dataset.dfPage, 10); }));
							var pagination = document.getElementById('demandForecastPagination');
							var indicator = document.getElementById('dfPageIndicator');
							var prevBtn = document.getElementById('dfPrevBtn');
							var nextBtn = document.getElementById('dfNextBtn');
							var currentPage = 1;

							if (totalPages <= 1) return;
							pagination.style.display = 'flex';

							function render() {
								rows.forEach(function (row) {
									row.style.display = (parseInt(row.dataset.dfPage, 10) === currentPage) ? '' : 'none';
								});
								indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
								prevBtn.disabled = currentPage === 1;
								nextBtn.disabled = currentPage === totalPages;
							}

							prevBtn.addEventListener('click', function () {
								if (currentPage > 1) { currentPage--; render(); }
							});
							nextBtn.addEventListener('click', function () {
								if (currentPage < totalPages) { currentPage++; render(); }
							});

							render();
						})();
					</script>
				</div>
			</div>
		</div>
	@endif

	<!-- Section Divider -->
	<div class="section-divider"></div>

	<div class="row">
		<!-- Top Selling Products -->
		<div class="col-md-12">
			<div class="stats-card">
				<h4>🏆 Top Selling Products (This Month)</h4>
				@if($topProducts->count() > 0)
					<div class="table-responsive">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Product</th>
									<th>Sold</th>
									<th>Revenue</th>
								</tr>
							</thead>
							<tbody>
								@foreach($topProducts as $product)
									<tr>
										<td>{{ $product->name }}</td>
										<td>{{ $product->total_sold }}</td>
										<td>₱{{ number_format($product->total_revenue, 2) }}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				@else
					<p class="text-muted text-center">No sales data available for this month.</p>
				@endif
			</div>
		</div>
	</div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Debug jQuery loading -->
<script>
	if (typeof jQuery === 'undefined') {
		console.error('jQuery is not loaded!');
	} else {
		console.log('jQuery loaded successfully, version:', jQuery.fn.jquery);
	}

	// Test toast functionality on page load
	$(document).ready(function () {
		console.log('Document ready, testing functionality...');
		console.log('jQuery version:', $.fn.jquery);
		console.log('Toast containers:', $('.toast').length);

		// Check CSRF token availability
		const csrfToken = $('meta[name="csrf-token"]').attr('content');
		console.log('CSRF Token available:', csrfToken ? 'Yes' : 'No');
		if (csrfToken) {
			console.log('CSRF Token (first 10 chars):', csrfToken.substring(0, 10) + '...');
		} else {
			console.error('CSRF token not found! Check meta tag.');
		}

		// Test if bootstrap toast is available
		if (typeof bootstrap !== 'undefined') {
			console.log('Bootstrap object available:', typeof bootstrap.Toast);
		} else if ($.fn.toast) {
			console.log('jQuery toast plugin available');
		} else {
			console.log('No toast functionality available, will fallback to alerts');
		}

		// Set today's date as default
		const today = new Date().toISOString().split('T')[0];
		$('#sale_date').val(today);
		console.log('Default date set:', today);
	});
</script>

<!-- Pass PHP data to JavaScript -->
<script type="text/javascript">
	window.salesTrendData = @json($salesTrend ?? ['months' => [], 'actual' => [], 'forecast' => []]);
	window.sarimaForecastData = @json($forecast ?? ['months' => [], 'predicted' => []]);
</script>

<script>
	// Simple Actual Sales and Forecast Charts
	document.addEventListener('DOMContentLoaded', function () {
		const salesData = window.salesTrendData;
		// Actual Sales Chart (Historical Only)
		const actualCtx = document.getElementById('actualSalesChart');
		if (actualCtx && salesData) {
			const actualMonths = [];
			const actualValues = [];
			salesData.months.forEach((month, index) => {
				if (salesData.actual[index] !== null) {
					actualMonths.push(month);
					actualValues.push(salesData.actual[index]);
				}
			});
			window.actualSalesChart = new Chart(actualCtx, {
				type: 'line',
				data: {
					labels: actualMonths,
					datasets: [{
						label: 'Actual Sales',
						data: actualValues,
						borderColor: '#6366f1',
						backgroundColor: 'rgba(99, 102, 241, 0.1)',
						borderWidth: 3,
						fill: true,
						tension: 0.4,
						pointRadius: 5,
						pointHoverRadius: 7,
						pointBackgroundColor: '#6366f1'
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							callbacks: {
								label: function (context) {
									return 'Revenue: ₱' + new Intl.NumberFormat('en-PH').format(context.parsed.y);
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0,0,0,0.05)'
							},
							ticks: {
								callback: function (value) {
									return '₱' + new Intl.NumberFormat('en-PH', {
										notation: 'compact',
										compactDisplay: 'short'
									}).format(value);
								},
								font: {
									size: 11
								}
							}
						},
						x: {
							grid: {
								display: false
							},
							ticks: {
								maxRotation: 45,
								minRotation: 45,
								font: {
									size: 10
								}
							}
						}
					}
				}
			});
		}
		// Forecast Chart (SARIMA Predictions)
		const forecastCtx = document.getElementById('forecastChart');
		const sarimaData = window.sarimaForecastData;
		if (forecastCtx && sarimaData && sarimaData.predicted) {
			const forecastMonths = Object.keys(sarimaData.predicted).map(month => {
				// The real SARIMA path (python/sarima_forecast.py) already returns
				// human-readable "MMM YYYY" labels (e.g. "Jul 2026") — use as-is.
				if (/^[A-Za-z]{3}\s\d{4}$/.test(month)) {
					return month;
				}
				// The PHP fallback path returns "YYYY-MM" (e.g. "2026-07") instead.
				const date = new Date(month + '-01');
				if (isNaN(date.getTime())) {
					return month;
				}
				return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
			});
			const forecastValues = Object.values(sarimaData.predicted);
			window.forecastChart = new Chart(forecastCtx, {
				type: 'line',
				data: {
					labels: forecastMonths,
					datasets: [{
						label: 'SARIMA Forecast',
						data: forecastValues,
						borderColor: '#16a34a',
						backgroundColor: 'rgba(22, 163, 74, 0.1)',
						borderWidth: 3,
						borderDash: [8, 4],
						fill: true,
						tension: 0.4,
						pointRadius: 5,
						pointHoverRadius: 7,
						pointBackgroundColor: '#16a34a',
						pointStyle: 'circle'
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							callbacks: {
								label: function (context) {
									return 'Predicted: ₱' + new Intl.NumberFormat('en-PH').format(context.parsed.y);
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0,0,0,0.05)'
							},
							ticks: {
								callback: function (value) {
									return '₱' + new Intl.NumberFormat('en-PH', {
										notation: 'compact',
										compactDisplay: 'short'
									}).format(value);
								},
								font: {
									size: 11
								}
							}
						},
						x: {
							grid: {
								display: false
							},
							ticks: {
								font: {
									size: 10
								},
								maxRotation: 45,
								minRotation: 45
							}
						}
					}
				}
			});
		}

		// Replay each chart's draw-in animation when it scrolls into view, since
		// Chart.js only animates once at creation time (which usually happens
		// off-screen, below the fold, before the user ever scrolls to see it).
		if ('IntersectionObserver' in window) {
			const chartObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					const canvas = entry.target.querySelector('canvas');
					const chart = canvas && Chart.getChart(canvas);
					if (chart) {
						chart.reset();
						chart.update();
					}
				});
			}, { threshold: 0.4 });

			document.querySelectorAll('.chart-box').forEach(function (box) {
				chartObserver.observe(box);
			});
		}
	});

</script>

@include('components.report-drawer')

{{-- Model Accuracy Metrics (MAE / RMSE / MAPE / MASE).
     Moved out of the page flow into a modal: these are diagnostic statistics
     rather than day-to-day figures, so they belong behind a deliberate click.
     Uses the shared .modal-overlay / .modal-card pair so it matches the
     suppliers, inventory and account-management modals. --}}
@if(isset($forecastAccuracy) && $forecastAccuracy['products_analyzed'] > 0)
	@php
		$maseTitle = $forecastAccuracy['mase'] !== null
			? ($forecastAccuracy['mase'] < 1 ? 'Beats a naive forecast' : 'Worse than a naive forecast')
			: 'Not enough data to compute';
		$methodologyTitle = 'Based on ' . $forecastAccuracy['products_analyzed'] . ' product' . ($forecastAccuracy['products_analyzed'] === 1 ? '' : 's') . ' with enough sales history. Each is evaluated by holding out its most recent month of sales and forecasting it from the prior months.';
		$statusBadgeClass = match($forecastAccuracy['status']) {
			'Excellent', 'Good' => 'badge-success',
			'Fair' => 'badge-warning',
			default => 'badge-danger',
		};
	@endphp
	<div id="accuracyMetricsModal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true"
		aria-labelledby="accuracyMetricsModalTitle">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 640px;">
			<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 id="accuracyMetricsModalTitle" style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">
					📐 Model Accuracy Metrics
					<span title="{{ $methodologyTitle }}" style="cursor: help; font-size: 0.9rem; color: var(--color-text-muted);">&#9432;</span>
				</h5>
				<button type="button" data-modal-close aria-label="Close"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<div style="padding: 24px 28px;">
				<div class="mb-3">
					<span style="font-size: 1.3rem; font-weight: 700; color: var(--color-text);">{{ number_format($forecastAccuracy['accuracy_percentage'], 1) }}% Overall Accuracy</span>
					<span class="badge {{ $statusBadgeClass }}" style="margin-left: 8px;">{{ $forecastAccuracy['status'] }}</span>
				</div>
				<div class="row">
					<div class="col-6 col-md-3 mb-3">
						<div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
							MAE <span title="Average error, in units" style="cursor: help;">&#9432;</span>
						</div>
						<div style="font-size: 1.4rem; font-weight: 700; color: var(--color-text);">{{ number_format($forecastAccuracy['mae'], 2) }}</div>
					</div>
					<div class="col-6 col-md-3 mb-3">
						<div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
							RMSE <span title="Penalizes large misses more" style="cursor: help;">&#9432;</span>
						</div>
						<div style="font-size: 1.4rem; font-weight: 700; color: var(--color-text);">{{ number_format($forecastAccuracy['rmse'], 2) }}</div>
					</div>
					<div class="col-6 col-md-3 mb-3">
						<div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
							MAPE <span title="Average error, as % of actual" style="cursor: help;">&#9432;</span>
						</div>
						<div style="font-size: 1.4rem; font-weight: 700; color: var(--color-text);">{{ number_format($forecastAccuracy['mape'], 2) }}%</div>
					</div>
					<div class="col-6 col-md-3 mb-3">
						<div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
							MASE <span title="{{ $maseTitle }}" style="cursor: help;">&#9432;</span>
						</div>
						<div class="{{ $forecastAccuracy['mase'] !== null ? ($forecastAccuracy['mase'] < 1 ? 'growth-positive' : 'growth-negative') : '' }}"
							style="font-size: 1.4rem; font-weight: 700;">
							{{ $forecastAccuracy['mase'] !== null ? number_format($forecastAccuracy['mase'], 2) : 'N/A' }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

@endif

{{-- Automated Restocking Recommendations --}}
@if(isset($restockingRecommendations))
	<div id="restockingModal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true"
		aria-labelledby="restockingModalTitle">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 560px;">
			<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 id="restockingModalTitle" style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">
					🚨 Automated Restocking Recommendations
				</h5>
				<button type="button" data-modal-close aria-label="Close"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<div style="padding: 24px 28px;">
				@if(count($restockingRecommendations['urgent_restock']) > 0)
					<div class="alert alert-danger">
						<h6><strong>🔴 Urgent Restock Required
								({{ count($restockingRecommendations['urgent_restock']) }} items)</strong></h6>
						@foreach(array_slice($restockingRecommendations['urgent_restock'], 0, 3) as $item)
							<div class="d-flex justify-content-between">
								<span>{{ $item['product_name'] }}</span>
								<span class="badge badge-danger">{{ $item['current_stock'] }} left</span>
							</div>
						@endforeach
					</div>
				@endif

				@if(count($restockingRecommendations['monitor_closely']) > 0)
					<div class="alert alert-warning">
						<h6><strong>🟡 Monitor Closely
								({{ count($restockingRecommendations['monitor_closely']) }} items)</strong></h6>
						@foreach(array_slice($restockingRecommendations['monitor_closely'], 0, 3) as $item)
							<div class="d-flex justify-content-between">
								<span>{{ $item['product_name'] }}</span>
								<span class="badge badge-warning">{{ $item['current_stock'] }} stock</span>
							</div>
						@endforeach
					</div>
				@endif
			</div>
		</div>
	</div>
@endif

{{-- Seasonal Pattern Analysis + SARIMA Model Parameters.
     Both stay behind the same isset($seasonalityAnalysis) gate they had when
     they were page cards, so neither appears in a state it did not before. --}}
@if(isset($seasonalityAnalysis))
	<div id="seasonalityModal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true"
		aria-labelledby="seasonalityModalTitle">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 560px;">
			<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 id="seasonalityModalTitle" style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">
					📅 Seasonal Pattern Analysis
				</h5>
				<button type="button" data-modal-close aria-label="Close"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<div style="padding: 24px 28px;">
				@php
					// Does the historical peak/low pattern actually recur in the
					// real 12-month SARIMA forecast? Maps each forecasted month
					// to its calendar-month code (e.g. '07' for July) so a
					// historically-peak month can be checked against its next
					// real occurrence. Badge color reflects the result
					// (confirmed vs. not) without needing explanatory text per
					// month - green/red stays "success"/"secondary" when there's
					// no upcoming occurrence to check yet.
					$upcomingByMonthCode = [];
					foreach (($forecast['predicted'] ?? []) as $fMonth => $fPrediction) {
						$code = \Carbon\Carbon::parse($fMonth)->format('m');
						if (!isset($upcomingByMonthCode[$code])) {
							$upcomingByMonthCode[$code] = ['month' => $fMonth, 'prediction' => $fPrediction];
						}
					}

					$seasonalRecentHistory = array_filter(array_slice($forecast['historical'] ?? [], -6));
					$seasonalRecentAverage = count($seasonalRecentHistory) > 0 ? array_sum($seasonalRecentHistory) / count($seasonalRecentHistory) : null;

					$seasonalBadgeClass = function ($month, bool $isPeak) use ($upcomingByMonthCode, $seasonalRecentAverage) {
						if (!$seasonalRecentAverage || !isset($upcomingByMonthCode[$month])) {
							return $isPeak ? 'badge-success' : 'badge-secondary';
						}
						$dev = $upcomingByMonthCode[$month]['prediction'] - $seasonalRecentAverage;
						$confirmed = $isPeak ? $dev > 0 : $dev < 0;
						return $confirmed ? ($isPeak ? 'badge-success' : 'badge-secondary') : 'badge-warning';
					};
				@endphp
				<div class="mb-3">
					<h6>Peak Sales Months:</h6>
					@foreach($seasonalityAnalysis['peak_months'] as $month)
						<span class="badge {{ $seasonalBadgeClass($month, true) }} mr-1">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</span>
					@endforeach
				</div>
				<div class="mb-3">
					<h6>Low Sales Months:</h6>
					@foreach($seasonalityAnalysis['low_months'] as $month)
						<span class="badge {{ $seasonalBadgeClass($month, false) }} mr-1">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</span>
					@endforeach
				</div>
			</div>
		</div>
	</div>

	<div id="modelParamsModal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true"
		aria-labelledby="modelParamsModalTitle">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 560px;">
			<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 id="modelParamsModalTitle" style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">
					⚙️ SARIMA Model Parameters
				</h5>
				<button type="button" data-modal-close aria-label="Close"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<div style="padding: 24px 28px;">
				@if(isset($forecast['model_parameters']))
					<small><strong>AR Order (p):</strong> {{ $forecast['model_parameters']['p'] }}</small><br>
					<small><strong>Differencing (d):</strong> {{ $forecast['model_parameters']['d'] }}</small><br>
					<small><strong>MA Order (q):</strong> {{ $forecast['model_parameters']['q'] }}</small><br>
					<small><strong>Seasonal AR (P):</strong> {{ $forecast['model_parameters']['P'] }}</small><br>
					<small><strong>Seasonal Diff (D):</strong> {{ $forecast['model_parameters']['D'] }}</small><br>
					<small><strong>Seasonal MA (Q):</strong> {{ $forecast['model_parameters']['Q'] }}</small>
					<div class="mt-2">
						<small><strong>Seasonal Period (s):</strong> {{ $forecast['model_parameters']['s'] }} months</small><br>
						<small><strong>Months Forecasted:</strong> {{ count($forecast['predicted']) }}</small>
					</div>
				@endif
			</div>
		</div>
	</div>
@endif

{{-- One delegated handler for every modal on this page, rather than a copy of
     the same open/close wiring per card. Triggers declare `data-modal-open`,
     close buttons `data-modal-close`. --}}
<script>
	(function () {
		let lastTrigger = null;

		function openModal(id, trigger) {
			const modal = document.getElementById(id);
			if (!modal) return;
			lastTrigger = trigger || null;
			modal.style.display = 'flex';
			const closeBtn = modal.querySelector('[data-modal-close]');
			if (closeBtn) closeBtn.focus();
		}

		function closeModal(modal) {
			modal.style.display = 'none';
			// Return focus to whatever opened it, so keyboard users are not
			// dumped back at the top of the document.
			if (lastTrigger) lastTrigger.focus();
			lastTrigger = null;
		}

		document.addEventListener('click', function (e) {
			const trigger = e.target.closest('[data-modal-open]');
			if (trigger) {
				e.preventDefault();
				openModal(trigger.getAttribute('data-modal-open'), trigger);
				return;
			}

			const closeBtn = e.target.closest('[data-modal-close]');
			if (closeBtn) {
				const modal = closeBtn.closest('.modal-overlay');
				if (modal) closeModal(modal);
				return;
			}

			// Backdrop only — a click inside the card must not close it. Scoped
			// to this page's modals so it cannot interfere with other overlays.
			if (e.target.matches('#accuracyMetricsModal, #restockingModal, #seasonalityModal, #modelParamsModal')) {
				closeModal(e.target);
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			document
				.querySelectorAll('#accuracyMetricsModal, #restockingModal, #seasonalityModal, #modelParamsModal')
				.forEach(function (modal) {
					if (modal.style.display === 'flex') closeModal(modal);
				});
		});
	})();
</script>

@endsection