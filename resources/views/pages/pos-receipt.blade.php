<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Receipt - Larios Pharmacy</title>
	<style>
		* {
			box-sizing: border-box;
		}

		body {
			font-family: 'Courier New', Courier, monospace;
			background: #f3f4f6;
			margin: 0;
			padding: 24px 12px;
			color: #111827;
		}

		.receipt {
			max-width: 380px;
			margin: 0 auto;
			background: #fff;
			padding: 24px 22px;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
		}

		.receipt-header {
			text-align: center;
			border-bottom: 1px dashed #9ca3af;
			padding-bottom: 14px;
			margin-bottom: 14px;
		}

		.receipt-logo {
			width: 52px;
			height: 52px;
			object-fit: contain;
			margin: 0 auto 8px;
			display: block;
		}

		.receipt-header h1 {
			font-size: 1.25rem;
			margin: 0 0 4px 0;
			letter-spacing: 1px;
		}

		.receipt-header p {
			margin: 2px 0;
			font-size: 0.8rem;
			color: #4b5563;
		}

		.receipt-meta {
			font-size: 0.8rem;
			margin-bottom: 14px;
			color: #374151;
		}

		.receipt-meta div {
			display: flex;
			justify-content: space-between;
			margin-bottom: 2px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			font-size: 0.82rem;
			margin-bottom: 14px;
		}

		thead th {
			text-align: left;
			border-bottom: 1px dashed #9ca3af;
			padding-bottom: 6px;
		}

		thead th:last-child,
		tbody td:last-child {
			text-align: right;
		}

		tbody td {
			padding: 6px 0;
			border-bottom: 1px dotted #e5e7eb;
			vertical-align: top;
		}

		.receipt-total-row {
			display: flex;
			justify-content: space-between;
			font-size: 1.05rem;
			font-weight: 700;
			border-top: 1px dashed #9ca3af;
			padding-top: 10px;
			margin-top: 4px;
		}

		.receipt-tender-row {
			display: flex;
			justify-content: space-between;
			font-size: 0.98rem;
			padding-top: 6px;
		}

		.receipt-tender-row--change {
			font-weight: 700;
			font-size: 1.05rem;
			border-top: 1px dotted #9ca3af;
			margin-top: 6px;
			padding-top: 8px;
		}

		.receipt-footer {
			text-align: center;
			margin-top: 18px;
			font-size: 0.78rem;
			color: #6b7280;
		}

		.no-print {
			text-align: center;
			margin-top: 20px;
		}

		.print-btn {
			background: #111827;
			color: #fff;
			border: none;
			border-radius: 6px;
			padding: 10px 22px;
			font-size: 0.95rem;
			cursor: pointer;
		}

		.print-btn:hover {
			background: #1f2937;
		}

		@media print {
			body {
				background: #fff;
				padding: 0;
			}

			.receipt {
				box-shadow: none;
				max-width: 100%;
			}

			.no-print {
				display: none;
			}
		}
	</style>
</head>

<body>
	<div class="receipt">
		<div class="receipt-header">
			<img src="{{ asset('images/logo-icon-64.png') }}" alt="Larios Pharmacy" class="receipt-logo">
			<h1>LARIOS PHARMACY</h1>
			<p>Official Sales Receipt</p>
		</div>

		<div class="receipt-meta">
			<div><span>Transaction ID:</span><span>{{ substr($transactionId, 0, 8) }}</span></div>
			<div><span>Date:</span><span>{{ now()->format('M d, Y h:i A') }}</span></div>
		</div>

		<table>
			<thead>
				<tr>
					<th>Item</th>
					<th>Qty</th>
					<th>Price</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				@foreach($sales as $sale)
					<tr>
						<td>{{ $sale->product->name ?? 'Unknown item' }}</td>
						<td>{{ $sale->quantity_sold }}</td>
						<td>&#8369;{{ number_format($sale->unit_price, 2) }}</td>
						<td>&#8369;{{ number_format($sale->total_amount, 2) }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>

		<div class="receipt-total-row">
			<span>TOTAL</span>
			<span>&#8369;{{ number_format($totalAmount, 2) }}</span>
		</div>

		{{-- Null for sales recorded before cash tendering was captured. --}}
		@if(!is_null($amountTendered))
			<div class="receipt-tender-row">
				<span>Cash Received</span>
				<span>&#8369;{{ number_format($amountTendered, 2) }}</span>
			</div>
			<div class="receipt-tender-row receipt-tender-row--change">
				<span>CHANGE</span>
				<span>&#8369;{{ number_format($changeDue ?? 0, 2) }}</span>
			</div>
		@endif

		<div class="receipt-footer">
			<p>Thank you for your purchase!</p>
			<p>Please keep this receipt for any returns or exchanges.</p>
		</div>
	</div>

	<div class="no-print">
		<button type="button" class="print-btn" onclick="window.print()">Print Receipt</button>
	</div>
</body>

</html>
