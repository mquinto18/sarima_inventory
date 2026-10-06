@extends('layouts.app')

@section('content')
<style>
	/* Fix notification bell clickable area for this page */
	.notification-bell {
		width: 50px !important;
		height: 50px !important;
		padding: 12px !important;
		cursor: pointer !important;
		pointer-events: auto !important;
	}

	.notification-bell svg,
	.notification-bell span {
		pointer-events: none !important;
	}

	@media (max-width: 900px) {

		.inventory-table th,
		.inventory-table td {
			padding: 10px 6px;
			font-size: 0.98rem;
		}
	}
</style>
<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">Inventory Management</div>
			<div class="dashboard-hero-sub">
				{{ $totalProducts }} products tracked
				@if($criticalStockCount > 0)
					— {{ $criticalStockCount }} at critical stock and need urgent attention.
				@elseif($lowStockCount > 0)
					— {{ $lowStockCount }} running low on stock.
				@else
					— everything is well-stocked.
				@endif
			</div>
		</div>
		<div class="dashboard-hero-actions">
			@if(Auth::user()->role !== 'staff')
				<a href="javascript:void(0)" onclick="document.getElementById('addProductModal').style.display='flex'" class="btn-action edit">+ Add Product</a>
				<a href="javascript:void(0)" onclick="document.getElementById('reorderRecommendationsModal').style.display='flex'" class="btn-action ghost">View Reorder Recommendations</a>
			@else
				<a href="javascript:void(0)" onclick="document.getElementById('editRequestBtn') && document.getElementById('editRequestBtn').click()" class="btn-action edit">Submit Edit Request</a>
			@endif
		</div>
	</div>
	<div class="stat-grid">
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="7" width="18" height="13" rx="2" />
					<path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Products</div>
			<div class="main">{{ $totalProducts }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--warning">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
						stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Low Stock</div>
			<div class="main">{{ $lowStockCount }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--danger">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
						stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Critical</div>
			<div class="main">{{ $criticalStockCount }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--success">
				<span class="currency-icon">₱</span>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Value</div>
			<div class="main">₱{{ number_format($totalValue, 2) }}</div>
		</div>
	</div>

	<!-- Search and Filter Bar -->
	<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 22px;">
		<div style="position: relative; flex: 1;">
			<input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search products..."
				style="width: 100%; padding: 14px 18px 14px 44px; border-radius: var(--radius-md); border: 2px solid var(--color-primary); font-size: 1.15rem; background: var(--color-page-bg); box-shadow: var(--shadow-md); transition: border 0.2s; outline: none;">
			<span
				style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--color-primary); pointer-events: none;">
				<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
					<circle cx="11" cy="11" r="8" />
					<line x1="21" y1="21" x2="16.65" y2="16.65" />
				</svg>
			</span>
		</div>
		<div style="position: relative;">
			<button type="button" id="filterToggleBtn" title="Filter products" aria-label="Filter products"
				style="display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: var(--radius-md); border: 2px solid var(--color-primary); background: var(--color-page-bg); color: var(--color-primary); cursor: pointer; box-shadow: var(--shadow-md); transition: background 0.2s;">
				<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
					<path d="M4 5h16M7 12h10M10 19h4" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
				<span id="filterActiveDot" style="display:none; position:absolute; top:6px; right:6px; width:9px; height:9px; border-radius:50%; background: var(--color-danger); border: 2px solid var(--color-page-bg);"></span>
			</button>
			<div id="filterPanel"
				style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); padding: 18px; min-width: 220px; z-index: 200;">
				<div style="margin-bottom: 14px;">
					<label for="filterStatus" style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; color: var(--color-text);">Status</label>
					<select id="filterStatus" onchange="applyFilters()" style="width: 100%; padding: 8px 10px; border-radius: var(--radius-sm); border: 1.5px solid #e5e7eb; font-size: 0.95rem;">
						<option value="">All</option>
						<option value="Critical">Critical</option>
						<option value="Low Stock">Low Stock</option>
						<option value="In Stock">In Stock</option>
					</select>
				</div>
				<div style="margin-bottom: 16px;">
					<label for="filterCategory" style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; color: var(--color-text);">Category</label>
					<select id="filterCategory" onchange="applyFilters()" style="width: 100%; padding: 8px 10px; border-radius: var(--radius-sm); border: 1.5px solid #e5e7eb; font-size: 0.95rem;">
						<option value="">All</option>
						@foreach($products->pluck('category')->unique()->sort() as $category)
							<option value="{{ $category }}">{{ $category }}</option>
						@endforeach
					</select>
				</div>
				<button type="button" id="clearFiltersBtn" class="btn-action" style="background: #6c757d; width: 100%; padding: 8px;">Clear Filters</button>
			</div>
		</div>
	</div>

	@php
		// Categories already in use, plus the original built-in set so a fresh
		// database still offers sensible choices. Anything added through
		// "+ Add new category" shows up here on the next load.
		$categoryOptions = $products->pluck('category')
			->merge(['Medicine', 'Vitamins & Supplements', 'Medical Supplies', 'Personal Care'])
			->map(fn ($c) => trim((string) $c))
			->filter()
			->unique()
			->sort(SORT_NATURAL | SORT_FLAG_CASE)
			->values();
	@endphp

	<!-- Add Product Modal -->
	<div id="addProductModal"
		class="modal-overlay" style="display: none;">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 500px;">
			<div
				style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Add Product</h5>
				<button type="button" id="closeModal"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<form id="addProductForm" method="POST" action="/products">
				@csrf
				<div style="padding: 24px 28px;">
					<div style="margin-bottom: 18px;">
						<label for="productName"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Name</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input list="productNames" name="name" id="productName" required>
						</div>
						<datalist id="productNames">
							<option value="Paracetamol 500mg">
							<option value="Amoxicillin 500mg">
							<option value="Vitamin C 500mg">
							<option value="Cough Syrup">
							<option value="Antiseptic Solution">
							<option value="Surgical Face Mask">
						</datalist>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="productCategory"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Category</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24L4 3a1 1 0 0 0-1 1l.24 5.59a2 2 0 0 0 .59 1.41l9.58 9.58a2 2 0 0 0 2.83 0l4.35-4.35a2 2 0 0 0 0-2.82z" stroke-linecap="round" stroke-linejoin="round" />
								<circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" stroke="none" />
							</svg>
							{{-- No name attribute: the value that actually gets submitted
								 lives in the hidden field below, so the dropdown and the
								 "new category" box can never both post a `category`. --}}
							<select id="productCategory" required>
								<option value="">Select Category</option>
								@foreach($categoryOptions as $categoryOption)
									<option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
								@endforeach
								<option value="__new__">+ Add new category…</option>
							</select>
						</div>

						<div id="newCategoryWrap" style="display: none; margin-top: 10px;">
							<div class="form-input-group">
								<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M12 5v14M5 12h14" stroke-linecap="round" />
								</svg>
								<input type="text" id="productCategoryNew" maxlength="255"
									placeholder="New category name" autocomplete="off">
							</div>
							<small style="color: var(--color-text-muted); font-size: 0.85rem;">
								It will appear in this list for future products.
							</small>
						</div>

						<input type="hidden" name="category" id="productCategoryValue">
					</div>
					<div style="margin-bottom: 18px;">
						<label for="productStock"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Stock</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<select id="productStock" name="stock" required>
								<option value="">Select Stock</option>
								@for($i = 1; $i <= 500; $i++)
									<option value="{{ $i }}">{{ $i }}</option>
								@endfor
							</select>
						</div>
						<small style="color: var(--color-text-muted); font-size: 0.92rem;">Status will be automatically set: Critical (≤{{ $criticalLevel }}),
							Low Stock ({{ $criticalLevel + 1 }}-{{ $lowThreshold }}), In Stock (>{{ $lowThreshold }})</small>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="productPrice"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Price</label>
						<div class="form-input-group">
							<span class="form-input-icon currency-icon">₱</span>
							<input type="number" step="0.01" id="productPrice" name="price">
						</div>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="productReorder"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Reorder
							Level</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11c0-3.1-1.6-5.6-5-6V4a1 1 0 1 0-2 0v1c-3.4.4-5 2.9-5 6v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input type="number" id="productReorder" name="reorder_level" min="0" value="10" required>
						</div>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="productExpiry"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Expiration Date</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input type="date" id="productExpiry" name="expiry_date">
						</div>
						<small style="color: var(--color-text-muted); font-size: 0.85rem;">Leave blank if this product doesn't expire or the batch isn't tracked.</small>
					</div>
				</div>
				<div
					style="padding: 24px 28px; border-top: 1px solid #e0e0e0; display: flex; gap: 14px; justify-content: flex-end;">
					<button type="button" id="cancelBtn" class="btn-action" style="background: #6c757d;">Cancel</button>
					<button type="submit" class="btn-action edit">Add
						Product</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Product Modal -->
	<div id="editProductModal"
		class="modal-overlay" style="display: none;">
		<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 500px;">
			<div
				style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
				<h5 style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Edit Product</h5>
				<button type="button" id="closeEditModal"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
			</div>
			<form id="editProductForm" method="POST">
				@csrf
				@method('PUT')
				<input type="hidden" id="editProductId" name="id">
				<div style="padding: 24px 28px;">
					<div style="margin-bottom: 18px;">
						<label for="editProductName"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Name</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input type="text" id="editProductName" name="name" required>
						</div>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="editProductCategory"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Category</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24L4 3a1 1 0 0 0-1 1l.24 5.59a2 2 0 0 0 .59 1.41l9.58 9.58a2 2 0 0 0 2.83 0l4.35-4.35a2 2 0 0 0 0-2.82z" stroke-linecap="round" stroke-linejoin="round" />
								<circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" stroke="none" />
							</svg>
							<input type="text" id="editProductCategory" name="category">
						</div>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="editProductStock"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Stock</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M3 7l9 5 9-5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input type="number" id="editProductStock" name="stock" min="0" required>
						</div>
						<small style="color: var(--color-text-muted); font-size: 0.92rem;">Status will be automatically updated: Critical
							(≤{{ $criticalLevel }}), Low Stock ({{ $criticalLevel + 1 }}-{{ $lowThreshold }}), In Stock (>{{ $lowThreshold }})</small>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="editProductPrice"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Price</label>
						<div class="form-input-group">
							<span class="form-input-icon currency-icon">₱</span>
							<input type="number" step="0.01" id="editProductPrice" name="price">
						</div>
					</div>
					<div style="margin-bottom: 18px;">
						<label for="editProductReorder"
							style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Reorder Level</label>
						<div class="form-input-group">
							<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11c0-3.1-1.6-5.6-5-6V4a1 1 0 1 0-2 0v1c-3.4.4-5 2.9-5 6v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<input type="number" id="editProductReorder" name="reorder_level" min="0" required>
						</div>
					</div>
						<div style="margin-bottom: 18px;">
							<label for="editProductExpiry"
								style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Expiration Date</label>
							<div class="form-input-group">
								<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
								<input type="date" id="editProductExpiry" name="expiry_date">
							</div>
							<small style="color: var(--color-text-muted); font-size: 0.85rem;">Leave blank if this product doesn't expire or the batch isn't tracked.</small>
						</div>
				</div>
				<div
					style="padding: 24px 28px; border-top: 1px solid #e0e0e0; display: flex; gap: 14px; justify-content: flex-end;">
					<button type="button" id="cancelEditBtn" class="btn-action" style="background: #6c757d;">Cancel</button>
					<button type="submit" class="btn-action edit">Update
						Product</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Scripts for Modal, AJAX, Toast, and Table Update -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script>
		$(function () {
			// Edit Request button AJAX (single request per user)
			$('#editRequestBtn').on('click', function () {
				var btn = $(this).get(0);
				setButtonLoading(btn, true, 'Submitting...');
				$.ajax({
					url: '/edit-requests',
					method: 'POST',
					data: {
						request_details: '',
						_token: '{{ csrf_token() }}'
					},
					success: function (response) {
						showToast('Edit request has been submitted', 'success');
						setTimeout(function () {
							location.reload();
						}, 1200);
					},
					error: function (xhr) {
						setButtonLoading(btn, false);
						showToast('Could not submit edit request.', 'error');
					}
				});
			});
			// Setup CSRF token for all AJAX requests
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});

			// Hide modal
			// --- Category picker with an inline "add new" option ---
			var categorySelect = document.getElementById('productCategory');
			var newCategoryWrap = document.getElementById('newCategoryWrap');
			var newCategoryInput = document.getElementById('productCategoryNew');
			var categoryValue = document.getElementById('productCategoryValue');

			/**
			 * Mirrors whichever control is active into the single hidden field
			 * that actually gets submitted.
			 */
			function syncCategory() {
				var addingNew = categorySelect.value === '__new__';

				newCategoryWrap.style.display = addingNew ? 'block' : 'none';
				newCategoryInput.required = addingNew;

				categoryValue.value = addingNew ? newCategoryInput.value.trim() : categorySelect.value;
			}

			/**
			 * Reuse an existing category's spelling when the typed name matches
			 * one case-insensitively, so "medicine" doesn't become a second
			 * category alongside "Medicine".
			 */
			function normaliseNewCategory() {
				var typed = newCategoryInput.value.trim();
				if (!typed) return;

				var match = Array.prototype.find.call(categorySelect.options, function (opt) {
					return opt.value && opt.value !== '__new__'
						&& opt.value.toLowerCase() === typed.toLowerCase();
				});

				if (match) {
					categorySelect.value = match.value;
					newCategoryInput.value = '';
				}
				syncCategory();
			}

			function resetCategoryField() {
				categorySelect.value = '';
				newCategoryInput.value = '';
				syncCategory();
			}

			categorySelect.addEventListener('change', function () {
				syncCategory();
				if (categorySelect.value === '__new__') {
					newCategoryInput.focus();
				}
			});
			newCategoryInput.addEventListener('input', syncCategory);
			newCategoryInput.addEventListener('blur', normaliseNewCategory);
			syncCategory();

			$('#closeModal, #cancelBtn').on('click', function () {
				resetCategoryField();
				$('#addProductModal').hide();
			});

			// Click outside modal to close
			$('#addProductModal').on('click', function (e) {
				if (e.target.id === 'addProductModal') {
					resetCategoryField();
					$('#addProductModal').hide();
				}
			});

			// Form submission
			$('#addProductForm').on('submit', function (e) {
				e.preventDefault();
				var form = $(this);

				// Guard against a whitespace-only new category slipping through:
				// `required` on the text box is satisfied by " ".
				normaliseNewCategory();
				if (!categoryValue.value) {
					showToast('Please choose a category or enter a new one.', 'error');
					(categorySelect.value === '__new__' ? newCategoryInput : categorySelect).focus();
					return;
				}

				var submitBtn = form.find('button[type="submit"]').get(0);
				setButtonLoading(submitBtn, true, 'Adding...');
				$.ajax({
					url: '/products',
					method: 'POST',
					data: form.serialize(),
					success: function (response) {
						setButtonLoading(submitBtn, false);
						$('#addProductModal').hide();
						form[0].reset();
						resetCategoryField();
						showToast('Product added successfully!', 'success');
						// Reload page to show new product
						setTimeout(function () {
							location.reload();
						}, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						showToast(xhr.responseJSON?.message || 'Could not add product.', 'error');
					}
				});
			});

			// Show edit modal
			$(document).on('click', '.edit-btn', function () {
				var productId = $(this).data('id');
				var productName = $(this).data('name');
				var productCategory = $(this).data('category');
				var productStock = $(this).data('stock');
				var productStatus = $(this).data('status');
				var productPrice = $(this).data('price');
				var productReorder = $(this).data('reorder');
				var productExpiry = $(this).data('expiry');

				// Populate form fields
				$('#editProductId').val(productId);
				$('#editProductName').val(productName);
				$('#editProductCategory').val(productCategory);
				$('#editProductStock').val(productStock);
				$('#editProductPrice').val(productPrice);
				$('#editProductReorder').val(productReorder);
				$('#editProductExpiry').val(productExpiry || '');

				// Set form action
				$('#editProductForm').attr('action', '/products/' + productId);

				// Show modal
				$('#editProductModal').show();
			});

			// Hide edit modal
			$('#closeEditModal, #cancelEditBtn').on('click', function () {
				$('#editProductModal').hide();
			});

			// Click outside edit modal to close
			$('#editProductModal').on('click', function (e) {
				if (e.target.id === 'editProductModal') {
					$('#editProductModal').hide();
				}
			});

			// Edit form submission
			$('#editProductForm').on('submit', function (e) {
				e.preventDefault();
				var form = $(this);
				var productId = $('#editProductId').val();
				var submitBtn = form.find('button[type="submit"]').get(0);
				setButtonLoading(submitBtn, true, 'Updating...');

				$.ajax({
					url: '/products/' + productId,
					method: 'POST',
					data: form.serialize(),
					success: function (response) {
						setButtonLoading(submitBtn, false);
						$('#editProductModal').hide();
						showToast('Product updated successfully!', 'success');
						// Reload page to show updated product
						setTimeout(function () {
							location.reload();
						}, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						showToast(xhr.responseJSON?.message || 'Could not update product.', 'error');
					}
				});
			});

			// Delete product
			$(document).on('click', '.delete-btn', function () {
				var productId = $(this).data('id');
				var productName = $(this).data('name');
				var deleteButton = $(this);

				confirmDialog('Are you sure you want to delete "' + productName + '"? This cannot be undone.', {
					title: 'Delete product',
					confirmText: 'Delete'
				}).then(function (confirmed) {
					if (!confirmed) return;

					setButtonLoading(deleteButton.get(0), true, 'Deleting...');

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
						success: function (response) {
							form.remove();
							if (response.success) {
								showToast('Product deleted successfully!', 'success');
								// Reload page to remove deleted product
								setTimeout(function () {
									location.reload();
								}, 1200);
							} else {
								setButtonLoading(deleteButton.get(0), false);
								showToast(response.message || 'Could not delete product.', 'error');
							}
						},
						error: function (xhr, status, error) {
							form.remove();
							console.log('Delete error:', xhr.responseText);
							var message = 'Could not delete product.';
							if (xhr.responseJSON && xhr.responseJSON.message) {
								message = xhr.responseJSON.message;
							} else if (xhr.statusText) {
								message = xhr.statusText;
							}
							setButtonLoading(deleteButton.get(0), false);
							showToast(message, 'error');
						}
					});
				});
			});

		});

		function applyFilters() {
			const searchInput = document.getElementById('searchInput');
			const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
			const statusSelect = document.getElementById('filterStatus');
			const categorySelect = document.getElementById('filterCategory');
			const statusFilter = statusSelect ? statusSelect.value : '';
			const categoryFilter = categorySelect ? categorySelect.value : '';

			const rows = document.querySelectorAll('.inventory-table tbody tr');
			rows.forEach(row => {
				const matchesSearch = !searchTerm || row.textContent.toLowerCase().includes(searchTerm);
				const matchesStatus = !statusFilter || row.dataset.status === statusFilter;
				const matchesCategory = !categoryFilter || row.dataset.category === categoryFilter;
				row.style.display = (matchesSearch && matchesStatus && matchesCategory) ? '' : 'none';
			});

			const filterActiveDot = document.getElementById('filterActiveDot');
			if (filterActiveDot) {
				filterActiveDot.style.display = (statusFilter || categoryFilter) ? 'block' : 'none';
			}
		}

		// Shortcuts from the notification bell land here with query params:
		//   ?search=<product>  pre-filters the table down to that product
		//   ?reorder=1         opens the reorder recommendations panel
		document.addEventListener('DOMContentLoaded', function () {
			const params = new URLSearchParams(window.location.search);

			const search = params.get('search');
			if (search) {
				const input = document.getElementById('searchInput');
				if (input) {
					input.value = search;
					applyFilters();
					// Make it obvious the list is filtered, and easy to undo.
					input.focus({ preventScroll: true });
				}
			}

			if (params.get('reorder')) {
				const reorderModal = document.getElementById('reorderRecommendationsModal');
				// Absent for staff, who do not get the recommendations panel.
				if (reorderModal) {
					reorderModal.style.display = 'flex';
				}
			}
		});

		document.addEventListener('DOMContentLoaded', function () {
			const filterToggleBtn = document.getElementById('filterToggleBtn');
			const filterPanel = document.getElementById('filterPanel');
			const clearFiltersBtn = document.getElementById('clearFiltersBtn');

			if (filterToggleBtn && filterPanel) {
				filterToggleBtn.addEventListener('click', function (e) {
					e.stopPropagation();
					filterPanel.style.display = filterPanel.style.display === 'block' ? 'none' : 'block';
				});

				document.addEventListener('click', function (e) {
					if (!filterPanel.contains(e.target) && e.target !== filterToggleBtn) {
						filterPanel.style.display = 'none';
					}
				});
			}

			if (clearFiltersBtn) {
				clearFiltersBtn.addEventListener('click', function () {
					document.getElementById('filterStatus').value = '';
					document.getElementById('filterCategory').value = '';
					applyFilters();
				});
			}
		});

		// ---- Sortable columns ----
		let currentSort = { key: null, dir: 1 };

		function sortInventoryTable(key) {
			const table = document.querySelector('.inventory-table');
			const tbody = table.querySelector('tbody');
			const rows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
				return row.querySelectorAll('td[data-value]').length > 0;
			});
			if (!rows.length) return;

			currentSort.dir = (currentSort.key === key) ? currentSort.dir * -1 : 1;
			currentSort.key = key;

			const columnIndex = Array.from(table.querySelectorAll('thead th')).findIndex(function (th) {
				return th.dataset.sortKey === key;
			});

			rows.sort(function (a, b) {
				const cellA = a.children[columnIndex];
				const cellB = b.children[columnIndex];
				const rawA = cellA ? cellA.dataset.value : '';
				const rawB = cellB ? cellB.dataset.value : '';
				const numA = parseFloat(rawA);
				const numB = parseFloat(rawB);
				const bothNumeric = !isNaN(numA) && !isNaN(numB);
				const cmp = bothNumeric ? (numA - numB) : String(rawA).localeCompare(String(rawB));
				return cmp * currentSort.dir;
			});

			rows.forEach(function (row) { tbody.appendChild(row); });

			table.querySelectorAll('.sortable-th').forEach(function (th) {
				const arrow = th.querySelector('.sort-arrow');
				if (th.dataset.sortKey === key) {
					arrow.textContent = currentSort.dir === 1 ? '▲' : '▼';
				} else {
					arrow.textContent = '';
				}
			});
		}

		document.querySelectorAll('.sortable-th').forEach(function (th) {
			th.addEventListener('click', function () {
				sortInventoryTable(th.dataset.sortKey);
			});
		});

	</script>

	<!-- Current Inventory Table -->
	<div class="data-table-container">
		<div
			style="display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.15rem; margin-bottom: 16px; color: var(--color-text); padding-left: 24px; padding-top: 18px;">
			<span>Current Inventory</span>
			@if(Auth::user()->role === 'staff')
				<button id="editRequestBtn" class="btn-action edit" style="margin-right: 24px;">Edit
					Request</button>
			@endif
		</div>
		@if(Auth::user()->role === 'staff')
			<!-- Edit Request History for Staff -->
			<div class="card-panel" style="margin: 0 24px 24px;">
				<div style="font-weight: 700; font-size: 1.15rem; color: var(--color-text); margin-bottom: 16px;">Your Edit Request
					History</div>
				@php
					$editRequests = \App\Models\EditRequest::where('user_id', Auth::id())
						->orderBy('created_at', 'desc')
						->get();
				@endphp
				<table class="data-table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Status</th>
							<th>Completed</th>
						</tr>
					</thead>
					<tbody>
						@forelse($editRequests as $req)
							<tr>
								<td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
								<td>
									@if($req->status === 'approved')
										<span class="status-badge status-badge--success">Approved</span>
									@elseif($req->status === 'pending')
										<span class="status-badge status-badge--info">Pending</span>
									@elseif($req->status === 'rejected')
										<span class="status-badge status-badge--danger">Rejected</span>
									@else
										<span>{{ $req->status }}</span>
									@endif
								</td>
								<td>
									@if($req->completed)
										<span style="color: var(--color-success); font-weight: 600;">Yes</span>
									@else
										<span style="color: var(--color-text-muted);">No</span>
									@endif
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="3" style="text-align: center; color: #aaa; padding: 14px;">No edit requests found.
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		@endif
		<div style="overflow-x: auto;">
			<table class="data-table inventory-table">
				<thead>
					<tr>
						<th style="text-align: center; width: 60px;">#</th>
						<th class="sortable-th" data-sort-key="id">Product ID <span class="sort-arrow"></span></th>
						<th class="sortable-th" data-sort-key="name">Name <span class="sort-arrow"></span></th>
						<th class="sortable-th" data-sort-key="category">Category <span class="sort-arrow"></span></th>
						<th class="sortable-th" data-sort-key="stock">Stock <span class="sort-arrow"></span></th>
						<th class="sortable-th" data-sort-key="price">Price <span class="sort-arrow"></span></th>
						<th class="sortable-th" data-sort-key="status">Status <span class="sort-arrow"></span></th>
						<th style="text-align: center;">Actions</th>
					</tr>
				</thead>
				<tbody>
					@forelse($products as $product)
						@php
							$statusRank = $product->status === 'Critical' ? 0 : ($product->status === 'Low Stock' ? 1 : 2);
						@endphp
						<tr data-category="{{ $product->category }}" data-status="{{ $product->status }}">
							<td style="text-align: center; color: var(--color-text-muted); font-weight: 500;">{{ $loop->iteration }}</td>
							<td data-value="{{ $product->id }}">{{ $product->id }}</td>
							<td data-value="{{ strtolower($product->name) }}" style="font-weight: 600; color: var(--color-text);">{{ $product->name }}</td>
							<td data-value="{{ strtolower($product->category) }}">{{ $product->category }}</td>
							<td data-value="{{ $product->stock }}">{{ $product->stock }}</td>
							<td data-value="{{ $product->price }}">₱{{ number_format($product->price, 2) }}</td>
							<td data-value="{{ $statusRank }}">
								@if($product->status === 'In Stock')
									<span class="status-badge in-stock">In Stock</span>
								@elseif($product->status === 'Low Stock')
									<span class="status-badge low">Low Stock</span>
								@elseif($product->status === 'Critical')
									<span class="status-badge critical">Critical</span>
								@else
									<span class="status-badge">{{ $product->status }}</span>
								@endif
							</td>
							<td style="text-align: center; white-space: nowrap;">
								{{-- Staff must have an approved edit request before they can edit.
									 Managers edit directly, same as admins — they can already add and
									 delete products, so gating edit alone made no sense, and the
									 request-approval fallback below is staff-only, which left managers
									 with a permanently disabled button and no way to ask for access. --}}
								@if(Auth::user()->role === 'staff')
									@php
										// Get the latest edit request for this user (not per product)
										$latestEditRequest = \App\Models\EditRequest::where('user_id', Auth::id())
											->latest()
											->first();
									@endphp
									@if($latestEditRequest && $latestEditRequest->status === 'approved' && !$latestEditRequest->completed)
										<button class="btn-action edit edit-btn" data-id="{{ $product->id }}"
											data-name="{{ $product->name }}" data-category="{{ $product->category }}"
											data-stock="{{ $product->stock }}" data-status="{{ $product->status }}"
											data-price="{{ $product->price }}"
											data-reorder="{{ $product->reorder_level }}"
										data-expiry="{{ $product->expiry_date?->format('Y-m-d') }}">Edit</button>
									@else
										<button class="btn-action edit edit-btn" disabled
											style="background: #bdbdbd; cursor: not-allowed; opacity: 0.7;"
											data-id="{{ $product->id }}" data-name="{{ $product->name }}"
											data-category="{{ $product->category }}" data-stock="{{ $product->stock }}"
											data-status="{{ $product->status }}" data-price="{{ $product->price }}"
											data-reorder="{{ $product->reorder_level }}"
										data-expiry="{{ $product->expiry_date?->format('Y-m-d') }}">Edit</button>
									@endif
								@else
									<button class="btn-action edit edit-btn" data-id="{{ $product->id }}"
										data-name="{{ $product->name }}" data-category="{{ $product->category }}"
										data-stock="{{ $product->stock }}" data-status="{{ $product->status }}"
										data-price="{{ $product->price }}"
										data-reorder="{{ $product->reorder_level }}"
										data-expiry="{{ $product->expiry_date?->format('Y-m-d') }}">Edit</button>
								@endif
								@if(Auth::user()->role !== 'staff')
									<button class="btn-action delete delete-btn" data-id="{{ $product->id }}"
										data-name="{{ $product->name }}">Delete</button>
								@endif
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ Auth::user()->role !== 'staff' ? 9 : 8 }}" style="text-align: center; color: #aaa;">No products found.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>

</div>

@if(Auth::user()->role !== 'staff')
	<!-- Reorder Recommendations Modal -->
	<div id="reorderRecommendationsModal"
		class="modal-overlay" style="display: none;">
		<div class="modal-card" style="position: relative; width: 90%; max-width: 640px; max-height: 85vh; padding: 0; display: flex; flex-direction: column;">
			<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
				<div style="font-weight: 700; font-size: 1.2rem; color: var(--color-text);">
					Reorder Recommendations
					@if($reorderRecommendations->count() > 0)
						<span
							style="background: var(--color-danger); color: #fff; border-radius: var(--radius-md); padding: 2px 8px; font-size: 0.8rem; margin-left: 8px;">{{ $reorderRecommendations->count() }}</span>
					@endif
				</div>
				<button type="button" id="closeReorderRecommendationsModal"
					style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary); line-height: 1;">&times;</button>
			</div>
			<div style="padding: 24px 28px; overflow-y: auto;">
				<div style="display: flex; flex-direction: column; gap: 12px;">
					@forelse($reorderRecommendations as $recommendation)
						<div
							style="display: flex; align-items: center; background: var(--color-neutral-bg); border-radius: var(--radius-sm); padding: 18px 20px; gap: 18px;">
							<div style="flex: 1;">
								<div style="font-weight: 500;">{{ $recommendation['name'] }}</div>
								<div style="font-size: 0.98rem; color: var(--color-text-muted);">
									Current: {{ $recommendation['current_stock'] }} |
									@if(isset($recommendation['dynamic_reorder_level']))
										SARIMA Reorder: {{ round($recommendation['dynamic_reorder_level']) }} |
										Static: {{ $recommendation['static_reorder_level'] ?? 'N/A' }}
									@else
										Reorder at:
										{{ $recommendation['reorder_level'] ?? $recommendation['static_reorder_level'] ?? 'N/A' }}
									@endif
								</div>
							</div>
							@if($recommendation['priority'] === 'High')
								<span class="status-badge status-badge--danger" style="font-size: 1rem;">
							@elseif($recommendation['priority'] === 'Medium')
									<span class="status-badge status-badge--warning" style="font-size: 1rem;">
								@else
										<span class="status-badge status-badge--success" style="font-size: 1rem;">
									@endif
										{{ $recommendation['priority'] }}
									</span>
									<div style="text-align: right;">
										<div style="color: var(--color-success); font-size: 1rem; font-weight: 600;">
											{{ $recommendation['recommended_quantity'] }} units
										</div>
										@if(isset($recommendation['forecasted_demand']))
											<div style="color: var(--color-info); font-size: 0.8rem;">Forecast:
												{{ $recommendation['forecasted_demand'] }} units
											</div>
										@endif
										@if(isset($recommendation['algorithm']))
											<div style="color: #8b5cf6; font-size: 0.75rem;">{{ $recommendation['algorithm'] }}</div>
										@endif
										<div style="color: var(--color-text-muted); font-size: 0.85rem;">
											₱{{ number_format($recommendation['estimated_cost'], 2) }}</div>
									</div>
						</div>
					@empty
						<div
							style="display: flex; align-items: center; justify-content: center; background: var(--color-neutral-bg); border-radius: var(--radius-sm); padding: 40px 20px;">
							<div style="text-align: center;">
								<div style="font-size: 1.5rem; margin-bottom: 8px;">✅</div>
								<div style="font-weight: 500; color: var(--color-success); margin-bottom: 4px;">All products are well-stocked!
								</div>
								<div style="font-size: 0.9rem; color: var(--color-text-muted);">No reorder recommendations at this time.</div>
							</div>
						</div>
					@endforelse
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var reorderModal = document.getElementById('reorderRecommendationsModal');
			var closeReorderModal = document.getElementById('closeReorderRecommendationsModal');

			if (closeReorderModal) {
				closeReorderModal.addEventListener('click', function () {
					reorderModal.style.display = 'none';
				});
			}

			if (reorderModal) {
				reorderModal.addEventListener('click', function (e) {
					if (e.target === reorderModal) {
						reorderModal.style.display = 'none';
					}
				});
			}
		});
	</script>
@endif
@endsection
