<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ShortPaymentException;
use App\Models\EditRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    /**
     * POS checkout screen. Cashiers of any role can use it; product data is
     * embedded as JSON for the initial render, with /pos/lookup used for
     * live search/barcode-scan lookups afterwards.
     */
    public function index()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'price', 'stock']);

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.pos', compact(
            'products',
            'reorderCount',
            'reorderNotifications',
            'pendingApprovalCount',
            'notificationCount'
        ));
    }

    /**
     * SKU-first exact match, falling back to a LIKE search on name/sku.
     * Kept deliberately small (only the fields the cart needs) since this
     * is called on every keystroke/scan.
     */
    public function lookup(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json(['products' => []]);
        }

        $columns = ['id', 'name', 'sku', 'price', 'stock'];

        $exact = Product::whereRaw('LOWER(sku) = ?', [strtolower($q)])->first($columns);

        if ($exact) {
            return response()->json(['products' => [$exact]]);
        }

        $products = Product::where('name', 'LIKE', "%{$q}%")
            ->orWhere('sku', 'LIKE', "%{$q}%")
            ->orderBy('name')
            ->limit(20)
            ->get($columns);

        return response()->json(['products' => $products]);
    }

    /**
     * Checkout the cart. Each line becomes a real Sale row (so forecasting
     * keeps working unmodified) and goes through InventoryService for the
     * actual stock mutation. All lines share one pos_transaction_id so the
     * receipt can group them; if any line oversells, the whole transaction
     * rolls back and nothing is charged/deducted.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_tendered' => 'required|numeric|min:0',
        ], [
            'amount_tendered.required' => 'Enter the amount the customer handed over.',
        ]);

        $transactionId = (string) Str::uuid();
        $tendered = round((float) $validated['amount_tendered'], 2);
        $updatedStock = [];
        $totalAmount = 0;
        $changeDue = 0;

        try {
            DB::transaction(function () use ($validated, $transactionId, $tendered, &$updatedStock, &$totalAmount, &$changeDue) {
                $lines = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $quantity = (int) $item['quantity'];
                    $lineTotal = round($quantity * (float) $product->price, 2);

                    $lines[] = [$product, $quantity, $lineTotal];
                    $totalAmount += $lineTotal;
                }

                $totalAmount = round($totalAmount, 2);

                // Checked against the price the server just read, never against a
                // total supplied by the browser. Thrown inside the transaction so
                // a short payment rolls back before any stock is deducted.
                if ($tendered < $totalAmount) {
                    throw new ShortPaymentException($totalAmount, $tendered);
                }

                $changeDue = round($tendered - $totalAmount, 2);

                foreach ($lines as [$product, $quantity, $lineTotal]) {
                    $sale = Sale::create([
                        'product_id' => $product->id,
                        'quantity_sold' => $quantity,
                        'unit_price' => $product->price,
                        'total_amount' => $lineTotal,
                        'amount_tendered' => $tendered,
                        'change_due' => $changeDue,
                        'sale_date' => now()->toDateString(),
                        'month_year' => now()->format('Y-m'),
                        'pos_transaction_id' => $transactionId,
                        // The cashier, recorded on the sale itself rather than
                        // only in the stock ledger, so the POS log can name them
                        // without joining through stock_movements.
                        'user_id' => Auth::id(),
                    ]);

                    // Locked, transactional stock deduction; guards against overselling.
                    $updated = (new InventoryService())->deductStock(
                        $product,
                        $quantity,
                        'pos_sale',
                        $sale,
                        Auth::id()
                    );

                    $updatedStock[$updated->id] = $updated->stock;
                }
            });
        } catch (ShortPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Amount received (₱' . number_format($e->tendered, 2) . ') is less than the total (₱'
                    . number_format($e->total, 2) . ').',
                'total_amount' => $e->total,
                'amount_tendered' => $e->tendered,
            ], 422);
        } catch (InsufficientStockException $e) {
            // Thrown inside the transaction closure above; catching it out
            // here (rather than inside the closure) lets Laravel's
            // DB::transaction() auto-rollback everything from this checkout.
            return response()->json([
                'success' => false,
                'message' => "Not enough stock: only {$e->available} available, {$e->requested} requested.",
                'product_id' => $e->productId,
                'available' => $e->available,
                'requested' => $e->requested,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'transaction_id' => $transactionId,
            'total_amount' => $totalAmount,
            'amount_tendered' => $tendered,
            'change_due' => $changeDue,
            'receipt_url' => route('pos.receipt', $transactionId),
            'updated_stock' => $updatedStock,
        ]);
    }

    /**
     * Standalone printable receipt for one checkout (grouped by
     * pos_transaction_id). No app chrome - meant to be opened in a new tab.
     */
    public function receipt($transactionId)
    {
        $sales = Sale::where('pos_transaction_id', $transactionId)
            ->with('product')
            ->orderBy('id')
            ->get();

        // Tendered/change are written identically to every line of the
        // transaction, so the first row carries the transaction-level figures.
        $first = $sales->first();

        return view('pages.pos-receipt', [
            'sales' => $sales,
            'transactionId' => $transactionId,
            'totalAmount' => $sales->sum('total_amount'),
            'amountTendered' => $first?->amount_tendered,
            'changeDue' => $first?->change_due,
        ]);
    }
}
