@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
	<h1 style="margin-bottom: 0.25em;">Inventory Management</h1>
	<p style="margin-top: 0; color: #666;">Stock levels and automated reorder recommendations</p>

	<!-- Summary Cards -->
	<div style="display: flex; gap: 24px; margin-top: 32px; margin-bottom: 32px;">
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #3b82f6;">&#128230;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Total Products</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">{{ $totalProducts }}</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #ef4444;">&#9888;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Low Stock</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">{{ $lowStockCount }}</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #ff9800;">&#9888;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Critical</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">{{ $criticalStockCount }}</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #22c55e;">₱</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Total Value</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">₱{{ number_format($totalValue, 2) }}</span>
			</div>
		</div>
	</div>

	<!-- Search and Actions Bar -->
	<div style="display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<input type="text" placeholder="Search products..." style="flex: 1; padding: 10px 16px; border-radius: 8px; border: none; background: #f3f4f6; color: #555; font-size: 1rem;" />
		<button style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px 18px; font-size: 1rem; font-weight: 500; cursor: pointer;">Filter</button>
		<button id="addProductBtn" style="background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 1rem; font-weight: 600; cursor: pointer;">Add Product</button>
	</div>

	<!-- Add Product Modal -->
	<div id="addProductModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="position: relative; margin: 3% auto; background: white; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
			<div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 style="margin: 0; font-weight: 600;">Add Product</h5>
				<button type="button" id="closeModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
			</div>
			<form id="addProductForm" method="POST" action="/products">
				@csrf
				<div style="padding: 20px;">
					<div style="margin-bottom: 16px;">
						<label for="productName" style="display: block; margin-bottom: 5px; font-weight: 500;">Name</label>
						<input type="text" id="productName" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="productCategory" style="display: block; margin-bottom: 5px; font-weight: 500;">Category</label>
						<input type="text" id="productCategory" name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="productStock" style="display: block; margin-bottom: 5px; font-weight: 500;">Stock</label>
						<input type="number" id="productStock" name="stock" min="0" value="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
						<small style="color: #666; font-size: 0.85rem;">Status will be automatically set: Critical (≤5), Low Stock (6-10), In Stock (>10)</small>
					</div>
					<div style="margin-bottom: 16px;">
						<label for="productPrice" style="display: block; margin-bottom: 5px; font-weight: 500;">Price</label>
						<input type="number" step="0.01" id="productPrice" name="price" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="productReorder" style="display: block; margin-bottom: 5px; font-weight: 500;">Reorder Level</label>
						<input type="number" id="productReorder" name="reorder_level" min="0" value="10" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
				</div>
				<div style="padding: 20px; border-top: 1px solid #e0e0e0; display: flex; gap: 12px; justify-content: flex-end;">
					<button type="button" id="cancelBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Cancel</button>
					<button type="submit" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Add Product</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Product Modal -->
	<div id="editProductModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="position: relative; margin: 3% auto; background: white; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
			<div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 style="margin: 0; font-weight: 600;">Edit Product</h5>
				<button type="button" id="closeEditModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
			</div>
			<form id="editProductForm" method="POST">
				@csrf
				@method('PUT')
				<input type="hidden" id="editProductId" name="id">
				<div style="padding: 20px;">
					<div style="margin-bottom: 16px;">
						<label for="editProductName" style="display: block; margin-bottom: 5px; font-weight: 500;">Name</label>
						<input type="text" id="editProductName" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="editProductCategory" style="display: block; margin-bottom: 5px; font-weight: 500;">Category</label>
						<input type="text" id="editProductCategory" name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="editProductStock" style="display: block; margin-bottom: 5px; font-weight: 500;">Stock</label>
						<input type="number" id="editProductStock" name="stock" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
						<small style="color: #666; font-size: 0.85rem;">Status will be automatically updated: Critical (≤5), Low Stock (6-10), In Stock (>10)</small>
					</div>
					<div style="margin-bottom: 16px;">
						<label for="editProductPrice" style="display: block; margin-bottom: 5px; font-weight: 500;">Price</label>
						<input type="number" step="0.01" id="editProductPrice" name="price" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
					<div style="margin-bottom: 16px;">
						<label for="editProductReorder" style="display: block; margin-bottom: 5px; font-weight: 500;">Reorder Level</label>
						<input type="number" id="editProductReorder" name="reorder_level" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
					</div>
				</div>
				<div style="padding: 20px; border-top: 1px solid #e0e0e0; display: flex; gap: 12px; justify-content: flex-end;">
					<button type="button" id="cancelEditBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Cancel</button>
					<button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Update Product</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Toast Notification -->
	<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; min-width: 320px; z-index: 9999;">
		<div id="productToast" class="toast" data-delay="3000" style="opacity:0; background: #fff; color: #23272f; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); border-left: 8px solid #22c55e; border: 1px solid #e0e0e0; padding: 0;">
			<div style="display: flex; align-items: flex-start; padding: 18px 20px 18px 18px;">
				<div style="margin-right: 16px; display: flex; align-items: center; justify-content: center;">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="12" cy="12" r="12" fill="#22c55e" opacity="0.15" />
						<path d="M8 12.5l3 3 5-5" stroke="#22c55e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</div>
				<div style="flex:1;">
					<div id="toastTitle" style="font-weight: 700; font-size: 1.15rem; color: #23272f; margin-bottom: 2px;">Success</div>
					<div id="toastMessage" style="color: #666; font-size: 1.02rem;">Operation completed successfully!</div>
				</div>
				<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close" style="color: #888; opacity: 0.7; font-size: 1.3rem; margin-left: 12px; background: none; border: none; outline: none;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
		</div>
	</div>

	<!-- Scripts for Modal, AJAX, Toast, and Table Update -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		$(function() {
			// Setup CSRF token for all AJAX requests
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});

			// Show modal
			$('#addProductBtn').on('click', function() {
				$('#addProductModal').show();
			});

			// Hide modal
			$('#closeModal, #cancelBtn').on('click', function() {
				$('#addProductModal').hide();
			});

			// Click outside modal to close
			$('#addProductModal').on('click', function(e) {
				if (e.target.id === 'addProductModal') {
					$('#addProductModal').hide();
				}
			});

			// Form submission
			$('#addProductForm').on('submit', function(e) {
				e.preventDefault();
				var form = $(this);
				$.ajax({
					url: '/products',
					method: 'POST',
					data: form.serialize(),
					success: function(response) {
						$('#addProductModal').hide();
						form[0].reset();
						$('#toastTitle').text('Success');
						$('#toastMessage').text('Product added successfully!');
						$('#productToast').css('opacity', 1).show();
						setTimeout(function() {
							$('#productToast').hide();
						}, 3000);
						// Reload page to show new product
						setTimeout(function() {
							location.reload();
						}, 1200);
					},
					error: function(xhr) {
						alert('Error: ' + (xhr.responseJSON?.message || 'Could not add product.'));
					}
				});
			});

			// Show edit modal
			$(document).on('click', '.edit-btn', function() {
				var productId = $(this).data('id');
				var productName = $(this).data('name');
				var productCategory = $(this).data('category');
				var productStock = $(this).data('stock');
				var productStatus = $(this).data('status');
				var productPrice = $(this).data('price');
				var productReorder = $(this).data('reorder');

				// Populate form fields
				$('#editProductId').val(productId);
				$('#editProductName').val(productName);
				$('#editProductCategory').val(productCategory);
				$('#editProductStock').val(productStock);
				$('#editProductPrice').val(productPrice);
				$('#editProductReorder').val(productReorder);

				// Set form action
				$('#editProductForm').attr('action', '/products/' + productId);

				// Show modal
				$('#editProductModal').show();
			});

			// Hide edit modal
			$('#closeEditModal, #cancelEditBtn').on('click', function() {
				$('#editProductModal').hide();
			});

			// Click outside edit modal to close
			$('#editProductModal').on('click', function(e) {
				if (e.target.id === 'editProductModal') {
					$('#editProductModal').hide();
				}
			});

			// Edit form submission
			$('#editProductForm').on('submit', function(e) {
				e.preventDefault();
				var form = $(this);
				var productId = $('#editProductId').val();
				
				$.ajax({
					url: '/products/' + productId,
					method: 'POST',
					data: form.serialize(),
					success: function(response) {
						$('#editProductModal').hide();
						$('#toastTitle').text('Success');
						$('#toastMessage').text('Product updated successfully!');
						$('#productToast').css('opacity', 1).show();
						setTimeout(function() {
							$('#productToast').hide();
						}, 3000);
						// Reload page to show updated product
						setTimeout(function() {
							location.reload();
						}, 1200);
					},
					error: function(xhr) {
						alert('Error: ' + (xhr.responseJSON?.message || 'Could not update product.'));
					}
				});
			});

			// Delete product
			$(document).on('click', '.delete-btn', function() {
				var productId = $(this).data('id');
				var productName = $(this).data('name');
				
				if (confirm('Are you sure you want to delete "' + productName + '"?')) {
					var deleteButton = $(this);
					deleteButton.prop('disabled', true).text('Deleting...');
					
					// Create a form and submit it
					var form = $('<form>', {
						'method': 'POST',
						'action': '/products/' + productId,
						'style': 'display: none;'
					});
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': '_method',
						'value': 'DELETE'
					}));
					
					form.append($('<input>', {
						'type': 'hidden',
						'name': '_token',
						'value': '{{ csrf_token() }}'
					}));
					
					$('body').append(form);
					
					// Submit via AJAX
					$.ajax({
						url: form.attr('action'),
						type: 'POST',
						data: form.serialize(),
						dataType: 'json',
						success: function(response) {
							form.remove();
							if (response.success) {
								// Update toast message for delete operation
								$('#toastTitle').text('Success');
								$('#toastMessage').text('Product deleted successfully!');
								$('#productToast').css('opacity', 1).show();
								setTimeout(function() {
									$('#productToast').hide();
								}, 3000);
								// Reload page to remove deleted product
								setTimeout(function() {
									location.reload();
								}, 1200);
							} else {
								alert('Error: ' + (response.message || 'Could not delete product.'));
								deleteButton.prop('disabled', false).text('Delete');
							}
						},
						error: function(xhr, status, error) {
							form.remove();
							console.log('Delete error:', xhr.responseText);
							var message = 'Could not delete product.';
							if (xhr.responseJSON && xhr.responseJSON.message) {
								message = xhr.responseJSON.message;
							} else if (xhr.statusText) {
								message = xhr.statusText;
							}
							alert('Error: ' + message);
							deleteButton.prop('disabled', false).text('Delete');
						}
					});
				}
			});

			// Approve reorder recommendation
			$(document).on('click', '.approve-reorder-btn', function() {
				var productId = $(this).data('product-id');
				var quantity = $(this).data('quantity');
				var button = $(this);
				
				if (confirm('Approve reorder of ' + quantity + ' units for this product?')) {
					button.prop('disabled', true).text('Processing...');
					
					$.ajax({
						url: '/products/' + productId + '/approve-reorder',
						method: 'POST',
						data: { 
							quantity: quantity,
							_token: '{{ csrf_token() }}'
						},
						success: function(response) {
							if (response.success) {
								$('#toastTitle').text('Success');
								$('#toastMessage').text('Reorder approved successfully!');
								$('#productToast').css('opacity', 1).show();
								setTimeout(function() {
									$('#productToast').hide();
								}, 3000);
								// Reload page to update recommendations
								setTimeout(function() {
									location.reload();
								}, 1200);
							} else {
								alert('Error: ' + (response.message || 'Could not approve reorder.'));
								button.prop('disabled', false).text('Approve');
							}
						},
						error: function(xhr) {
							alert('Error: ' + (xhr.responseJSON?.message || 'Could not approve reorder.'));
							button.prop('disabled', false).text('Approve');
						}
					});
				}
			});
		});
	</script>

	<!-- Current Inventory Table -->
	<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; margin-bottom: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Current Inventory</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #fafbfc;">
						<th style="padding: 12px 8px; text-align: center; font-weight: 600; color: #23272f; width: 60px;">#</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Product ID</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Name</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Category</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Stock</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Price</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Status</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Actions</th>
					</tr>
				</thead>
				<tbody>
					@forelse($products as $product)
					<tr>
						<td style="padding: 10px 8px; text-align: center; font-weight: 500; color: #666;">{{ $loop->iteration }}</td>
						<td style="padding: 10px 8px;">{{ $product->id }}</td>
						<td style="padding: 10px 8px;">{{ $product->name }}</td>
						<td style="padding: 10px 8px;">{{ $product->category }}</td>
						<td style="padding: 10px 8px;">{{ $product->stock }}</td>
						<td style="padding: 10px 8px;">₱{{ number_format($product->price, 2) }}</td>
						<td style="padding: 10px 8px;">{{ $product->status }}</td>
						<td style="padding: 10px 8px;">
							<button class="edit-btn" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-category="{{ $product->category }}" data-stock="{{ $product->stock }}" data-status="{{ $product->status }}" data-price="{{ $product->price }}" data-reorder="{{ $product->reorder_level }}" style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 6px 16px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-right: 8px;">Edit</button>
							<button class="delete-btn" data-id="{{ $product->id }}" data-name="{{ $product->name }}" style="background: #dc3545; color: #fff; border: none; border-radius: 8px; padding: 6px 16px; font-size: 1rem; font-weight: 500; cursor: pointer;">Delete</button>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="8" style="padding: 10px 8px; text-align: center; color: #aaa;">No products found.</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>

	<!-- Reorder Recommendations -->
	<div id="reorder-recommendations" style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">
			Reorder Recommendations 
			@if($reorderRecommendations->count() > 0)
				<span style="background: #ef4444; color: #fff; border-radius: 12px; padding: 2px 8px; font-size: 0.8rem; margin-left: 8px;">{{ $reorderRecommendations->count() }}</span>
			@endif
		</div>
		<div style="display: flex; flex-direction: column; gap: 12px;">
			@forelse($reorderRecommendations as $recommendation)
			<div style="display: flex; align-items: center; background: #f6f6fa; border-radius: 10px; padding: 18px 20px; gap: 18px;">
				<div style="flex: 1;">
					<div style="font-weight: 500;">{{ $recommendation['name'] }}</div>
					<div style="font-size: 0.98rem; color: #888;">
						Current: {{ $recommendation['current_stock'] }} | 
						@if(isset($recommendation['dynamic_reorder_level']))
							SARIMA Reorder: {{ round($recommendation['dynamic_reorder_level']) }} | 
							Static: {{ $recommendation['static_reorder_level'] ?? 'N/A' }}
						@else
							Reorder at: {{ $recommendation['reorder_level'] ?? $recommendation['static_reorder_level'] ?? 'N/A' }}
						@endif
					</div>
				</div>
				@if($recommendation['priority'] === 'High')
					<span style="background: #ef4444; color: #fff; border-radius: 8px; padding: 4px 16px; font-size: 1rem; font-weight: 500;">
				@elseif($recommendation['priority'] === 'Medium')
					<span style="background: #fde68a; color: #b45309; border-radius: 8px; padding: 4px 16px; font-size: 1rem; font-weight: 500;">
				@else
					<span style="background: #d1fae5; color: #065f46; border-radius: 8px; padding: 4px 16px; font-size: 1rem; font-weight: 500;">
				@endif
					{{ $recommendation['priority'] }}
				</span>
				<div style="text-align: right;">
					<div style="color: #22c55e; font-size: 1rem; font-weight: 600;">{{ $recommendation['recommended_quantity'] }} units</div>
					@if(isset($recommendation['forecasted_demand']))
						<div style="color: #3b82f6; font-size: 0.8rem;">Forecast: {{ $recommendation['forecasted_demand'] }} units</div>
					@endif
					@if(isset($recommendation['algorithm']))
						<div style="color: #8b5cf6; font-size: 0.75rem;">{{ $recommendation['algorithm'] }}</div>
					@endif
					<div style="color: #666; font-size: 0.85rem;">₱{{ number_format($recommendation['estimated_cost'], 2) }}</div>
				</div>
				<button class="approve-reorder-btn" 
						data-product-id="{{ $recommendation['id'] }}" 
						data-quantity="{{ $recommendation['recommended_quantity'] }}"
						style="background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 1rem; font-weight: 600; cursor: pointer;">
					Approve
				</button>
			</div>
			@empty
			<div style="display: flex; align-items: center; justify-content: center; background: #f6f6fa; border-radius: 10px; padding: 40px 20px;">
				<div style="text-align: center;">
					<div style="font-size: 1.5rem; margin-bottom: 8px;">✅</div>
					<div style="font-weight: 500; color: #22c55e; margin-bottom: 4px;">All products are well-stocked!</div>
					<div style="font-size: 0.9rem; color: #888;">No reorder recommendations at this time.</div>
				</div>
			</div>
			@endforelse
		</div>
	</div>
</div>