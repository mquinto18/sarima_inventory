<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SARIMA Forecasting - Inventory System</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/topheader.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<style>
		.forecasting-container {
			margin-left: 220px;
			padding: 40px;
			padding-top: 90px;
			background: #fafbfc;
			min-height: 100vh;
		}

		.stats-card {
			background: white;
			border-radius: 10px;
			padding: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
			margin-bottom: 20px;
		}

		.stat-number {
			font-size: 2rem;
			font-weight: bold;
			color: #007bff;
		}

		.stat-label {
			color: #6c757d;
			font-size: 0.9rem;
		}

		.growth-positive {
			color: #28a745;
		}

		.growth-negative {
			color: #dc3545;
		}

		.chart-container {
			position: relative;
			height: 400px;
		}

		/* Bootstrap 4 Toast Styles */
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

		.stats-card {
			background: white;
			border-radius: 10px;
			padding: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
			margin-bottom: 20px;
		}

		.sales-form {
			background: white;
			border-radius: 10px;
			padding: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.btn-primary {
			background-color: #007bff;
			border: none;
		}

		.btn-primary:hover {
			background-color: #0056b3;
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
	</style>
</head>

<body>
	@include('components.topheader')
	@include('components.sidebar')

	<div class="forecasting-container">
		<div class="container-fluid">
			<h2 class="mb-4">📈 SARIMA Forecasting & Sales Analytics</h2>

			<!-- Statistics Cards -->
			<div class="row">
				<div class="col-md-3">
					<div class="stats-card text-center">
						<div class="stat-number" data-stat="current-month-revenue">₱{{ number_format($salesStats['current_month_revenue'], 2) }}</div>
						<div class="stat-label">This Month Revenue</div>
						@if($salesStats['growth_percentage'] != 0)
						<small class="growth-{{ $salesStats['growth_percentage'] > 0 ? 'positive' : 'negative' }}" data-stat="growth-percentage">
							{{ $salesStats['growth_percentage'] > 0 ? '+' : '' }}{{ $salesStats['growth_percentage'] }}% from last month
						</small>
						@endif
					</div>
				</div>
				<div class="col-md-3">
					<div class="stats-card text-center">
						<div class="stat-number" data-stat="total-sales-count">{{ $salesStats['total_sales_count'] }}</div>
						<div class="stat-label">Total Sales This Month</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="stats-card text-center">
						<div class="stat-number">₱{{ number_format($salesStats['average_order_value'], 2) }}</div>
						<div class="stat-label">Average Order Value</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="stats-card text-center">
						<div class="stat-number">{{ count($forecast['predicted']) }}</div>
						<div class="stat-label">Months Forecasted</div>
					</div>
				</div>
			</div>

			<!-- Enhanced SARIMA Analysis Dashboard -->
			<div class="row mb-4">
				<div class="col-12">
					<div class="stats-card">
						<h4>🔬 SARIMA Analysis & System Performance</h4>
						<div class="row">
							<div class="col-md-3">
								<div class="text-center">
									<div class="stat-number text-info">{{ isset($seasonalityAnalysis) ? $seasonalityAnalysis['trend_direction'] : 'stable' }}</div>
									<div class="stat-label">Market Trend</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="text-center">
									<div class="stat-number text-success">{{ isset($forecastAccuracy) ? number_format($forecastAccuracy['accuracy_percentage'], 1) : '0' }}%</div>
									<div class="stat-label">Forecast Accuracy</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="text-center">
									<div class="stat-number text-warning">{{ isset($seasonalityAnalysis) ? number_format($seasonalityAnalysis['seasonality_strength'], 2) : '0' }}</div>
									<div class="stat-label">Seasonality Index</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="text-center">
									<div class="stat-number text-primary">{{ isset($seasonalityAnalysis) ? number_format($seasonalityAnalysis['yearly_growth_rate'], 1) : '0' }}%</div>
									<div class="stat-label">Annual Growth Rate</div>
								</div>
							</div>
						</div>
					</div>
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

			<!-- SARIMA Forecast Chart -->
			<div class="row">
				<div class="col-12">
					<div class="chart-container">
						<h4>📊 Enhanced SARIMA Forecast - Revenue Prediction with Confidence Intervals</h4>
						<canvas id="forecastChart"></canvas>
						<div class="forecast-legend">
							<div class="legend-item">
								<div class="legend-color" style="background-color: #007bff;"></div>
								<span>Historical Data</span>
							</div>
							<div class="legend-item">
								<div class="legend-color" style="background-color: #28a745;"></div>
								<span>SARIMA Forecast</span>
							</div>
							<div class="legend-item">
								<div class="legend-color" style="background-color: rgba(40, 167, 69, 0.3);"></div>
								<span>95% Confidence Interval</span>
							</div>
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
										<th>Trend Component</th>
										<th>Seasonal Component</th>
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
											@if(isset($forecast['trend_component'][$month]))
											₱{{ number_format($forecast['trend_component'][$month], 0) }}
											@endif
										</td>
										<td>
											@if(isset($forecast['seasonal_component'][$month]))
											₱{{ number_format($forecast['seasonal_component'][$month], 0) }}
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
									@foreach(array_slice($demandForecast, 0, 10) as $productId => $forecast)
									<tr>
										<td><strong>{{ $forecast['product_name'] }}</strong></td>
										<td>{{ $forecast['current_stock'] }}</td>
										<td>{{ number_format($forecast['forecasted_demand'], 1) }}/month</td>
										<td>
											@if($forecast['risk_level'] === 'HIGH')
											<span class="badge badge-danger">HIGH</span>
											@elseif($forecast['risk_level'] === 'MEDIUM')
											<span class="badge badge-warning">MEDIUM</span>
											@else
											<span class="badge badge-success">LOW</span>
											@endif
										</td>
										<td>{{ $forecast['days_until_stockout'] > 365 ? '365+' : $forecast['days_until_stockout'] }} days</td>
										<td>
											<small>
												@if($forecast['recommended_order_quantity'] > 0)
												Order {{ $forecast['recommended_order_quantity'] }} units
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
									<option value="">Select a product...</option>
									@php
									$products = \App\Models\Product::all();
									@endphp
									@foreach($products as $product)
									<option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->stock }}">
										{{ $product->name }} (Stock: {{ $product->stock }}) - ₱{{ number_format($product->price, 2) }}
									</option>
									@endforeach
								</select>
							</div>
							<div class="form-group">
								<label for="quantity_sold">Quantity Sold</label>
								<input type="number" class="form-control" id="quantity_sold" name="quantity_sold" min="1" required
									oninput="simpleCalculate()" onchange="simpleCalculate()" onkeyup="simpleCalculate()">
								<small class="text-muted" id="stockInfo"></small>
							</div>
							<div class="form-group">
								<label for="sale_date">Sale Date</label>
								<input type="date" class="form-control" id="sale_date" name="sale_date" value="{{ date('Y-m-d') }}" required>
							</div>
							<div class="form-group">
								<label>Total Amount</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text">₱</span>
									</div>
									<div class="form-control bg-light" style="font-size: 1.1rem; font-weight: 600; display: flex; align-items: center;">
										<span id="totalAmount" style="color: #6c757d;">0.00</span>
									</div>
								</div>
								<small class="form-text text-info">Amount is calculated automatically based on product price and quantity</small>
							</div>
							<button type="button" id="recordSaleBtn" class="btn btn-primary btn-block">Record Sale</button>
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
		window.forecastData = <?php echo json_encode($forecast); ?>;
	</script>

	<script>
		// Enhanced SARIMA Forecast Chart with Confidence Intervals
		const ctx = document.getElementById('forecastChart').getContext('2d');
		const forecastData = window.forecastData;

		// Prepare chart data with confidence intervals
		const allMonths = [...(forecastData.months || []), ...Object.keys(forecastData.predicted || {})];
		const historicalData = [...(forecastData.historical || []), ...Array(Object.keys(forecastData.predicted || {}).length).fill(null)];
		const predictedData = [...Array((forecastData.historical || []).length).fill(null), ...Object.values(forecastData.predicted || {})];

		// Confidence interval data
		const upperBoundData = [];
		const lowerBoundData = [];

		if (forecastData.confidence_intervals) {
			// Fill historical part with nulls
			for (let i = 0; i < (forecastData.historical || []).length; i++) {
				upperBoundData.push(null);
				lowerBoundData.push(null);
			}

			// Add confidence interval data for forecast period
			Object.values(forecastData.confidence_intervals).forEach(interval => {
				upperBoundData.push(interval.upper);
				lowerBoundData.push(interval.lower);
			});
		}

		const chart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: allMonths,
				datasets: [{
						label: 'Historical Revenue',
						data: historicalData,
						borderColor: '#007bff',
						backgroundColor: 'rgba(0, 123, 255, 0.1)',
						borderWidth: 3,
						fill: false,
						tension: 0.1,
						pointRadius: 4,
						pointHoverRadius: 6
					},
					{
						label: 'SARIMA Forecast',
						data: predictedData,
						borderColor: '#28a745',
						backgroundColor: 'rgba(40, 167, 69, 0.1)',
						borderWidth: 3,
						borderDash: [8, 4],
						fill: false,
						tension: 0.1,
						pointRadius: 5,
						pointHoverRadius: 7
					},
					{
						label: '95% Confidence Upper',
						data: upperBoundData,
						borderColor: 'rgba(40, 167, 69, 0.3)',
						backgroundColor: 'rgba(40, 167, 69, 0.1)',
						borderWidth: 1,
						borderDash: [2, 2],
						fill: '+1',
						tension: 0.1,
						pointRadius: 0
					},
					{
						label: '95% Confidence Lower',
						data: lowerBoundData,
						borderColor: 'rgba(40, 167, 69, 0.3)',
						backgroundColor: 'rgba(40, 167, 69, 0.1)',
						borderWidth: 1,
						borderDash: [2, 2],
						fill: false,
						tension: 0.1,
						pointRadius: 0
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					intersect: false,
					mode: 'index'
				},
				scales: {
					y: {
						beginAtZero: true,
						grid: {
							color: 'rgba(0,0,0,0.1)'
						},
						ticks: {
							callback: function(value) {
								return '₱' + new Intl.NumberFormat().format(value);
							}
						}
					},
					x: {
						grid: {
							color: 'rgba(0,0,0,0.1)'
						}
					}
				},
				plugins: {
					legend: {
						display: false // We have custom legend
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
							}
						}
					}
				}
			}
		});

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

			// Get CSRF token
			const csrfToken = $('meta[name="csrf-token"]').attr('content');
			console.log('CSRF Token:', csrfToken);

			if (!csrfToken) {
				console.error('CSRF token not found!');
				alert('Security token not found. Please refresh the page.');
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
			const stock = selectedOption.getAttribute('data-stock') || '';
			const quantity = parseFloat(quantityInput.value) || 0;

			console.log('Values:', {
				price,
				quantity,
				stock
			});

			// Update stock info
			if (productSelect.value && stock) {
				stockElement.textContent = 'Available stock: ' + stock;
				quantityInput.setAttribute('max', stock);
			} else {
				stockElement.textContent = '';
				quantityInput.removeAttribute('max');
			}

			// Calculate total
			if (price > 0 && quantity > 0) {
				const total = price * quantity;
				totalElement.textContent = total.toFixed(2);
				totalElement.style.color = '#28a745';
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