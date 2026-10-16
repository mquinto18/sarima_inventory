<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
</head>
{{-- Table-based, inline-styled markup throughout: email clients don't load
     external stylesheets or support CSS variables/flexbox reliably, so the
     app's design tokens (resources/css/app.css) are hand-translated to
     literal hex values and bulletproof <table> layout instead. --}}
<body style="margin: 0; padding: 0; background-color: #f1f3f9; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f3f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e5e7eb;">
                    {{-- Letterhead --}}
                    <tr>
                        <td style="padding: 32px 36px 24px; border-bottom: 2px solid #18181b;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align: middle;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align: middle; padding-right: 10px;">
                                                    <img src="{{ $message->embed(public_path('images/logo-icon-128.png')) }}" width="32" height="32" alt="" style="display: block; border-radius: 6px;">
                                                </td>
                                                <td style="vertical-align: middle;">
                                                    <div style="font-size: 1.05rem; font-weight: 700; color: #18181b; letter-spacing: 0.01em;">Larios Pharmacy</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <div style="font-size: 0.72rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em;">Purchase Order</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Document meta: PO number, date, supplier - the formal
                         reference block any procurement contact expects to
                         see at a glance, matching the PDF attachment. --}}
                    <tr>
                        <td style="padding: 24px 36px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="33%" style="vertical-align: top;">
                                        <div style="color: #6b7280; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">PO Number</div>
                                        <div style="color: #18181b; font-size: 0.95rem; font-weight: 700; margin-top: 3px;">{{ $purchaseOrder->po_number }}</div>
                                    </td>
                                    <td width="33%" style="vertical-align: top;">
                                        <div style="color: #6b7280; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Date Issued</div>
                                        <div style="color: #18181b; font-size: 0.95rem; font-weight: 700; margin-top: 3px;">{{ $purchaseOrder->created_at?->format('F j, Y') }}</div>
                                    </td>
                                    <td width="34%" style="vertical-align: top;">
                                        <div style="color: #6b7280; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Expected Delivery</div>
                                        <div style="color: #18181b; font-size: 0.95rem; font-weight: 700; margin-top: 3px;">{{ $purchaseOrder->expected_delivery_date?->format('F j, Y') ?? '—' }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Salutation --}}
                    <tr>
                        <td style="padding: 28px 36px 4px;">
                            <p style="margin: 0 0 14px; color: #18181b; font-size: 0.95rem;">Dear {{ $purchaseOrder->supplier->name }},</p>
                            <p style="margin: 0 0 4px; color: #374151; font-size: 0.92rem; line-height: 1.65;">
                                Please find below our order for {{ $purchaseOrder->items->count() }} {{ $purchaseOrder->items->count() == 1 ? 'item' : 'items' }}, detailed in the attached PDF. Kindly confirm receipt and expected delivery at your earliest convenience.
                            </p>
                        </td>
                    </tr>

                    {{-- Line items --}}
                    <tr>
                        <td style="padding: 20px 36px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; border-collapse: separate;">
                                <tr>
                                    <td style="background-color: #f8fafc; padding: 11px 16px; font-size: 0.76rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid #e5e7eb;">Product</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 11px 16px; font-size: 0.76rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid #e5e7eb;">Qty</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 11px 16px; font-size: 0.76rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid #e5e7eb;">Unit Cost</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 11px 16px; font-size: 0.76rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid #e5e7eb;">Subtotal</td>
                                </tr>
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td style="padding: 11px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">{{ $item->product->name ?? '—' }}</td>
                                        <td align="right" style="padding: 11px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">{{ number_format($item->quantity_ordered) }}</td>
                                        <td align="right" style="padding: 11px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">₱{{ number_format($item->unit_cost, 2) }}</td>
                                        <td align="right" style="padding: 11px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">₱{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3" align="right" style="background-color: #f8fafc; padding: 12px 16px; font-size: 0.85rem; font-weight: 700; color: #18181b; border-top: 1px solid #e5e7eb;">Total Order Value</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 12px 16px; font-size: 0.95rem; font-weight: 700; color: #18181b; border-top: 1px solid #e5e7eb;">₱{{ number_format($purchaseOrder->total_value, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if($purchaseOrder->confirmation_token)
                        {{-- Approve / Decline: both are plain GET links to a landing page
                             that itself requires a POST to actually change anything, so an
                             email "Safe Links" scanner prefetching this href can't silently
                             approve/decline on the supplier's behalf. --}}
                        <tr>
                            <td style="padding: 24px 36px 8px;">
                                <p style="margin: 0 0 14px; color: #374151; font-size: 0.9rem; font-weight: 600;">Please confirm this order:</p>
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="border-radius: 6px; background-color: #18181b;">
                                            <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'approve']) }}"
                                                style="display: inline-block; padding: 11px 24px; font-size: 0.88rem; font-weight: 600; color: #ffffff; text-decoration: none;">
                                                Approve Order
                                            </a>
                                        </td>
                                        <td width="12"></td>
                                        <td style="border-radius: 6px; border: 1px solid #d1d5db;">
                                            <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'decline']) }}"
                                                style="display: inline-block; padding: 10px 23px; font-size: 0.88rem; font-weight: 600; color: #374151; text-decoration: none;">
                                                Decline Order
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 36px 28px;">
                                <p style="margin: 0; color: #6b7280; font-size: 0.82rem;">Alternatively, you may reply directly to this email to confirm.</p>
                            </td>
                        </tr>
                    @else
                        <tr><td style="padding: 0 0 20px;"></td></tr>
                    @endif

                    {{-- Closing / signature --}}
                    <tr>
                        <td style="padding: 20px 36px 28px; border-top: 1px solid #f3f4f6;">
                            <p style="margin: 0; color: #18181b; font-size: 0.9rem; line-height: 1.6;">
                                Sincerely,<br>
                                <strong>Larios Pharmacy</strong><br>
                                <span style="color: #6b7280;">Purchasing Department</span>
                            </p>
                            <p style="margin: 18px 0 0; color: #9ca3af; font-size: 0.74rem;">This purchase order was generated automatically by Larios Pharmacy's inventory management system.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
