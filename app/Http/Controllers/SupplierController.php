<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $products = Product::orderBy('name')->get();

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

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier deleted successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Supplier deleted successfully!');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting supplier: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error deleting supplier');
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

        $isPrimary = $request->boolean('is_primary');

        $link = DB::transaction(function () use ($validated, $isPrimary) {
            if ($isPrimary) {
                ProductSupplier::where('product_id', $validated['product_id'])
                    ->update(['is_primary' => false]);
            }

            return ProductSupplier::updateOrCreate(
                [
                    'product_id' => $validated['product_id'],
                    'supplier_id' => $validated['supplier_id'],
                ],
                [
                    'cost_price' => $validated['cost_price'],
                    'lead_time_days' => $validated['lead_time_days'] ?? null,
                    'is_primary' => $isPrimary,
                ]
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
