<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Suppliers are a procurement / back-office concern - staff (cashier-like
     * role) should never be able to hit these endpoints even if they guess
     * the URL, matching the inline-check style used in ProductController.
     */
    private function denyStaff()
    {
        if (Auth::user()->role === 'staff') {
            abort(403);
        }
    }

    /**
     * Display a listing of suppliers with their linked products.
     */
    public function index()
    {
        $this->denyStaff();

        $suppliers = Supplier::with(['products' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        // Eager-loaded so the "Link Product to Supplier" modal can hide,
        // per product, whichever suppliers already have it linked - without
        // an N+1 query per row.
        $products = Product::with('suppliers')->orderBy('name')->get();

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.suppliers', compact(
            'suppliers',
            'products',
            'reorderCount',
            'reorderNotifications',
            'pendingApprovalCount',
            'notificationCount'
        ));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:64',
            'address' => 'nullable|string|max:500',
            'lead_time_days' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['lead_time_days'] = $validated['lead_time_days'] ?? 7;

        $supplier = Supplier::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'supplier' => $supplier]);
        }

        return redirect()->back()->with('success', 'Supplier added successfully!');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:64',
            'address' => 'nullable|string|max:500',
            'lead_time_days' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['lead_time_days'] = $validated['lead_time_days'] ?? 7;

        $supplier->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully',
                'supplier' => $supplier,
            ]);
        }

        return redirect()->back()->with('success', 'Supplier updated successfully!');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id)
    {
        $this->denyStaff();

        $supplier = Supplier::findOrFail($id);

        try {
            $supplier->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier deleted successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Supplier deleted successfully!');
        } catch (QueryException $e) {
            Log::error("Failed to delete supplier #{$id}: " . $e->getMessage());

            // SQLSTATE 23000: integrity constraint violation - this supplier
            // still has purchase order history referencing it, which must be
            // kept for the record. Deactivating (instead of deleting) is
            // already supported via the Edit form's "Active" checkbox.
            $message = $e->getCode() === '23000'
                ? "Cannot delete \"{$supplier->name}\" because it has purchase order history on record. Deactivate it instead (Edit → uncheck Active) to stop new orders while keeping its history."
                : 'Error deleting supplier. Please try again.';

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Link (or update the link for) a product to a supplier.
     *
     * At most one product_supplier row per product may have is_primary=true,
     * so when the incoming link is primary we first demote every other link
     * for that product inside a transaction.
     */
    public function linkProduct(Request $request)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'cost_price' => 'required|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
        ]);

        $link = DB::transaction(function () use ($validated, $request) {
            return $this->upsertProductSupplierLink(
                (int) $validated['product_id'],
                (int) $validated['supplier_id'],
                (float) $validated['cost_price'],
                isset($validated['lead_time_days']) ? (int) $validated['lead_time_days'] : null,
                $request->boolean('is_primary')
            );
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product linked to supplier successfully',
                'link' => $link,
            ]);
        }

        return redirect()->back()->with('success', 'Product linked to supplier successfully!');
    }

    /**
     * Link several products to one supplier in a single request, instead of
     * repeating the single-product form one product at a time. Cost price is
     * still required per product (it varies by product and feeds the
     * auto-reorder value caps), but supplier / lead time / primary flag are
     * shared across the whole batch.
     */
    public function linkProducts(Request $request)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
            'products' => 'required|array|min:1',
            'products.*.cost_price' => 'required|numeric|min:0',
        ]);

        $productIds = array_keys($validated['products']);

        if (Product::whereIn('id', $productIds)->count() !== count($productIds)) {
            $message = 'One or more selected products could not be found.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $isPrimary = $request->boolean('is_primary');
        $leadTimeDays = isset($validated['lead_time_days']) ? (int) $validated['lead_time_days'] : null;

        $links = DB::transaction(function () use ($validated, $isPrimary, $leadTimeDays) {
            $links = [];

            foreach ($validated['products'] as $productId => $product) {
                $links[] = $this->upsertProductSupplierLink(
                    (int) $productId,
                    (int) $validated['supplier_id'],
                    (float) $product['cost_price'],
                    $leadTimeDays,
                    $isPrimary
                );
            }

            return $links;
        });

        $message = count($links) . ' product' . (count($links) === 1 ? '' : 's') . ' linked to supplier successfully';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'links' => $links,
            ]);
        }

        return redirect()->back()->with('success', $message . '!');
    }

    /**
     * Shared by linkProduct()/linkProducts(): creates or updates one
     * product_supplier row. At most one row per product may have
     * is_primary=true, so an incoming primary link first demotes every other
     * link for that product. When the link is primary, the product's selling
     * price is also recalculated from this cost (see class docblock note on
     * Setting::get('default_markup_percent')) - a product can have several
     * suppliers at different costs, but only one retail price, so only the
     * primary supplier's cost should ever drive it. Must be called from
     * within a transaction (bulk callers loop this multiple times).
     */
    private function upsertProductSupplierLink(int $productId, int $supplierId, float $costPrice, ?int $leadTimeDays, bool $isPrimary): ProductSupplier
    {
        if ($isPrimary) {
            ProductSupplier::where('product_id', $productId)->update(['is_primary' => false]);
        }

        $link = ProductSupplier::updateOrCreate(
            ['product_id' => $productId, 'supplier_id' => $supplierId],
            ['cost_price' => $costPrice, 'lead_time_days' => $leadTimeDays, 'is_primary' => $isPrimary]
        );

        if ($isPrimary) {
            $markup = (float) Setting::get('default_markup_percent', 10);
            Product::where('id', $productId)->update([
                'price' => round($costPrice * (1 + $markup / 100), 2),
            ]);
        }

        return $link;
    }

    /**
     * Unlink a product from a supplier.
     */
    public function unlinkProduct($productSupplierId)
    {
        $this->denyStaff();

        try {
            $link = ProductSupplier::findOrFail($productSupplierId);
            $link->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product unlinked from supplier successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Product unlinked from supplier successfully!');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error unlinking product: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error unlinking product');
        }
    }
}
