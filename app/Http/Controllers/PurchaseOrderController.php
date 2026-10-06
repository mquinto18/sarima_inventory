<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceipt;
use App\Services\InventoryService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends Controller
{
    /**
     * Purchase orders are a procurement / back-office concern - staff
     * (cashier-like role) should never be able to hit these endpoints even
     * if they guess the URL, matching the inline-check style used in
     * ProductController.
     */
    private function denyStaff()
    {
        if (Auth::user()->role === 'staff') {
            abort(403);
        }
    }

    /**
     * Display a listing of purchase orders, newest first.
     */
    public function index()
    {
        $this->denyStaff();

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->orderByDesc('created_at')
            ->get();

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.purchase-orders.index', compact(
            'purchaseOrders',
            'reorderCount',
            'reorderNotifications',
            'pendingApprovalCount',
            'notificationCount'
        ));
    }

    /**
     * Display the specified purchase order with its line items and, when
     * applicable, the "Receive Delivery" form.
     */
    public function show(Request $request, $id)
    {
        $this->denyStaff();

        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.product', 'creator'])
            ->findOrFail($id);

        // The Purchase Orders index opens this in a modal via AJAX rather
        // than navigating away; serve just the detail fragment in that case.
        if ($request->ajax() || $request->wantsJson()) {
            return view('pages.purchase-orders._detail', compact('purchaseOrder'));
        }

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.purchase-orders.show', compact(
            'purchaseOrder',
            'reorderCount',
            'reorderNotifications',
            'pendingApprovalCount',
            'notificationCount'
        ));
    }

    /**
     * Email this draft purchase order to its supplier: generates the PDF,
     * issues a confirmation token embedded in the Reply-To address (so the
     * supplier's reply can be matched back to this PO by the inbound mail
     * webhook), and marks the PO as sent.
     */
    public function send(Request $request, $id)
    {
        $this->denyStaff();

        $purchaseOrder = PurchaseOrder::with('supplier')->findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            $message = 'Only draft purchase orders can be sent to the supplier.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        try {
            app(PurchaseOrderService::class)->sendToSupplier($purchaseOrder);
        } catch (\Throwable $e) {
            Log::error("Failed to send PO {$purchaseOrder->po_number}: " . $e->getMessage());

            $message = 'Could not send this purchase order to the supplier. Please try again.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->with('error', $message);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order sent to supplier.',
                'purchase_order' => $purchaseOrder->fresh(['supplier', 'items.product']),
            ]);
        }

        return redirect()->back()->with('success', 'Purchase order sent to supplier!');
    }

    /**
     * Manual override for when the supplier confirms by phone/in person, or
     * their confirmation link is unreachable (e.g. a PO emailed before a
     * deployment/URL fix) - lets an admin record the same outcome the
     * supplier's own Approve button or email reply would have, without
     * depending on either.
     */
    public function markConfirmed(Request $request, $id)
    {
        $this->denyStaff();

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'sent') {
            $message = 'Only a sent purchase order can be manually confirmed.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $note = trim((string) $request->input('note'));

        $purchaseOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmation_note' => $note !== '' ? $note : 'Manually confirmed by ' . (Auth::user()->name ?? 'admin') . ' (no supplier link response).',
        ]);

        Log::info("PurchaseOrderController: PO {$purchaseOrder->po_number} manually marked confirmed by user #" . Auth::id() . '.');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Purchase order marked as confirmed.']);
        }

        return redirect()->back()->with('success', 'Purchase order marked as confirmed.');
    }

    /**
     * Manual override for cancelling a purchase order the supplier declined
     * by phone/in person, or that's no longer needed - same effect as the
     * supplier's own Decline button, without depending on it. Allowed any
     * time before the order is received, matching how far along a real
     * decline could plausibly still happen.
     */
    public function markCancelled(Request $request, $id)
    {
        $this->denyStaff();

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            $message = 'This purchase order is already finalized and cannot be cancelled.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $note = trim((string) $request->input('note'));

        $purchaseOrder->update([
            'status' => 'cancelled',
            'confirmation_note' => $note !== '' ? $note : 'Manually cancelled by ' . (Auth::user()->name ?? 'admin') . '.',
        ]);

        Log::info("PurchaseOrderController: PO {$purchaseOrder->po_number} manually cancelled by user #" . Auth::id() . '.');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Purchase order cancelled.']);
        }

        return redirect()->back()->with('success', 'Purchase order cancelled.');
    }

    /**
     * Receive a delivery against this purchase order: adds stock via
     * InventoryService (the only place stock is ever mutated), increments
     * quantity_received per line, and recalculates the PO status.
     */
    public function receiveDelivery(Request $request, $id)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'items' => 'array',
            'items.*.quantity_received' => 'nullable|integer|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        // Required whenever that same line is actually receiving stock
        // (qty > 0) - newly arrived stock has its own expiry, and since this
        // app tracks one expiry_date per product rather than per batch,
        // skipping it would silently leave the old batch's date attached to
        // the new one. Done as a manual pass after validate() rather than a
        // validation rule: Laravel skips non-required rules (including
        // closures) entirely when a field is empty, which made a closure-
        // based "required if quantity > 0" check never actually run.
        $missingExpiry = [];

        foreach ($validated['items'] ?? [] as $itemId => $itemInput) {
            $qty = (int) ($itemInput['quantity_received'] ?? 0);

            if ($qty > 0 && empty($itemInput['expiry_date'] ?? null)) {
                $missingExpiry["items.{$itemId}.expiry_date"] = ['An expiry date is required for any item being received.'];
            }
        }

        if (!empty($missingExpiry)) {
            throw \Illuminate\Validation\ValidationException::withMessages($missingExpiry);
        }

        $purchaseOrder = DB::transaction(function () use ($id, $validated) {
            // Lock the PO row so two concurrent receive requests for the same
            // PO serialize instead of both reading the same pre-lock
            // quantity_received and double-crediting stock.
            $purchaseOrder = PurchaseOrder::where('id', $id)->lockForUpdate()->firstOrFail();
            $purchaseOrder->load('items.product');

            $itemsInput = $validated['items'] ?? [];
            $inventoryService = new InventoryService();

            foreach ($purchaseOrder->items as $item) {
                $qty = (int) ($itemsInput[$item->id]['quantity_received'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                // Never allow receiving more than what remains on the line.
                $remaining = $item->quantity_ordered - $item->quantity_received;
                $qty = min($qty, max(0, $remaining));

                if ($qty <= 0) {
                    continue;
                }

                $inventoryService->addStock($item->product, $qty, 'purchase_receipt', $purchaseOrder, Auth::id());
                $item->increment('quantity_received', $qty);

                // The new stock replaces whatever batch was there before
                // (this app tracks one expiry_date per product, not per
                // batch) - overwrite intentionally, including clearing a
                // null left behind by a full expired-stock write-off.
                if (!empty($itemsInput[$item->id]['expiry_date'])) {
                    $item->product->update(['expiry_date' => $itemsInput[$item->id]['expiry_date']]);
                }

                // quantity_received is only a running total, so the individual
                // delivery is recorded here. Written inside the same transaction
                // as the stock movement above: a receipt row can never exist for
                // stock that was not actually credited.
                PurchaseOrderReceipt::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_received' => $qty,
                    'received_by' => Auth::id(),
                    'received_at' => now(),
                ]);
            }

            $purchaseOrder->refresh();
            $items = $purchaseOrder->items()->get();

            $allReceived = $items->every(function ($item) {
                return $item->quantity_received >= $item->quantity_ordered;
            });
            $anyReceived = $items->contains(function ($item) {
                return $item->quantity_received > 0;
            });

            if ($allReceived) {
                $purchaseOrder->status = 'received';
                $purchaseOrder->delivered_at = now();
                $purchaseOrder->save();
            } elseif ($anyReceived) {
                $purchaseOrder->status = 'partially_received';
                $purchaseOrder->save();
            }

            return $purchaseOrder;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Delivery received successfully',
                'purchase_order' => $purchaseOrder->fresh(['supplier', 'items.product']),
            ]);
        }

        return redirect()->back()->with('success', 'Delivery received successfully!');
    }
}
