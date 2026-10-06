@extends('layouts.app')

@section('content')
<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">Purchase Order {{ $purchaseOrder->po_number }}</div>
		</div>
		<div class="dashboard-hero-actions">
			<a href="{{ route('purchase-orders.index') }}" class="btn-action ghost">Back to Purchase Orders</a>
		</div>
	</div>

	@include('pages.purchase-orders._detail', ['purchaseOrder' => $purchaseOrder])
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	$(function () {
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		$('#sendToSupplierForm').on('submit', function (e) {
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

		$('#markConfirmedForm').on('submit', function (e) {
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

		$('#markCancelledForm').on('submit', function (e) {
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

		$('#receiveDeliveryForm').on('submit', function (e) {
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
