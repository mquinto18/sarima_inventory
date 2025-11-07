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

			<!-- SARIMA Forecast Chart -->
			<div class="row">
				<div class="col-12">
					<div class="chart-container">
						<h4>📊 SARIMA Forecast - Revenue Prediction</h4>
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
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<!-- Record New Sale -->
				<div class="col-md-6">
					<div class="sales-form">
						<h4>📝 Record New Sale</h4>
						<form id="salesForm">
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
							<button type="submit" class="btn btn-primary btn-block">Record Sale</button>
							<button type="button" class="btn btn-secondary btn-sm mt-2" onclick="debugForm()">🔧 Debug Form</button>
							<button type="button" class="btn btn-info btn-sm mt-1" onclick="testAjax()">🧪 Test AJAX Connection</button>
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

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Debug jQuery loading -->
	<script>
		if (typeof jQuery === 'undefined') {
			console.error('jQuery is not loaded!');
		} else {
			console.log('jQuery loaded successfully, version:', jQuery.fn.jquery);
		}
	</script>

	<!-- Pass PHP data to JavaScript -->
	<script type="text/javascript">
		window.forecastData = <?php echo json_encode($forecast); ?>;
	</script>

	<script>
		// SARIMA Forecast Chart
		const ctx = document.getElementById('forecastChart').getContext('2d');
		const forecastData = window.forecastData;

		// Prepare chart data
		const allMonths = [...forecastData.months, ...Object.keys(forecastData.predicted)];
		const historicalData = [...forecastData.historical, ...Array(Object.keys(forecastData.predicted).length).fill(null)];
		const predictedData = [...Array(forecastData.historical.length).fill(null), ...Object.values(forecastData.predicted)];

		const chart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: allMonths,
				datasets: [{
						label: 'Historical Revenue',
						data: historicalData,
						borderColor: '#007bff',
						backgroundColor: 'rgba(0, 123, 255, 0.1)',
						borderWidth: 2,
						fill: false,
						tension: 0.1
					},
					{
						label: 'SARIMA Forecast',
						data: predictedData,
						borderColor: '#28a745',
						backgroundColor: 'rgba(40, 167, 69, 0.1)',
						borderWidth: 2,
						borderDash: [5, 5],
						fill: false,
						tension: 0.1
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							callback: function(value) {
								return '₱' + value.toLocaleString();
							}
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

		$('#salesForm').submit(function(e) {
			e.preventDefault();

			console.log('🚀 Form submitted!');

			const formData = $(this).serialize();
			console.log('📋 Form data:', formData);
			
			// Debug form values
			console.log('🔍 Debug form values:');
			console.log('- Product ID:', $('#product_id').val());
			console.log('- Quantity:', $('#quantity_sold').val());
			console.log('- Sale Date:', $('#sale_date').val());
			console.log('- Total Amount:', $('#totalAmount').text());

			// Validate required fields
			if (!$('#product_id').val() || !$('#quantity_sold').val() || !$('#sale_date').val()) {
				alert('❌ Please fill in all required fields');
				return false;
			}

			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});

			console.log('Sending AJAX request to /sales');

			$.ajax({
				url: '/sales',
				method: 'POST',
				data: formData,
				beforeSend: function() {
					console.log('🔄 AJAX request started');
					console.log('📡 Sending to URL:', '/sales');
					console.log('📦 Data being sent:', formData);
				},
				success: function(response) {
					console.log('Success response:', response);
					
					// Show success message with details
					let message = `✅ Sale recorded successfully!\n\n`;
					message += `Product: ${response.sale_details.product_name}\n`;
					message += `Quantity: ${response.sale_details.quantity_sold}\n`;
					message += `Total Amount: ₱${response.sale_details.total_amount}\n`;
					message += `Remaining Stock: ${response.sale_details.remaining_stock}`;
					
					alert(message);
					
					// Reset form
					$('#salesForm')[0].reset();
					$('#totalAmount').text('0.00');
					$('#stockInfo').text('');
					
					// Update statistics on page without reload if available
					if (response.updated_statistics) {
						updatePageStatistics(response.updated_statistics);
					}
					
					// Still reload to ensure all data is fresh (including charts)
					setTimeout(function() {
						location.reload();
					}, 2000);
				},
				error: function(xhr) {
					console.error('❌ AJAX error:', xhr);
					console.error('📊 Status:', xhr.status);
					console.error('📝 Response text:', xhr.responseText);
					console.error('🔍 Headers:', xhr.getAllResponseHeaders());

					let errorDetails = `❌ Error recording sale!\n\n`;
					errorDetails += `Status: ${xhr.status}\n`;
					errorDetails += `Status Text: ${xhr.statusText}\n\n`;

					try {
						const response = JSON.parse(xhr.responseText);
						console.error('📋 Parsed response:', response);
						
						if (response.errors) {
							errorDetails += 'Validation Errors:\n';
							for (let field in response.errors) {
								errorDetails += `- ${field}: ${response.errors[field].join(', ')}\n`;
							}
						} else if (response.message) {
							errorDetails += `Message: ${response.message}\n`;
						}
					} catch (e) {
						errorDetails += `Raw Response: ${xhr.responseText}\n`;
					}

					alert(errorDetails);
				}
			});
		});

		// Update page statistics without reload
		function updatePageStatistics(stats) {
			try {
				// Update revenue display
				const revenueElement = document.querySelector('[data-stat="current-month-revenue"]');
				if (revenueElement) {
					revenueElement.textContent = '₱' + parseFloat(stats.current_month_revenue).toLocaleString('en-US', {minimumFractionDigits: 2});
				}
				
				// Update sales count
				const salesCountElement = document.querySelector('[data-stat="total-sales-count"]');
				if (salesCountElement) {
					salesCountElement.textContent = stats.total_sales_count;
				}
				
				// Update growth percentage
				const growthElement = document.querySelector('[data-stat="growth-percentage"]');
				if (growthElement) {
					const growth = parseFloat(stats.growth_percentage);
					growthElement.textContent = (growth > 0 ? '+' : '') + growth + '% from last month';
					growthElement.className = growth > 0 ? 'growth-positive' : 'growth-negative';
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

		// Debug function
		function debugForm() {
			console.log('=== FORM DEBUG ===');
			console.log('Product ID:', $('#product_id').val());
			console.log('Quantity:', $('#quantity_sold').val());
			console.log('Sale Date:', $('#sale_date').val());
			console.log('Total Amount:', $('#totalAmount').text());
			console.log('Form serialized:', $('#salesForm').serialize());
			
			alert('Check console for debug info');
		}

		// Test AJAX connection
		function testAjax() {
			console.log('🧪 Testing AJAX connection...');
			
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});

			$.ajax({
				url: '/test/sales',
				method: 'GET',
				success: function(response) {
					console.log('✅ AJAX connection working!', response);
					alert('✅ AJAX connection is working!\n\nTotal Sales: ' + response.total_sales_count + '\nRecent Sales: ' + response.recent_sales.length);
				},
				error: function(xhr) {
					console.error('❌ AJAX connection failed:', xhr);
					alert('❌ AJAX connection failed. Check console for details.');
				}
			});
		}
	</script>
</body>

</html>
<div style="background: #ededf2; border-radius: 12px; min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #888;">
	<span style="font-size: 3rem; margin-bottom: 8px;">&#128202;</span>
	<div style="font-size: 1.2rem; font-weight: 500; color: #444;">SARIMA Forecast Chart</div>
	<div style="font-size: 1rem; color: #888;">Historical + Predicted Sales</div>
</div>
</div>

<!-- Detailed Predictions Table -->
<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
	<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Detailed Predictions</div>
	<div style="overflow-x: auto;">
		<table style="width: 100%; border-collapse: collapse;">
			<thead>
				<tr style="background: #fafbfc;">
					<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Period</th>
					<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Predicted Sales</th>
					<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Lower Bound</th>
					<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Upper Bound</th>
					<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Confidence</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="padding: 10px 8px;"></td>
					<td style="padding: 10px 8px;"></td>
					<td style="padding: 10px 8px; color: #ef4444;"></td>
					<td style="padding: 10px 8px; color: #22c55e;"></td>
					<td style="padding: 10px 8px;"></td>
				</tr>
				<tr>
					<td style="padding: 10px 8px;"></td>
					<td style="padding: 10px 8px;"></td>
					<td style="padding: 10px 8px; color: #ef4444;"></td>
					<td style="padding: 10px 8px; color: #22c55e;"></td>
					<td style="padding: 10px 8px;"></td>
				</tr>
				<!-- Add more rows as needed -->
			</tbody>
		</table>
		<!-- Data rows will be dynamically inserted here -->
	</div>