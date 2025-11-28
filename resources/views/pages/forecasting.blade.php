<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>SARIMA Forecasting/Analytics - Inventory System</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/topheader.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		body {
			background: #f8f9fa;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
		}

		.forecasting-container {
			margin-left: 220px;
			padding: 30px 40px;
			padding-top: 90px;
			background: #f8f9fa;
			min-height: 100vh;
		}

		.page-header {
			margin-bottom: 30px;
		}

		.page-header h2 {
			font-size: 1.75rem;
			font-weight: 600;
			color: #2c3e50;
			margin-bottom: 5px;
		}

		.page-header p {
			color: #6c757d;
			margin: 0;
			font-size: 0.95rem;
		}

		.stats-card {
			background: white;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
			margin-bottom: 24px;
			border: 1px solid #e9ecef;
			transition: transform 0.2s, box-shadow 0.2s;
		}

		.stats-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		}

		.stats-card h4 {
			font-size: 1.1rem;
			font-weight: 600;
			color: #2c3e50;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.stat-number {
			font-size: 1.75rem;
			font-weight: 700;
			color: #2c3e50;
			margin-bottom: 8px;
		}

		.stat-label {
			color: #6c757d;
			font-size: 0.875rem;
			font-weight: 500;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.growth-positive {
			color: #28a745;
			font-weight: 600;
		}

		.growth-negative {
			color: #dc3545;
			font-weight: 600;
		}

		.chart-box {
			background: white;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
			border: 1px solid #e9ecef;
			margin-bottom: 24px;
		}

		.chart-box h5 {
			font-size: 1.1rem;
			font-weight: 600;
			color: #2c3e50;
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
			background: white;
			border-radius: 12px;
			padding: 28px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
			border: 1px solid #e9ecef;
		}

		.sales-form h4 {
			font-size: 1.1rem;
			font-weight: 600;
			color: #2c3e50;
			margin-bottom: 24px;
		}

		.form-group label {
			font-weight: 500;
			color: #495057;
			margin-bottom: 8px;
			font-size: 0.9rem;
		}

		.form-control {
			border-radius: 8px;
			border: 1px solid #dee2e6;
			padding: 10px 14px;
			font-size: 0.9rem;
		}

		.form-control:focus {
			border-color: #007bff;
			box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
		}

		/* Enhanced select dropdown styling */
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

		select.form-control option {
			padding: 12px 10px;
			font-size: 0.9rem;
			white-space: normal;
			word-wrap: break-word;
			line-height: 1.5;
		}

		/* Fix for dropdown option display */
		#product_id {
			max-width: 100%;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Stock info styling */
		#stockInfo {
			display: block;
			margin-top: 6px;
			font-size: 0.85rem;
		}

		.btn-primary {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border: none;
			padding: 12px 28px;
			font-weight: 600;
			border-radius: 8px;
			transition: transform 0.2s, box-shadow 0.2s;
		}

		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		}

		.table {
			font-size: 0.9rem;
		}

		.table thead th {
			border-top: none;
			border-bottom: 2px solid #dee2e6;
			color: #495057;
			font-weight: 600;
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
			background-color: #f8f9fa;
		}

		.badge {
			padding: 6px 12px;
			font-weight: 600;
			border-radius: 6px;
			font-size: 0.75rem;
		}

		.forecast-legend {
			display: flex;
			gap: 20px;
			margin-top: 10px;
		}

		.legend-item {
			display: flex;
			align-items: center;
			gap: 5px;
		}

		.legend-color {
			width: 20px;
			height: 3px;
		}

		.toast {
			opacity: 0;
			max-width: 350px;
			font-size: 0.875rem;
			background-color: rgba(255, 255, 255, 0.85);
			background-clip: padding-box;
			border: 1px solid rgba(0, 0, 0, 0.1);
			box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
			backdrop-filter: blur(10px);
			border-radius: 0.25rem;
		}

		.toast:not(:last-child) {
			margin-bottom: 0.75rem;
		}

		.toast.showing {
			opacity: 1;
		}

		.toast.show {
			display: block;
			opacity: 1;
		}

		.toast.hide {
			display: none;
		}

		.toast-header {
			display: flex;
			align-items: center;
			padding: 0.5rem 0.75rem;
			color: #6c757d;
			background-color: rgba(255, 255, 255, 0.85);
			background-clip: padding-box;
			border-bottom: 1px solid rgba(0, 0, 0, 0.05);
		}

		.toast-body {
			padding: 0.75rem;
		}

		.toast-container {
			position: fixed;
			z-index: 1060;
			top: 20px;
			right: 20px;
			max-width: 100%;
		}

		.section-divider {
			border-top: 2px solid #e9ecef;
			margin: 40px 0;
		}

		/* Chart boxes styling */
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
</head>


<style>
.dashboard-gradient {
	background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
	min-height: 100vh;
}
.dashboard-title {
	font-size: 2.3rem;
	font-weight: 800;
	margin-bottom: 0.2em;
	letter-spacing: -1px;
	color: #18181b;
}
.dashboard-subtitle {
	color: #6366f1;
	font-size: 1.1rem;
	margin-bottom: 2.5rem;
	font-weight: 500;
}
.dashboard-cards {
	display: flex;
	gap: 32px;
	margin-bottom: 32px;
	flex-wrap: wrap;
}
.dashboard-card {
	flex: 1 1 220px;
	background: #fff;
	border-radius: 18px;
	box-shadow: 0 4px 24px 0 rgba(99,102,241,0.08);
	padding: 32px 28px 28px 28px;
	display: flex;
	flex-direction: column;
	align-items: center;
	min-width: 220px;
	transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
	border: none;
	position: relative;
	cursor: pointer;
}
.dashboard-card:hover {
	transform: translateY(-7px) scale(1.03);
	box-shadow: 0 8px 32px 0 rgba(99,102,241,0.16);
}
.dashboard-card .icon {
	width: 48px;
	height: 48px;
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 2.1rem;
	margin-bottom: 10px;
}
.dashboard-card .main {
	font-size: 2rem;
	font-weight: 700;
	color: #18181b;
	margin-bottom: 0.2em;
}
.dashboard-card .desc {
	font-size: 1.08rem;
	color: #6366f1;
	font-weight: 600;
	margin-bottom: 0.5em;
}
.dashboard-card .sub {
	font-size: 1rem;
	color: #888;
	font-weight: 400;
}
.dashboard-card .trend-up {
	color: #22c55e;
	font-weight: 600;
	font-size: 1.02rem;
}
.dashboard-card .trend-down {
	color: #ef4444;
	font-weight: 600;
	font-size: 1.02rem;
}
.dashboard-card .status-excellent { color: #22c55e; font-weight: 600; }
.dashboard-card .status-good { color: #3b82f6; font-weight: 600; }
.dashboard-card .status-fair { color: #f59e0b; font-weight: 600; }
.dashboard-card .status-pending { color: #888; font-weight: 600; }
.dashboard-cards-row {
	display: flex;
	gap: 32px;
	margin-bottom: 32px;
	flex-wrap: wrap;
}
.dashboard-cards-row .dashboard-card {
	min-width: 180px;
	flex: 1 1 180px;
}
</style>
<body>
	@include('components.topheader')
	@include('components.sidebar')
	<div class="dashboard-gradient" style="margin-left: 220px; padding: 40px; padding-top: 90px;">
		<div class="dashboard-title">SARIMA Forecasting/Analytics</div>
		<div class="dashboard-subtitle">Advanced sales prediction and inventory management insights</div>
		<div class="dashboard-cards">
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #e0fce6; color: #22c55e;">&#8369;</div>
				<div class="desc">This Month Revenue</div>
				<div class="main">₱{{ number_format($salesStats['current_month_revenue'], 2) }}</div>
				@if($salesStats['growth_percentage'] != 0)
				<div class="{{ $salesStats['growth_percentage'] > 0 ? 'trend-up' : 'trend-down' }}">
					{{ $salesStats['growth_percentage'] > 0 ? '+' : '' }}{{ $salesStats['growth_percentage'] }}% from last month
				</div>
				@endif		
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #e0e7ff; color: #3b82f6;">&#128230;</div>
				<div class="desc">Total Sales This Month</div>
				<div class="main">{{ $salesStats['total_sales_count'] }}</div>
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #fef9c3; color: #f59e0b;">&#8369;</div>
				<div class="desc">Average Order Value</div>
				<div class="main">₱{{ number_format($salesStats['average_order_value'], 2) }}</div>
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #f3e8ff; color: #a21caf;">&#128200;</div>
				<div class="desc">Months Forecasted</div>
				<div class="main">{{ count($forecast['predicted']) }}</div>
			</div>
		</div>
		<div class="dashboard-cards-row">
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #e0e7ff; color: #3b82f6;">&#128200;</div>
				<div class="desc">Market Trend</div>
				<div class="main">{{ isset($seasonalityAnalysis) ? $seasonalityAnalysis['trend_direction'] : 'stable' }}</div>
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #e0fce6; color: #22c55e;">&#128202;</div>
				<div class="desc">Forecast Accuracy</div>
				<div class="main">{{ isset($forecastAccuracy) ? number_format($forecastAccuracy['accuracy_percentage'], 1) : '0' }}%</div>
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #fef9c3; color: #f59e0b;">&#128197;</div>
				<div class="desc">Seasonality Index</div>
				<div class="main">{{ isset($seasonalityAnalysis) ? number_format($seasonalityAnalysis['seasonality_strength'], 2) : '0' }}</div>
			</div>
			<div class="dashboard-card" tabindex="0">
				<div class="icon" style="background: #f3e8ff; color: #a21caf;">&#128200;</div>
				<div class="desc">Annual Growth Rate</div>
				<div class="main">{{ isset($seasonalityAnalysis) ? number_format($seasonalityAnalysis['yearly_growth_rate'], 1) : '0' }}%</div>
			</div>
		</div>

			<!-- Automated Restocking Recommendations -->
			@if(isset($restockingRecommendations))
			<div class="row mb-4">
				<div class="col-12">
					<div class="stats-card">
						<h4>🚨 Automated Restocking Recommendations</h4>
						<div class="row">
							@if(count($restockingRecommendations['urgent_restock']) > 0)
							<div class="col-md-6">
								<div class="alert alert-danger">
									<h6><strong>🔴 Urgent Restock Required ({{ count($restockingRecommendations['urgent_restock']) }} items)</strong></h6>
									@foreach(array_slice($restockingRecommendations['urgent_restock'], 0, 3) as $item)
									<div class="d-flex justify-content-between">
										<span>{{ $item['product_name'] }}</span>
										<span class="badge badge-danger">{{ $item['current_stock'] }} left</span>
									</div>
									@endforeach
								</div>
							</div>
							@endif

							@if(count($restockingRecommendations['monitor_closely']) > 0)
							<div class="col-md-6">
								<div class="alert alert-warning">
									<h6><strong>🟡 Monitor Closely ({{ count($restockingRecommendations['monitor_closely']) }} items)</strong></h6>
									@foreach(array_slice($restockingRecommendations['monitor_closely'], 0, 3) as $item)
									<div class="d-flex justify-content-between">
										<span>{{ $item['product_name'] }}</span>
										<span class="badge badge-warning">{{ $item['current_stock'] }} stock</span>
									</div>
									@endforeach
								</div>
							</div>
							@endif
						</div>
					</div>
				</div>
			</div>
			@endif

			<!-- Seasonality Analysis -->
			@if(isset($seasonalityAnalysis))
			<div class="row mb-4">
				<div class="col-md-6">
					<div class="stats-card">
						<h4>📅 Seasonal Pattern Analysis</h4>
						<div class="mb-3">
							<h6>Peak Sales Months:</h6>
							@foreach($seasonalityAnalysis['peak_months'] as $month)
							<span class="badge badge-success mr-1">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</span>
							@endforeach
						</div>
						<div class="mb-3">
							<h6>Low Sales Months:</h6>
							@foreach($seasonalityAnalysis['low_months'] as $month)
							<span class="badge badge-secondary mr-1">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</span>
							@endforeach
						</div>
						<small class="text-muted">
							Seasonality helps predict demand fluctuations and optimize inventory levels throughout the year.
						</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="stats-card">
						<h4>⚙️ SARIMA Model Parameters</h4>
						@if(isset($forecast['model_parameters']))
						<div class="row">
							<div class="col-6">
								<small><strong>AR Order (p):</strong> {{ $forecast['model_parameters']['p'] }}</small><br>
								<small><strong>Differencing (d):</strong> {{ $forecast['model_parameters']['d'] }}</small><br>
								<small><strong>MA Order (q):</strong> {{ $forecast['model_parameters']['q'] }}</small>
							</div>
							<div class="col-6">
								<small><strong>Seasonal AR (P):</strong> {{ $forecast['model_parameters']['P'] }}</small><br>
								<small><strong>Seasonal Diff (D):</strong> {{ $forecast['model_parameters']['D'] }}</small><br>
								<small><strong>Seasonal MA (Q):</strong> {{ $forecast['model_parameters']['Q'] }}</small>
							</div>
						</div>
						<div class="mt-2">
							<small><strong>Seasonal Period (s):</strong> {{ $forecast['model_parameters']['s'] }} months</small>
						</div>
						@endif
					</div>
				</div>
			</div>
			@endif

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
											
											if ($previousRevenue !== null && $previousRevenue > 0) {
												$growth = $revenue - $previousRevenue;
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
												<td><strong>₱{{ number_format($salesStats['current_month_revenue'], 2) }}</strong></td>
											@else
												<td><strong>₱{{ number_format($revenue, 2) }}</strong></td>
											@endif
											<td>{{ number_format($salesData) }} units</td>
											<td>
												@if($growth !== null)
													@if($growth > 0)
														<span class="text-success">
															<i class="fas fa-arrow-up"></i> ₱{{ number_format(abs($growth), 2) }}
															(+{{ number_format($growthPercentage, 1) }}%)
														</span>
													@elseif($growth < 0)
														<span class="text-danger">
															<i class="fas fa-arrow-down"></i> ₱{{ number_format(abs($growth), 2) }}
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
												@if($revenue > 100000)
													<span class="badge badge-success">High Sales</span>
												@elseif($revenue > 50000)
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
												<em>No historical sales data available. Please generate sales data using the seeder.</em>
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
										<th>{{ number_format($totalQuantity) }} units</th>
										<th colspan="2">
											<span class="text-muted">
												Avg: ₱{{ number_format(count($displayedMonths) > 0 ? $totalRevenue / count($displayedMonths) : 0, 2) }} / month
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
									@foreach($forecast['predicted'] as $month => $prediction)
									<tr>
										<td><strong>{{ Carbon\Carbon::parse($month)->format('M Y') }}</strong></td>
										<td>₱{{ number_format($prediction, 2) }}</td>
										<td>
											@if(isset($forecast['confidence_intervals'][$month]))
											<span class="text-muted">
												₱{{ number_format($forecast['confidence_intervals'][$month]['lower'], 0) }} -
												₱{{ number_format($forecast['confidence_intervals'][$month]['upper'], 0) }}
											</span>
											@endif
										</td>
                                        
										<td>
											@php
											$currentMonth = \Carbon\Carbon::now();
											$forecastMonth = \Carbon\Carbon::parse($month);
											$monthsAway = $currentMonth->diffInMonths($forecastMonth);
											@endphp
											@if($monthsAway <= 1)
												<span class="badge badge-danger">Prepare Inventory</span>
												@elseif($monthsAway <= 3)
													<span class="badge badge-warning">Monitor Trends</span>
													@else
													<span class="badge badge-info">Long-term Planning</span>
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
							<table class="table table-sm">
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
									@foreach(array_slice($demandForecast, 0, 10) as $productId => $productForecast)
									<tr>
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
										<td>{{ $productForecast['days_until_stockout'] > 365 ? '365+' : $productForecast['days_until_stockout'] }} days</td>
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
					</div>
				</div>
			</div>
			@endif

			<!-- Section Divider -->
			<div class="section-divider"></div>

			<div class="row">
				<!-- Record New Sale -->
				<div class="col-md-6">
					<div class="sales-form">
						<h4>📝 Record New Sale</h4>
						<form id="salesForm" onsubmit="return false;">
							@csrf
							<div class="form-group">
								<label for="product_id">Product</label>
								<select class="form-control" id="product_id" name="product_id" required onchange="simpleCalculate()">
									<option value="">-- Select a product --</option>
									@php
									$products = \App\Models\Product::all();
									@endphp
									@foreach($products as $product)
									<option value="{{ $product->id }}" 
											data-price="{{ $product->price }}" 
											data-stock="{{ $product->stock }}">
										{{ $product->name }} (Qty: {{ $product->stock }}, Price: ₱{{ number_format($product->price, 2) }})
									</option>
									@endforeach
								</select>
								<small class="text-muted">Choose a product from inventory</small>
							</div>
							<div class="form-group">
								<label for="quantity_sold">Quantity Sold</label>
								<input type="number" 
									   class="form-control" 
									   id="quantity_sold" 
									   name="quantity_sold" 
									   min="1" 
									   placeholder="Enter quantity"
									   required
									   oninput="simpleCalculate()" 
									   onchange="simpleCalculate()" 
									   onkeyup="simpleCalculate()">
								<small class="text-muted" id="stockInfo">Select a product first</small>
							</div>
							<div class="form-group">
								<label for="sale_date">Sale Date</label>
								<input type="date" 
									   class="form-control" 
									   id="sale_date" 
									   name="sale_date" 
									   value="{{ date('Y-m-d') }}" 
									   required>
								<small class="text-muted">Date of the transaction</small>
							</div>
							<div class="form-group">
								<label>Total Amount</label>
								<div class="input-group" style="box-shadow: 0 2px 4px rgba(0,0,0,0.08); border-radius: 8px; overflow: hidden;">
									<div class="input-group-prepend">
										<span class="input-group-text" style="background: #f8f9fa; border: 1px solid #dee2e6; border-right: none; font-weight: 600;">₱</span>
									</div>
									<div class="form-control bg-light" style="font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; border-left: none; background: #f8f9fa; border: 1px solid #dee2e6;">
										<span id="totalAmount" style="color: #6c757d;">0.00</span>
									</div>
								</div>
								<small class="form-text text-muted">💡 Calculated automatically: Product Price × Quantity</small>
							</div>
							<button type="button" id="recordSaleBtn" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 14px; font-size: 1rem;">
								<strong>💾 Record Sale</strong>
							</button>
						</form>
					</div>
				</div>

				<!-- Top Selling Products -->
				<div class="col-md-6">
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
	</div>

	<!-- Toast Container -->
	<div class="toast-container position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
		<div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px;">
			<div class="toast-header" style="background-color: #28a745; color: white;">
				<strong class="mr-auto">✅ Success</strong>
				<button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close" style="border: none; background: none; font-size: 1.2rem;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="toast-body" id="successToastBody">
				Sale recorded successfully!
			</div>
		</div>

		<div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px;">
			<div class="toast-header" style="background-color: #dc3545; color: white;">
				<strong class="mr-auto">❌ Error</strong>
				<button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close" style="border: none; background: none; font-size: 1.2rem;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="toast-body" id="errorToastBody">
				An error occurred!
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
		$(document).ready(function() {
			console.log('Document ready, testing functionality...');
			console.log('jQuery version:', $.fn.jquery);
			console.log('Form element found:', $('#salesForm').length > 0);
			console.log('Record button found:', $('#recordSaleBtn').length > 0);
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
		document.addEventListener('DOMContentLoaded', function() {
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
				new Chart(actualCtx, {
					type: 'line',
					data: {
						labels: actualMonths,
						datasets: [{
							label: 'Actual Sales',
							data: actualValues,
							borderColor: '#007bff',
							backgroundColor: 'rgba(0, 123, 255, 0.1)',
							borderWidth: 3,
							fill: true,
							tension: 0.4,
							pointRadius: 5,
							pointHoverRadius: 7,
							pointBackgroundColor: '#007bff'
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
									label: function(context) {
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
									callback: function(value) {
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
					const date = new Date(month + '-01');
					return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
				});
				const forecastValues = Object.values(sarimaData.predicted);
				new Chart(forecastCtx, {
					type: 'line',
					data: {
						labels: forecastMonths,
						datasets: [{
							label: 'SARIMA Forecast',
							data: forecastValues,
							borderColor: '#28a745',
							backgroundColor: 'rgba(40, 167, 69, 0.1)',
							borderWidth: 3,
							borderDash: [8, 4],
							fill: true,
							tension: 0.4,
							pointRadius: 5,
							pointHoverRadius: 7,
							pointBackgroundColor: '#28a745',
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
									label: function(context) {
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
									callback: function(value) {
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
		});
	</script>

	<script>
		// Sales form handling
		$(document).ready(function() {
			// Product selection change
			$('#product_id').change(function() {
				const selected = $(this).find(':selected');
				const stock = selected.data('stock');
				const price = selected.data('price');

				console.log('Product selected:', {
					stock: stock,
					price: price,
					productId: selected.val()
				});

				if (selected.val() && stock !== undefined) {
					$('#stockInfo').text(`Available stock: ${stock}`);
					$('#quantity_sold').attr('max', stock);
					updateTotal();
				} else {
					$('#stockInfo').text('');
					$('#quantity_sold').removeAttr('max');
					$('#totalAmount').text('₱0.00');
				}
			});

			// Quantity input change
			$('#quantity_sold').on('input keyup change', function() {
				updateTotal();
			});

			// Clear form when no product selected
			$('#product_id').on('change', function() {
				if (!$(this).val()) {
					$('#quantity_sold').val('');
					$('#totalAmount').text('₱0.00');
					$('#stockInfo').text('');
				}
			});
		});

		function updateTotal() {
			try {
				const selected = $('#product_id').find(':selected');
				const price = parseFloat(selected.data('price'));
				const quantity = parseInt($('#quantity_sold').val());

				console.log('Updating total:', {
					price: price,
					quantity: quantity,
					selectedValue: selected.val(),
					hasPrice: !isNaN(price),
					hasQuantity: !isNaN(quantity) && quantity > 0
				});

				if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
					const total = price * quantity;
					$('#totalAmount').text(total.toFixed(2));
					$('#totalAmount').css('color', '#28a745'); // Green color for calculated amount
					console.log('Total set to:', total.toFixed(2));
				} else {
					$('#totalAmount').text('0.00');
					$('#totalAmount').css('color', '#6c757d'); // Gray color for default
					console.log('Total reset to 0.00');
				}
			} catch (error) {
				console.error('Error in updateTotal:', error);
				$('#totalAmount').text('0.00');
			}
		}

		// Handle record sale button click
		$('#recordSaleBtn').on('click', function(e) {
			console.log('Record sale button clicked');

			// Validate required fields
			if (!$('#product_id').val() || !$('#quantity_sold').val() || !$('#sale_date').val()) {
				alert('Please fill in all required fields');
				return false;
			}

			// Validate quantity is positive
			const quantity = parseInt($('#quantity_sold').val());
			if (quantity <= 0) {
				alert('Quantity must be greater than 0');
				return false;
			}

			// Validate quantity does not exceed available stock
			const selectedProduct = $('#product_id').find(':selected');
			const availableStock = parseInt(selectedProduct.data('stock'));
			
			if (quantity > availableStock) {
				alert(`Insufficient stock! You entered ${quantity} but only ${availableStock} units are available.\n\nPlease enter a quantity of ${availableStock} or less.`);
				$('#quantity_sold').val(''); // Clear the invalid input
				$('#quantity_sold').focus(); // Focus back on quantity field
				return false;
			}

			// Get CSRF token from multiple sources for reliability
			let csrfToken = $('meta[name="csrf-token"]').attr('content');
			
			// Fallback: try to get from hidden input in form
			if (!csrfToken) {
				csrfToken = $('input[name="_token"]').val();
			}
			
			console.log('CSRF Token:', csrfToken);

			if (!csrfToken) {
				console.error('CSRF token not found!');
				alert('Security token not found. Please refresh the page (Press F5 or Ctrl+R).');
				// Auto-refresh after alert
				setTimeout(() => location.reload(), 1000);
				return false;
			}

			// Create form data manually to ensure CSRF token is included
			const formData = new FormData();
			formData.append('_token', csrfToken);
			formData.append('product_id', $('#product_id').val());
			formData.append('quantity_sold', $('#quantity_sold').val());
			formData.append('sale_date', $('#sale_date').val());

			console.log('Form data prepared with CSRF token');

			// Set up AJAX headers (double protection)
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': csrfToken
				}
			});

			console.log('Sending AJAX request to /sales');

			$.ajax({
				url: '/sales',
				method: 'POST',
				data: formData,
				processData: false, // Required for FormData
				contentType: false, // Required for FormData
				dataType: 'json',
				beforeSend: function() {
					// Show loading state
					console.log('Sending sale data...');
				},
				success: function(response) {
					console.log('Sale recorded successfully:', response);
					console.log('Updated statistics received:', response.updated_statistics);

					// Show success toast with details
					let message = `Sale recorded successfully!<br><br>`;
					message += `Product: ${response.sale_details.product_name}<br>`;
					message += `Quantity: ${response.sale_details.quantity_sold}<br>`;
					message += `Total Amount: ₱${response.sale_details.total_amount}<br>`;
					message += `Remaining Stock: ${response.sale_details.remaining_stock}`;

					showSuccessToast(message);

					// Reset form
					$('#salesForm')[0].reset();
					$('#totalAmount').text('0.00');
					$('#stockInfo').text('');

					// Update statistics on page without reload if available
					if (response.updated_statistics) {
						console.log('Calling updatePageStatistics with:', response.updated_statistics);
						updatePageStatistics(response.updated_statistics);
						console.log('Statistics update completed');
					} else {
						console.log('No updated_statistics in response');
					}

					// Don't reload immediately - let user see the updated stats
					setTimeout(function() {
						console.log('Reloading page to refresh all data...');
						location.reload();
					}, 5000);
				},
				error: function(xhr) {
					console.log('Error recording sale:', xhr);
					console.log('Response status:', xhr.status);
					console.log('Response text:', xhr.responseText);

					let errorMessage = 'Error recording sale!';

					// Handle specific CSRF token mismatch error
					if (xhr.status === 419) {
						errorMessage = 'Security token expired. Please refresh the page and try again.';
						console.error('CSRF Token Mismatch - Status 419');

						// Attempt to refresh CSRF token
						$.get('/forecasting', function(data) {
							const newToken = $(data).find('meta[name="csrf-token"]').attr('content');
							if (newToken) {
								$('meta[name="csrf-token"]').attr('content', newToken);
								console.log('Attempted to refresh CSRF token');
							}
						});
					} else {
						try {
							const response = JSON.parse(xhr.responseText);
							if (response.errors) {
								errorMessage = 'Validation Errors:\n';
								for (let field in response.errors) {
									errorMessage += `- ${field}: ${response.errors[field].join(', ')}\n`;
								}
							} else if (response.message) {
								errorMessage = response.message;
							}
						} catch (e) {
							errorMessage = `Error ${xhr.status}: ${xhr.responseText}`;
						}
					}

					showErrorToast(errorMessage);
				}
			});
		});

		// Update page statistics without reload
		function updatePageStatistics(stats) {
			try {
				console.log('updatePageStatistics called with:', stats);

				// Update revenue display
				const revenueElement = document.querySelector('[data-stat="current-month-revenue"]');
				if (revenueElement) {
					const oldValue = revenueElement.textContent;
					const newValue = '₱' + parseFloat(stats.current_month_revenue).toLocaleString('en-US', {
						minimumFractionDigits: 2
					});
					revenueElement.textContent = newValue;
					console.log('Revenue updated from', oldValue, 'to', newValue);
				} else {
					console.log('Revenue element not found');
				}

				// Update sales count
				const salesCountElement = document.querySelector('[data-stat="total-sales-count"]');
				if (salesCountElement) {
					const oldValue = salesCountElement.textContent;
					salesCountElement.textContent = stats.total_sales_count;
					console.log('Sales count updated from', oldValue, 'to', stats.total_sales_count);
				} else {
					console.log('Sales count element not found');
				}

				// Update growth percentage
				const growthElement = document.querySelector('[data-stat="growth-percentage"]');
				if (growthElement) {
					const growth = parseFloat(stats.growth_percentage);
					const oldValue = growthElement.textContent;
					const newValue = (growth > 0 ? '+' : '') + growth + '% from last month';
					growthElement.textContent = newValue;
					growthElement.className = growth > 0 ? 'growth-positive' : 'growth-negative';
					console.log('Growth updated from', oldValue, 'to', newValue);
				} else {
					console.log('Growth element not found');
				}

				console.log('Statistics updated successfully');
			} catch (error) {
				console.error('Error updating statistics:', error);
			}
		}

		// Simple vanilla JavaScript calculation (should always work)
		function simpleCalculate() {
			console.log('=== SIMPLE CALCULATE CALLED ===');

			const productSelect = document.getElementById('product_id');
			const quantityInput = document.getElementById('quantity_sold');
			const totalElement = document.getElementById('totalAmount');
			const stockElement = document.getElementById('stockInfo');

			if (!productSelect || !quantityInput || !totalElement) {
				console.error('Missing elements');
				return;
			}

			const selectedOption = productSelect.options[productSelect.selectedIndex];
			const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
			const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
			const quantity = parseFloat(quantityInput.value) || 0;

			console.log('Values:', {
				price,
				quantity,
				stock
			});

			// Update stock info with validation
			if (productSelect.value && stock) {
				if (quantity > stock) {
					stockElement.textContent = '⚠️ Insufficient stock! Available: ' + stock + ' (You entered: ' + quantity + ')';
					stockElement.style.color = '#dc3545'; // Red
					stockElement.style.fontWeight = 'bold';
					totalElement.style.color = '#dc3545';
				} else if (quantity > 0) {
					stockElement.textContent = '✓ Available stock: ' + stock;
					stockElement.style.color = '#28a745'; // Green
					stockElement.style.fontWeight = 'normal';
				} else {
					stockElement.textContent = 'Available stock: ' + stock;
					stockElement.style.color = '#6c757d'; // Gray
					stockElement.style.fontWeight = 'normal';
				}
				quantityInput.setAttribute('max', stock);
			} else {
				stockElement.textContent = '';
				stockElement.style.color = '#6c757d';
				quantityInput.removeAttribute('max');
			}

			// Calculate total
			if (price > 0 && quantity > 0) {
				const total = price * quantity;
				totalElement.textContent = total.toFixed(2);
				
				// Color based on stock validation
				if (quantity > stock) {
					totalElement.style.color = '#dc3545'; // Red if exceeds stock
				} else {
					totalElement.style.color = '#28a745'; // Green if valid
				}
				
				console.log('✓ Total calculated:', total.toFixed(2));
			} else {
				totalElement.textContent = '0.00';
				totalElement.style.color = '#6c757d';
				console.log('✓ Total reset to 0.00');
			}
		}

		// Toast utility functions
		function showSuccessToast(message) {
			console.log('Showing success toast:', message);
			$('#successToastBody').html(message);

			// Try Bootstrap 5 first, then Bootstrap 4
			if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
				console.log('Using Bootstrap 5 toast');
				const successToast = new bootstrap.Toast(document.getElementById('successToast'), {
					delay: 5000
				});
				successToast.show();
			} else if ($.fn.toast) {
				console.log('Using Bootstrap 4 toast');
				$('#successToast').toast({
					delay: 5000
				}).toast('show');
			} else {
				console.log('No toast support, using alert');
				// Fallback to alert if toast not available
				alert(message.replace(/<br>/g, '\n'));
			}
		}

		function showErrorToast(message) {
			console.log('Showing error toast:', message);
			$('#errorToastBody').html(message);

			// Try Bootstrap 5 first, then Bootstrap 4
			if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
				console.log('Using Bootstrap 5 toast');
				const errorToast = new bootstrap.Toast(document.getElementById('errorToast'), {
					delay: 8000
				});
				errorToast.show();
			} else if ($.fn.toast) {
				console.log('Using Bootstrap 4 toast');
				$('#errorToast').toast({
					delay: 8000
				}).toast('show');
			} else {
				console.log('No toast support, using alert');
				// Fallback to alert if toast not available
				alert(message.replace(/<br>/g, '\n'));
			}
		}
	</script>
</body>

</html>