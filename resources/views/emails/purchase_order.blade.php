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
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 2px 12px 0 rgba(99, 102, 241, 0.08);">
                    {{-- Brand accent bar --}}
                    <tr>
                        <td style="background-color: #6366f1; height: 6px; line-height: 6px; font-size: 0;">&nbsp;</td>
                    </tr>

                    {{-- Header --}}
                    <tr>
                        <td style="padding: 28px 32px 20px; border-bottom: 1px solid #f3f4f6;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align: middle; padding-right: 12px;">
                                        <img src="{{ $message->embed(public_path('images/logo-icon-128.png')) }}" width="36" height="36" alt="" style="display: block; border-radius: 8px;">
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <span style="font-size: 1.1rem; font-weight: 700; color: #18181b;">Larios Pharmacy</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 28px 32px 8px;">
                            <p style="margin: 0 0 16px; color: #18181b; font-size: 1rem;">Dear {{ $purchaseOrder->supplier->name }},</p>
                            <p style="margin: 0 0 20px; color: #374151; font-size: 0.95rem; line-height: 1.6;">
                                Please find attached
                                <span style="display: inline-block; background-color: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.85rem; padding: 3px 10px; border-radius: 999px;">
                                    {{ $purchaseOrder->po_number }}
                                </span>
                                for {{ $purchaseOrder->items->count() }} {{ $purchaseOrder->items->count() == 1 ? 'item' : 'items' }}.
                            </p>
                        </td>
                    </tr>

                    {{-- Summary stats --}}
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="background-color: #f8fafc; border-radius: 12px; padding: 14px 18px;">
                                        <div style="color: #6b7280; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;">Expected Delivery</div>
                                        <div style="color: #18181b; font-size: 1rem; font-weight: 700; margin-top: 2px;">{{ $purchaseOrder->expected_delivery_date?->format('Y-m-d') ?? '—' }}</div>
                                    </td>
                                    <td width="12"></td>
                                    <td width="50%" style="background-color: #f8fafc; border-radius: 12px; padding: 14px 18px;">
                                        <div style="color: #6b7280; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;">Total Order Value</div>
                                        <div style="color: #18181b; font-size: 1rem; font-weight: 700; margin-top: 2px;">₱{{ number_format($purchaseOrder->total_value, 2) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Line items --}}
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #f3f4f6; border-radius: 12px; overflow: hidden; border-collapse: separate;">
                                <tr>
                                    <td style="background-color: #f8fafc; padding: 10px 16px; font-size: 0.8rem; font-weight: 700; color: #374151; border-bottom: 1px solid #f3f4f6;">Product</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 10px 16px; font-size: 0.8rem; font-weight: 700; color: #374151; border-bottom: 1px solid #f3f4f6;">Qty</td>
                                    <td align="right" style="background-color: #f8fafc; padding: 10px 16px; font-size: 0.8rem; font-weight: 700; color: #374151; border-bottom: 1px solid #f3f4f6;">Subtotal</td>
                                </tr>
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td style="padding: 10px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">{{ $item->product->name ?? '—' }}</td>
                                        <td align="right" style="padding: 10px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">{{ number_format($item->quantity_ordered) }}</td>
                                        <td align="right" style="padding: 10px 16px; font-size: 0.9rem; color: #18181b; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">₱{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    @if($purchaseOrder->confirmation_token)
                        {{-- Approve / Decline: both are plain GET links to a landing page
                             that itself requires a POST to actually change anything, so an
                             email "Safe Links" scanner prefetching this href can't silently
                             approve/decline on the supplier's behalf. --}}
                        <tr>
                            <td style="padding: 0 32px 8px;">
                                <p style="margin: 0 0 14px; color: #374151; font-size: 0.95rem; font-weight: 600;">Please confirm this order:</p>
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="border-radius: 8px; background-color: #16a34a;">
                                            <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'approve']) }}"
                                                style="display: inline-block; padding: 11px 22px; font-size: 0.9rem; font-weight: 700; color: #ffffff; text-decoration: none;">
                                                ✅ Approve Order
                                            </a>
                                        </td>
                                        <td width="12"></td>
                                        <td style="border-radius: 8px; border: 1.5px solid #ef4444;">
                                            <a href="{{ route('purchase-orders.confirm.show', ['token' => $purchaseOrder->confirmation_token, 'action' => 'decline']) }}"
                                                style="display: inline-block; padding: 9px 20px; font-size: 0.9rem; font-weight: 700; color: #ef4444; text-decoration: none;">
                                                ❌ Decline Order
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 32px 28px;">
                                <p style="margin: 0; color: #6b7280; font-size: 0.85rem;">Or reply directly to this email to confirm.</p>
                            </td>
                        </tr>
                    @else
                        <tr><td style="padding: 0 0 20px;"></td></tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 32px 28px; border-top: 1px solid #f3f4f6;">
                            <p style="margin: 0 0 4px; color: #18181b; font-size: 0.9rem;">Thank you,<br><strong>Larios Pharmacy</strong></p>
                            <p style="margin: 16px 0 0; color: #9ca3af; font-size: 0.78rem;">Auto-generated by inventory system</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
