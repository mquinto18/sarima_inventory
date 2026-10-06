@extends('layouts.app')

@push('styles')
<style>
	.row-actions {
		display: inline-block;
	}

	.row-actions__toggle {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 34px;
		height: 34px;
		padding: 0;
		background: none;
		border: 1px solid transparent;
		border-radius: var(--radius-sm);
		color: var(--color-text-muted);
		cursor: pointer;
		transition: background var(--dur-fast) var(--ease-out),
			color var(--dur-fast) var(--ease-out),
			border-color var(--dur-fast) var(--ease-out);
	}

	.row-actions__toggle:hover,
	.row-actions__toggle[aria-expanded="true"] {
		background: var(--color-primary-soft);
		border-color: var(--color-border);
		color: var(--color-primary);
	}

	.row-actions__toggle:focus-visible {
		outline: 2px solid var(--color-primary);
		outline-offset: 2px;
	}

	/* Fixed rather than absolute: the table sits inside an `overflow-x: auto`
	   wrapper and a `.data-table-container` with `overflow: hidden`, either of
	   which would clip an absolutely positioned menu. JS sets top/left on open.

	   Hidden via opacity/visibility rather than `display` so it animates out as
	   well as in. `pointer-events: none` while closed is essential — these are
	   fixed-position boxes sitting over the table, and without it a closed menu
	   would intercept clicks on the rows beneath it. */
	.row-actions__menu {
		display: block;
		position: fixed;
		/* Above the fixed header (2000) and sidebar (2500/3000), below the
		   modal scrim (9000) — at the old 1040 the shell painted over it. */
		z-index: 8000;
		min-width: 180px;
		padding: 6px;
		background: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		box-shadow: var(--shadow-lg);
		opacity: 0;
		visibility: hidden;
		pointer-events: none;
		transform: translateY(-6px) scale(0.98);
		transform-origin: top right;
		transition: opacity var(--dur-base) var(--ease-out),
			transform var(--dur-base) var(--ease-out),
			visibility 0s linear var(--dur-base);
	}

	.row-actions__menu.is-open {
		opacity: 1;
		visibility: visible;
		pointer-events: auto;
		transform: none;
		transition: opacity var(--dur-base) var(--ease-out),
			transform var(--dur-base) var(--ease-out),
			visibility 0s linear 0s;
	}

	.row-actions__item {
		display: block;
		width: 100%;
		padding: 9px 12px;
		background: none;
		border: none;
		border-radius: var(--radius-sm);
		font: inherit;
		font-size: 0.95rem;
		font-weight: 500;
		text-align: left;
		color: var(--color-text);
		cursor: pointer;
		transition: background var(--dur-fast) var(--ease-out),
			color var(--dur-fast) var(--ease-out);
	}

	.row-actions__item:hover,
	.row-actions__item:focus-visible {
		background: var(--color-primary-soft);
		color: var(--color-primary);
		outline: none;
	}

	.row-actions__item--danger {
		color: #dc2626;
	}

	.row-actions__item--danger:hover,
	.row-actions__item--danger:focus-visible {
		background: rgba(220, 38, 38, 0.08);
		color: #b91c1c;
	}
</style>
@endpush

@section('content')
<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">Supplier Management</div>
			<div class="dashboard-hero-sub">
				{{ $suppliers->count() }} suppliers on file
				— {{ $suppliers->where('active', true)->count() }} active.
			</div>
		</div>
		<div class="dashboard-hero-actions">
			<a href="javascript:void(0)" onclick="document.getElementById('addSupplierModal').style.display='flex'" class="btn-action edit">+ Add Supplier</a>
			<a href="javascript:void(0)" onclick="openLinkModal()" class="btn-action ghost">Link Product to Supplier</a>
		</div>
	</div>

	<div class="stat-grid">
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M3 7l1.5-3h15L21 7M3 7v11a1 1 0 0 0 1 1h1m-2-12h18m0 0v11a1 1 0 0 1-1 1h-1M6 19h12M9 19v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Suppliers</div>
			<div class="main">{{ $suppliers->count() }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--success">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M8 12.5l3 3 5-5" stroke-linecap="round" stroke-linejoin="round" />
					<circle cx="12" cy="12" r="9" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Active Suppliers</div>
			<div class="main">{{ $suppliers->where('active', true)->count() }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--warning">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M16 3v4M8 3v4" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Linked Products</div>
			<div class="main">{{ $suppliers->sum(function($s) { return $s->products->count(); }) }}</div>
		</div>
	</div>

	<!-- Suppliers Table -->
	<div class="data-table-container">
		<div style="font-weight: 700; font-size: 1.15rem; margin-bottom: 16px; color: var(--color-text); padding-left: 24px; padding-top: 18px;">
			Suppliers
		</div>
		<div style="overflow-x: auto;">
			<table class="data-table" id="suppliersTable">
				<thead>
					<tr>
						<th style="text-align: center; width: 40px;"></th>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Lead Time (days)</th>
						<th>Status</th>
						<th>Linked Products</th>
						<th style="text-align: center;">Actions</th>
					</tr>
				</thead>
				<tbody>
					@forelse($suppliers as $supplier)
						<tr>
							<td style="text-align: center;">
								<button type="button" class="toggle-products-btn" data-target="products-row-{{ $supplier->id }}" style="background: none; border: none; cursor: pointer; color: var(--color-primary); font-size: 1.1rem;">▶</button>
							</td>
							<td style="font-weight: 600; color: var(--color-text);">{{ $supplier->name }}</td>
							<td>{{ $supplier->email }}</td>
							<td>{{ $supplier->phone ?? '—' }}</td>
							<td>{{ $supplier->lead_time_days }}</td>
							<td>
								@if($supplier->active)
									<span class="status-badge active">Active</span>
								@else
									<span class="status-badge inactive">Inactive</span>
								@endif
							</td>
							<td>{{ $supplier->products->count() }}</td>
							<td style="text-align: center;">
								<div class="row-actions">
									<button type="button" class="row-actions__toggle" aria-haspopup="true" aria-expanded="false" aria-label="Actions for {{ $supplier->name }}">
										<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
											<circle cx="12" cy="5" r="1.9" />
											<circle cx="12" cy="12" r="1.9" />
											<circle cx="12" cy="19" r="1.9" />
										</svg>
									</button>
									<div class="row-actions__menu" role="menu">
										<button type="button" role="menuitem" class="row-actions__item edit-supplier-btn"
											data-id="{{ $supplier->id }}"
											data-name="{{ $supplier->name }}"
											data-email="{{ $supplier->email }}"
											data-phone="{{ $supplier->phone }}"
											data-address="{{ $supplier->address }}"
											data-lead_time_days="{{ $supplier->lead_time_days }}"
											data-active="{{ $supplier->active ? 1 : 0 }}">Edit</button>
										<button type="button" role="menuitem" class="row-actions__item link-product-btn" data-supplier-id="{{ $supplier->id }}">Link Product</button>
									</div>
								</div>
							</td>
						</tr>
						<tr id="products-row-{{ $supplier->id }}" style="display: none;">
							<td colspan="8" style="background: var(--color-page-bg); padding: 16px 24px;">
								@if($supplier->products->count() > 0)
									<table class="data-table" style="margin: 0;">
										<thead>
											<tr>
												<th>Product</th>
												<th>Cost Price</th>
												<th>Lead Time Override</th>
												<th>Primary</th>
												<th style="text-align: center;">Actions</th>
											</tr>
										</thead>
										<tbody>
											@foreach($supplier->products as $product)
												<tr>
													<td>{{ $product->name }}</td>
													<td>₱{{ number_format($product->pivot->cost_price, 2) }}</td>
													<td>{{ $product->pivot->lead_time_days ?? '— (uses supplier default)' }}</td>
													<td>
														@if($product->pivot->is_primary)
															<span class="status-badge active">Primary</span>
														@else
															<span style="color: var(--color-text-muted);">—</span>
														@endif
													</td>
													<td style="text-align: center;">
														<div class="row-actions">
															<button type="button" class="row-actions__toggle" aria-haspopup="true" aria-expanded="false" aria-label="Actions for {{ $product->name }}">
																<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
																	<circle cx="12" cy="5" r="1.9" />
																	<circle cx="12" cy="12" r="1.9" />
																	<circle cx="12" cy="19" r="1.9" />
																</svg>
															</button>
															<div class="row-actions__menu" role="menu">
																<button type="button" role="menuitem" class="row-actions__item row-actions__item--danger unlink-product-btn" data-id="{{ $product->pivot->id }}" data-name="{{ $product->name }}">Unlink</button>
															</div>
														</div>
													</td>
												</tr>
											@endforeach
										</tbody>
									</table>
								@else
									<div style="color: var(--color-text-muted); padding: 8px 0;">No products linked to this supplier yet.</div>
								@endif
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="8" style="text-align: center; color: #aaa;">No suppliers found.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="modal-overlay" style="display: none;">
	<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 500px;">
		<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
			<h5 style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Add Supplier</h5>
			<button type="button" id="closeAddSupplierModal" style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
		</div>
		<form id="addSupplierForm" method="POST" action="{{ route('suppliers.store') }}">
			@csrf
			<div style="padding: 24px 28px;">
				<div style="margin-bottom: 18px;">
					<label for="supplierName" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Name</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M3 7l1.5-3h15L21 7M3 7v11a1 1 0 0 0 1 1h1m-2-12h18m0 0v11a1 1 0 0 1-1 1h-1M6 19h12M9 19v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="text" name="name" id="supplierName" required>
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="supplierEmail" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Email</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="email" name="email" id="supplierEmail" required>
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="supplierPhone" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Phone</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="text" name="phone" id="supplierPhone">
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="supplierAddress" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Address</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />
						</svg>
						<input type="text" name="address" id="supplierAddress">
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="supplierLeadTime" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Lead Time (days)</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="number" name="lead_time_days" id="supplierLeadTime" min="0" value="7">
					</div>
				</div>
				<div style="margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
					<input type="checkbox" name="active" id="supplierActive" value="1" checked style="width: 18px; height: 18px;">
					<label for="supplierActive" style="font-weight: 600; color: var(--color-text); margin: 0;">Active</label>
				</div>
			</div>
			<div style="padding: 24px 28px; border-top: 1px solid #e0e0e0; display: flex; gap: 14px; justify-content: flex-end;">
				<button type="button" id="cancelAddSupplierBtn" class="btn-action" style="background: #6c757d;">Cancel</button>
				<button type="submit" class="btn-action edit">Add Supplier</button>
			</div>
		</form>
	</div>
</div>

<!-- Edit Supplier Modal -->
<div id="editSupplierModal" class="modal-overlay" style="display: none;">
	<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 500px;">
		<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
			<h5 style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Edit Supplier</h5>
			<button type="button" id="closeEditSupplierModal" style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
		</div>
		<form id="editSupplierForm" method="POST">
			@csrf
			@method('PUT')
			<input type="hidden" id="editSupplierId" name="id">
			<div style="padding: 24px 28px;">
				<div style="margin-bottom: 18px;">
					<label for="editSupplierName" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Name</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M3 7l1.5-3h15L21 7M3 7v11a1 1 0 0 0 1 1h1m-2-12h18m0 0v11a1 1 0 0 1-1 1h-1M6 19h12M9 19v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="text" name="name" id="editSupplierName" required>
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="editSupplierEmail" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Email</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="email" name="email" id="editSupplierEmail" required>
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="editSupplierPhone" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Phone</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="text" name="phone" id="editSupplierPhone">
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="editSupplierAddress" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Address</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />
						</svg>
						<input type="text" name="address" id="editSupplierAddress">
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="editSupplierLeadTime" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Lead Time (days)</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="number" name="lead_time_days" id="editSupplierLeadTime" min="0">
					</div>
				</div>
				<div style="margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
					<input type="checkbox" name="active" id="editSupplierActive" value="1" style="width: 18px; height: 18px;">
					<label for="editSupplierActive" style="font-weight: 600; color: var(--color-text); margin: 0;">Active</label>
				</div>
			</div>
			<div style="padding: 24px 28px; border-top: 1px solid #e0e0e0; display: flex; gap: 14px; justify-content: flex-end;">
				<button type="button" id="cancelEditSupplierBtn" class="btn-action" style="background: #6c757d;">Cancel</button>
				<button type="submit" class="btn-action edit">Update Supplier</button>
			</div>
		</form>
	</div>
</div>

<!-- Link Product to Supplier Modal -->
<div id="linkProductModal" class="modal-overlay" style="display: none;">
	<div class="modal-card" style="position: relative; margin: 3% auto; width: 90%; max-width: 500px;">
		<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
			<h5 style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Link Product to Supplier</h5>
			<button type="button" id="closeLinkProductModal" style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary);">&times;</button>
		</div>
		<form id="linkProductForm" method="POST" action="{{ route('suppliers.link-products') }}">
			@csrf
			<div style="padding: 24px 28px;">
				<div style="margin-bottom: 18px;">
					<label for="linkSupplierId" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Supplier</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M3 7l1.5-3h15L21 7M3 7v11a1 1 0 0 0 1 1h1m-2-12h18m0 0v11a1 1 0 0 1-1 1h-1M6 19h12M9 19v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<select name="supplier_id" id="linkSupplierId" required>
							<option value="">Select Supplier</option>
							@foreach($suppliers as $supplier)
								<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Products</label>
					<input type="text" id="linkProductFilter" placeholder="Type to filter products..."
						style="width: 100%; box-sizing: border-box; padding: 8px 10px; margin-bottom: 8px; border-radius: var(--radius-sm); border: 1.5px solid #e5e7eb;">
					<div id="linkProductList" style="max-height: 240px; overflow-y: auto; border: 1.5px solid #e5e7eb; border-radius: var(--radius-sm); padding: 4px 10px;">
						@foreach($products as $product)
							<div class="link-product-row" data-name="{{ strtolower($product->name) }}"
								data-linked-suppliers="{{ $product->suppliers->pluck('id')->implode(',') }}"
								style="display: flex; align-items: center; gap: 10px; padding: 7px 0; border-bottom: 1px solid #f0f0f0;">
								<input type="checkbox" class="link-product-checkbox" data-id="{{ $product->id }}" style="width: 16px; height: 16px; flex-shrink: 0;">
								<span style="flex: 1; min-width: 0;">{{ $product->name }}</span>
								<div class="form-input-group" style="width: 130px; flex-shrink: 0;">
									<span class="form-input-icon currency-icon">₱</span>
									<input type="number" step="0.01" min="0" class="link-product-cost" data-id="{{ $product->id }}"
										placeholder="Cost" disabled style="width: 100%;">
								</div>
							</div>
						@endforeach
					</div>
					<div id="linkProductNoMatch" style="display: none; color: var(--color-text-muted); text-align: center; padding: 10px;">No products match.</div>
				</div>
				<div style="margin-bottom: 18px;">
					<label for="linkLeadTimeDays" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--color-text);">Lead Time Override (days)</label>
					<div class="form-input-group">
						<svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<input type="number" min="0" name="lead_time_days" id="linkLeadTimeDays" placeholder="Leave blank to use supplier default">
					</div>
				</div>
				<div style="margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
					<input type="checkbox" name="is_primary" id="linkIsPrimary" value="1" style="width: 18px; height: 18px;">
					<label for="linkIsPrimary" style="font-weight: 600; color: var(--color-text); margin: 0;">Set as primary supplier for this product</label>
				</div>
			</div>
			<div style="padding: 24px 28px; border-top: 1px solid #e0e0e0; display: flex; gap: 14px; justify-content: flex-end;">
				<button type="button" id="cancelLinkProductBtn" class="btn-action" style="background: #6c757d;">Cancel</button>
				<button type="submit" class="btn-action edit">Link Product</button>
			</div>
		</form>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	// Combines the name filter with "already linked to the selected
	// supplier" so a product can't be checked (or even seen) twice for the
	// same supplier. Re-run whenever either the search text or the supplier
	// dropdown changes.
	function applyLinkProductVisibility() {
		var q = $('#linkProductFilter').val().trim().toLowerCase();
		var supplierId = $('#linkSupplierId').val();
		var anyVisible = false;

		$('.link-product-row').each(function () {
			var row = $(this);
			var nameMatches = !q || row.data('name').indexOf(q) !== -1;
			var linkedSuppliers = (row.attr('data-linked-suppliers') || '').split(',');
			var alreadyLinked = supplierId !== '' && linkedSuppliers.indexOf(String(supplierId)) !== -1;
			var visible = nameMatches && !alreadyLinked;

			if (alreadyLinked) {
				// Never leave a hidden row checked - it would still submit.
				row.find('.link-product-checkbox').prop('checked', false);
				row.find('.link-product-cost').val('').prop('disabled', true);
			}

			row.toggle(visible);
			if (visible) anyVisible = true;
		});

		$('#linkProductNoMatch').toggle(!anyVisible);
	}

	function openLinkModal(supplierId) {
		$('#linkSupplierId').val(supplierId || '');
		// Reset any selection left over from a previous time the modal was opened.
		$('#linkProductFilter').val('');
		$('.link-product-checkbox').prop('checked', false);
		$('.link-product-cost').val('').prop('disabled', true);
		applyLinkProductVisibility();
		$('#linkProductModal').show();
	}

	$(function () {
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		// Row action kebab menus. The menu is position: fixed, so it is placed
		// against the toggle here rather than by CSS, and flipped above the
		// toggle when there is not enough room below it.
		function closeRowMenus() {
			$('.row-actions__menu.is-open')
				.removeClass('is-open')
				.prev('.row-actions__toggle')
				.attr('aria-expanded', 'false');
		}

		$(document).on('click', '.row-actions__toggle', function () {
			var toggle = $(this);
			var menu = toggle.next('.row-actions__menu');
			var wasOpen = menu.hasClass('is-open');

			closeRowMenus();
			if (wasOpen) return;

			// The menu is always laid out (hidden with opacity/visibility), so it
			// can be measured before being shown. offsetWidth/Height ignore the
			// closed state's scale transform, so these are the real dimensions.
			var rect = this.getBoundingClientRect();
			var width = menu.outerWidth();
			var height = menu.outerHeight();
			var left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
			// Never let a flipped-up menu slide under the fixed 70px header.
			var minTop = 78;
			var top = rect.bottom + 6;
			if (top + height > window.innerHeight - 8) {
				top = Math.max(minTop, rect.top - height - 6);
			}

			menu.css({ top: top + 'px', left: left + 'px' }).addClass('is-open');
			toggle.attr('aria-expanded', 'true');

			// preventScroll: a scroll here would trip the listener below and
			// immediately close the menu we just opened.
			var firstItem = menu.find('.row-actions__item').get(0);
			if (firstItem) firstItem.focus({ preventScroll: true });
		});

		// Clicking anywhere else closes the menu. Menu item clicks are allowed to
		// bubble to here so the action handlers below still fire.
		$(document).on('click', function (e) {
			if ($(e.target).closest('.row-actions__toggle').length) return;
			closeRowMenus();
		});

		$(document).on('keydown', function (e) {
			if (e.key !== 'Escape') return;
			$('.row-actions__menu.is-open').prev('.row-actions__toggle').trigger('focus');
			closeRowMenus();
		});

		// Capture phase so scrolling the table wrapper counts, not just the page.
		window.addEventListener('scroll', closeRowMenus, true);
		window.addEventListener('resize', closeRowMenus);

		// Expand/collapse linked-products sub-table
		$(document).on('click', '.toggle-products-btn', function () {
			var target = $(this).data('target');
			var row = $('#' + target);
			row.toggle();
			$(this).text(row.is(':visible') ? '▼' : '▶');
		});

		// Add supplier modal open/close
		$('#closeAddSupplierModal, #cancelAddSupplierBtn').on('click', function () {
			$('#addSupplierModal').hide();
		});
		$('#addSupplierModal').on('click', function (e) {
			if (e.target.id === 'addSupplierModal') $('#addSupplierModal').hide();
		});

		// Add supplier submit
		$('#addSupplierForm').on('submit', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);
			setButtonLoading(submitBtn, true, 'Adding...');
			$.ajax({
				url: form.attr('action'),
				method: 'POST',
				data: form.serialize(),
				success: function () {
					setButtonLoading(submitBtn, false);
					$('#addSupplierModal').hide();
					form[0].reset();
					showToast('Supplier added successfully!', 'success');
					setTimeout(function () { location.reload(); }, 1200);
				},
				error: function (xhr) {
					setButtonLoading(submitBtn, false);
					showToast(xhr.responseJSON?.message || 'Could not add supplier.', 'error');
				}
			});
		});

		// Edit supplier modal open
		$(document).on('click', '.edit-supplier-btn', function () {
			var id = $(this).data('id');
			$('#editSupplierId').val(id);
			$('#editSupplierName').val($(this).data('name'));
			$('#editSupplierEmail').val($(this).data('email'));
			$('#editSupplierPhone').val($(this).data('phone'));
			$('#editSupplierAddress').val($(this).data('address'));
			$('#editSupplierLeadTime').val($(this).data('lead_time_days'));
			$('#editSupplierActive').prop('checked', $(this).data('active') == 1);
			$('#editSupplierForm').attr('action', '/suppliers/' + id);
			$('#editSupplierModal').show();
		});

		$('#closeEditSupplierModal, #cancelEditSupplierBtn').on('click', function () {
			$('#editSupplierModal').hide();
		});
		$('#editSupplierModal').on('click', function (e) {
			if (e.target.id === 'editSupplierModal') $('#editSupplierModal').hide();
		});

		// Edit supplier submit
		$('#editSupplierForm').on('submit', function (e) {
			e.preventDefault();
			var form = $(this);
			var id = $('#editSupplierId').val();
			var submitBtn = form.find('button[type="submit"]').get(0);
			setButtonLoading(submitBtn, true, 'Updating...');
			$.ajax({
				url: '/suppliers/' + id,
				method: 'POST',
				data: form.serialize(),
				success: function () {
					setButtonLoading(submitBtn, false);
					$('#editSupplierModal').hide();
					showToast('Supplier updated successfully!', 'success');
					setTimeout(function () { location.reload(); }, 1200);
				},
				error: function (xhr) {
					setButtonLoading(submitBtn, false);
					showToast(xhr.responseJSON?.message || 'Could not update supplier.', 'error');
				}
			});
		});

		// Link product modal open (from a specific supplier row)
		$(document).on('click', '.link-product-btn', function () {
			openLinkModal($(this).data('supplier-id'));
		});

		$('#closeLinkProductModal, #cancelLinkProductBtn').on('click', function () {
			$('#linkProductModal').hide();
		});
		$('#linkProductModal').on('click', function (e) {
			if (e.target.id === 'linkProductModal') $('#linkProductModal').hide();
		});

		// Link product modal: checking a row enables its cost input; unchecking
		// clears it (never submit a stale cost for a since-unchecked product).
		$(document).on('change', '.link-product-checkbox', function () {
			var cost = $('.link-product-cost[data-id="' + $(this).data('id') + '"]');
			cost.prop('disabled', !this.checked);
			if (!this.checked) cost.val('');
		});

		// Link product modal: client-side filter, same idea as the POS search -
		// narrows the visible rows only, no server round-trip. Also re-applied
		// when the supplier changes, since that affects which rows are
		// already-linked (see applyLinkProductVisibility).
		$('#linkProductFilter').on('input', applyLinkProductVisibility);
		$('#linkSupplierId').on('change', applyLinkProductVisibility);

		// Link product submit: build the payload from just the checked rows
		// (products[id][cost_price]) rather than form.serialize(), since most
		// of the 64 product rows are never meant to be submitted.
		$('#linkProductForm').on('submit', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);

			var products = {};
			var missingCost = false;
			$('.link-product-checkbox:checked').each(function () {
				var id = $(this).data('id');
				var cost = $('.link-product-cost[data-id="' + id + '"]').val();
				if (cost === '' || cost === null || Number(cost) < 0) {
					missingCost = true;
					return false;
				}
				products[id] = { cost_price: cost };
			});

			if ($.isEmptyObject(products)) {
				showToast('Check at least one product to link.', 'error');
				return;
			}
			if (missingCost) {
				showToast('Enter a cost price for every checked product.', 'error');
				return;
			}

			setButtonLoading(submitBtn, true, 'Linking...');
			$.ajax({
				url: form.attr('action'),
				method: 'POST',
				data: {
					_token: $('meta[name="csrf-token"]').attr('content'),
					supplier_id: $('#linkSupplierId').val(),
					lead_time_days: $('#linkLeadTimeDays').val(),
					is_primary: $('#linkIsPrimary').is(':checked') ? 1 : 0,
					products: products
				},
				success: function (response) {
					setButtonLoading(submitBtn, false);
					$('#linkProductModal').hide();
					showToast(response.message || 'Products linked to supplier successfully!', 'success');
					setTimeout(function () { location.reload(); }, 1200);
				},
				error: function (xhr) {
					setButtonLoading(submitBtn, false);
					showToast(xhr.responseJSON?.message || 'Could not link products.', 'error');
				}
			});
		});

		// Unlink product
		$(document).on('click', '.unlink-product-btn', function () {
			var id = $(this).data('id');
			var name = $(this).data('name');
			var button = $(this);

			confirmDialog('Unlink "' + name + '" from this supplier?', {
				title: 'Unlink product',
				confirmText: 'Unlink'
			}).then(function (confirmed) {
				if (!confirmed) return;
				setButtonLoading(button.get(0), true, 'Unlinking...');
				$.ajax({
					url: '/suppliers/product-links/' + id,
					type: 'POST',
					data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
					success: function (response) {
						if (response.success) {
							showToast('Product unlinked successfully!', 'success');
							setTimeout(function () { location.reload(); }, 1200);
						} else {
							setButtonLoading(button.get(0), false);
							showToast(response.message || 'Could not unlink product.', 'error');
						}
					},
					error: function (xhr) {
						setButtonLoading(button.get(0), false);
						showToast(xhr.responseJSON?.message || 'Could not unlink product.', 'error');
					}
				});
			});
		});
	});
</script>
@endsection
