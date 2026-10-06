<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;

/**
 * Public, unauthenticated: reached by a supplier clicking Approve/Decline in
 * their PO email, not by a logged-in user. Lookup and every state change is
 * keyed off the per-PO confirmation_token instead of a session/login.
 */
class PurchaseOrderConfirmationController extends Controller
{
    public function show(string $token)
    {
        $purchaseOrder = PurchaseOrder::where('confirmation_token', $token)
            ->with(['supplier', 'items.product'])
            ->first();

        if (!$purchaseOrder) {
            return view('supplier.purchase-order-confirm', ['state' => 'invalid']);
        }

        if ($purchaseOrder->status !== 'sent') {
            return view('supplier.purchase-order-confirm', [
                'state' => 'already_processed',
                'purchaseOrder' => $purchaseOrder,
            ]);
        }

        return view('supplier.purchase-order-confirm', [
            'state' => 'pending',
            'purchaseOrder' => $purchaseOrder,
            'action' => request('action'),
        ]);
    }

    public function approve(string $token)
    {
        $purchaseOrder = PurchaseOrder::where('confirmation_token', $token)->first();

        if (!$purchaseOrder) {
            return view('supplier.purchase-order-confirm', ['state' => 'invalid']);
        }

        // Re-checked here, not just on the page that linked here: guards
        // against a double-submit, and against racing the Postmark
        // reply-to-email webhook, which uses this exact same guard.
        if ($purchaseOrder->status === 'sent') {
            $purchaseOrder->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            Log::info("PurchaseOrderConfirmation: PO {$purchaseOrder->po_number} approved by supplier via email link.");
        }

        return view('supplier.purchase-order-confirm', [
            'state' => 'approved',
            'purchaseOrder' => $purchaseOrder->fresh(),
        ]);
    }

    public function decline(string $token)
    {
        $purchaseOrder = PurchaseOrder::where('confirmation_token', $token)->first();

        if (!$purchaseOrder) {
            return view('supplier.purchase-order-confirm', ['state' => 'invalid']);
        }

        if ($purchaseOrder->status === 'sent') {
            $purchaseOrder->update([
                'status' => 'cancelled',
                'confirmation_note' => 'Declined by supplier via email link.',
            ]);
            Log::info("PurchaseOrderConfirmation: PO {$purchaseOrder->po_number} declined by supplier via email link.");
        }

        return view('supplier.purchase-order-confirm', [
            'state' => 'declined',
            'purchaseOrder' => $purchaseOrder->fresh(),
        ]);
    }
}
