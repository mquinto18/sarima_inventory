@extends('layouts.app')

@section('content')
<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">Purchase Orders</div>
			<div class="dashboard-hero-sub">
				{{ $purchaseOrders->count() }} purchase orders on record
				— {{ $purchaseOrders->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])->count() }} still open.
			</div>
		</div>
	</div>

	<div class="stat-grid">
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="4" width="18" height="17" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total POs</div>
			<div class="main">{{ $purchaseOrders->count() }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--warning">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Open</div>
			<div class="main">{{ $purchaseOrders->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])->count() }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--success">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M8 12.5l3 3 5-5" stroke-linecap="round" stroke-linejoin="round" />
					<circle cx="12" cy="12" r="9" />
				</svg>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Received</div>
			<div class="main">{{ $purchaseOrders->where('status', 'received')->count() }}</div>
		</div>
		<div class="stat-card" tabindex="0">
			<div class="stat-icon stat-icon--info">
				<span class="currency-icon">₱</span>
			</div>
			<div class="desc" style="font-size:1.08rem; color: var(--color-primary); font-weight:600; margin-bottom:0.5em;">Total Value (Open)</div>
			<div class="main">₱{{ number_format($purchaseOrders->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])->sum('total_value'), 2) }}</div>
		</div>
	</div>

	<!-- Filter Bar -->
	<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 22px;">
		<div>
			<select id="filterStatus" onchange="applyPoFilters()" style="padding: 10px 14px; border-radius: var(--radius-md); border: 2px solid var(--color-primary); font-size: 1rem; background: var(--color-page-bg);">
				<option value="">All Statuses</option>
				<option value="draft">Draft</option>
				<option value="sent">Sent</option>
				<option value="confirmed">Confirmed</option>
				<option value="partially_received">Partially Received</option>
				<option value="received">Received</option>
				<option value="cancelled">Cancelled</option>
			</select>
		</div>
	</div>

	<div class="data-table-container">
		<div style="font-weight: 700; font-size: 1.15rem; margin-bottom: 16px; color: var(--color-text); padding-left: 24px; padding-top: 18px;">
			All Purchase Orders
		</div>
		<div style="overflow-x: auto;">
			<table class="data-table" id="purchaseOrdersTable">
				<thead>
					<tr>
						<th>PO Number</th>
						<th>Supplier</th>
						<th>Status</th>
						<th>Total Value</th>
						<th>Expected Delivery</th>
						<th>Source</th>
						<th style="text-align: center;">Actions</th>
					</tr>
				</thead>
				<tbody>
					@forelse($purchaseOrders as $po)
						<tr data-status="{{ $po->status }}">
							<td style="font-weight: 600; color: var(--color-text);">{{ $po->po_number }}</td>
							<td>{{ $po->supplier->name ?? '—' }}</td>
							<td>
								@php
									$statusClass = match($po->status) {
										'received' => 'status-badge active',
										'cancelled' => 'status-badge inactive',
										'draft' => 'status-badge--info',
										'sent' => 'status-badge pending',
										'confirmed' => 'status-badge--info',
										'partially_received' => 'status-badge pending',
										default => 'status-badge',
									};
									$statusLabel = ucwords(str_replace('_', ' ', $po->status));
								@endphp
								<span class="{{ $statusClass }}">{{ $statusLabel }}</span>
							</td>
							<td>₱{{ number_format($po->total_value, 2) }}</td>
							<td>{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('Y-m-d') : '—' }}</td>
							<td>
								@if($po->is_auto_generated)
									<span class="status-badge--info status-badge">Auto</span>
								@else
									<span style="color: var(--color-text-muted);">Manual</span>
								@endif
							</td>
							<td style="text-align: center;">
								<button type="button" class="btn-action ghost view-po-btn" data-id="{{ $po->id }}" data-po-number="{{ $po->po_number }}">View</button>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="7" style="text-align: center; color: #aaa;">No purchase orders found.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>


<!-- Purchase Order Detail Modal -->
<div id="poDetailModal" class="modal-overlay" style="display: none;">
	<div class="modal-card" style="position: relative; width: 92%; max-width: 820px; max-height: 88vh; padding: 0; display: flex; flex-direction: column;">
		<div style="padding: 24px 28px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
			<h5 id="poDetailModalTitle" style="margin: 0; font-weight: 700; font-size: 1.2rem; color: var(--color-primary);">Purchase Order</h5>
			<button type="button" id="closePoDetailModal" style="background: none; border: none; font-size: 28px; cursor: pointer; color: var(--color-primary); line-height: 1;">&times;</button>
		</div>
		<div id="poDetailModalBody" style="padding: 24px 28px; overflow-y: auto;">
			<div style="text-align: center; color: var(--color-text-muted); padding: 40px 0;">Loading...</div>
		</div>
	</div>
</div>

<script>
	function applyPoFilters() {
		var statusFilter = document.getElementById('filterStatus').value;
		var rows = document.querySelectorAll('#purchaseOrdersTable tbody tr');
		rows.forEach(function (row) {
			if (!row.dataset.status) return;
			row.style.display = (!statusFilter || row.dataset.status === statusFilter) ? '' : 'none';
		});
	}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	$(function () {
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		// Open the modal and fetch the PO's detail fragment instead of navigating away.
		$(document).on('click', '.view-po-btn', function () {
			var id = $(this).data('id');
			var poNumber = $(this).data('po-number');

			$('#poDetailModalTitle').text('Purchase Order ' + poNumber);
			$('#poDetailModalBody').html('<div style="text-align: center; color: var(--color-text-muted); padding: 40px 0;">Loading...</div>');
			$('#poDetailModal').css('display', 'flex');

			$.ajax({
				url: '/purchase-orders/' + id,
				method: 'GET',
				success: function (html) {
					$('#poDetailModalBody').html(html);
				},
				error: function () {
					$('#poDetailModalBody').html('<div style="color: var(--color-danger);">Could not load purchase order details.</div>');
				}
			});
		});

		$('#closePoDetailModal').on('click', function () {
			$('#poDetailModal').hide();
		});
		$('#poDetailModal').on('click', function (e) {
			if (e.target.id === 'poDetailModal') $('#poDetailModal').hide();
		});

		// Delegated handler: the send-to-supplier form is injected into the
		// modal body above, so it doesn't exist in the DOM at page load.
		$(document).on('submit', '#sendToSupplierForm', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);

			confirmDialog('Email this purchase order to the supplier now?', {
				title: 'Send to supplier',
				confirmText: 'Send'
			}).then(function (confirmed) {
				if (!confirmed) return;
				setButtonLoading(submitBtn, true, 'Sending...');
				$.ajax({
					url: form.attr('action'),
					method: 'POST',
					data: form.serialize(),
					success: function (res) {
						showToast(res.message || 'Purchase order sent to supplier!', 'success');
						setTimeout(function () { location.reload(); }, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						showToast(xhr.responseJSON?.message || 'Could not send this purchase order.', 'error');
					}
				});
			});
		});

		// Delegated handlers: these forms are injected into the modal body
		// above, so they don't exist in the DOM at page load.
		$(document).on('submit', '#markConfirmedForm', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);

			confirmDialog('Mark this purchase order as confirmed by the supplier?', {
				title: 'Mark as confirmed',
				confirmText: 'Confirm'
			}).then(function (confirmed) {
				if (!confirmed) return;
				form.find('.mark-confirmed-note').val(window.prompt('Optional note (e.g. "Confirmed by phone"):', '') || '');
				setButtonLoading(submitBtn, true, 'Saving...');
				$.ajax({
					url: form.attr('action'),
					method: 'POST',
					data: form.serialize(),
					success: function (res) {
						showToast(res.message || 'Purchase order marked as confirmed.', 'success');
						setTimeout(function () { location.reload(); }, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						showToast(xhr.responseJSON?.message || 'Could not update this purchase order.', 'error');
					}
				});
			});
		});

		$(document).on('submit', '#markCancelledForm', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);

			confirmDialog('Mark this purchase order as cancelled?', {
				title: 'Mark as cancelled',
				confirmText: 'Cancel Order'
			}).then(function (confirmed) {
				if (!confirmed) return;
				form.find('.mark-cancelled-note').val(window.prompt('Optional note (e.g. "Declined by phone - out of stock"):', '') || '');
				setButtonLoading(submitBtn, true, 'Saving...');
				$.ajax({
					url: form.attr('action'),
					method: 'POST',
					data: form.serialize(),
					success: function (res) {
						showToast(res.message || 'Purchase order cancelled.', 'success');
						setTimeout(function () { location.reload(); }, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						showToast(xhr.responseJSON?.message || 'Could not update this purchase order.', 'error');
					}
				});
			});
		});

		$(document).on('submit', '#receiveDeliveryForm', function (e) {
			e.preventDefault();
			var form = $(this);
			var submitBtn = form.find('button[type="submit"]').get(0);

			confirmDialog('Confirm receipt of this delivery? Stock levels will be updated immediately.', {
				title: 'Receive delivery',
				confirmText: 'Receive'
			}).then(function (confirmed) {
				if (!confirmed) return;
				setButtonLoading(submitBtn, true, 'Processing...');
				$.ajax({
					url: form.attr('action'),
					method: 'POST',
					data: form.serialize(),
					success: function () {
						showToast('Delivery received successfully!', 'success');
						setTimeout(function () { location.reload(); }, 1200);
					},
					error: function (xhr) {
						setButtonLoading(submitBtn, false);
						var errors = xhr.responseJSON?.errors;
						var firstError = errors ? Object.values(errors)[0]?.[0] : null;
						showToast(firstError || xhr.responseJSON?.message || 'Could not process delivery.', 'error');
					}
				});
			});
		});
	});
</script>
@endsection
