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
						showToast(xhr.responseJSON?.message || 'Could not process delivery.', 'error');
					}
				});
			});
		});
	});
</script>
@endsection
