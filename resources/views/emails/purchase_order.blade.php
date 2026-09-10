<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Dear {{ $purchaseOrder->supplier->name }},</p>

    <p>
        Please find attached PO #{{ $purchaseOrder->po_number }} for
        {{ $purchaseOrder->items->count() }} item(s), expected delivery reference date
        {{ $purchaseOrder->expected_delivery_date?->format('Y-m-d') }}.
    </p>

    <p>Total order value: {{ number_format($purchaseOrder->total_value, 2) }}</p>

    <p>Thank you,<br>Larios Pharmacy</p>
</body>
</html>
