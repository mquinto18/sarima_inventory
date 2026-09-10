<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceipt;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        ]);

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
