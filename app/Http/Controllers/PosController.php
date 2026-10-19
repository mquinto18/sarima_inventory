<?php

namespace App\Http\Controllers;

use App\Exceptions\ExpiredProductException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ShortPaymentException;
use App\Models\EditRequest;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\InventoryService;
use App\Services\VatCalculator;
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
        // `category` drives the filter chips above the product grid.
        $products = Product::orderBy('name')->get([
            'id', 'name', 'sku', 'price', 'stock', 'category', 'expiry_date', 'vat_type', 'senior_pwd_eligible',
        ]);

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        $taxMode = Setting::get('tax_registration', 'Non-VAT');
        $vatRate = (float) Setting::get('vat_rate', 12);

        return view('pages.pos', compact(
            'products',
            'reorderCount',
            'reorderNotifications',
            'pendingApprovalCount',
            'notificationCount',
            'taxMode',
            'vatRate'
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

        $columns = ['id', 'name', 'sku', 'price', 'stock', 'category', 'expiry_date', 'vat_type', 'senior_pwd_eligible'];

        $exact = Product::whereRaw('LOWER(sku) = ?', [strtolower($q)])->first($columns);

        if ($exact) {
            return response()->json(['products' => [$exact]]);
        }

        // The cap exists so a one-letter query on a large catalogue cannot
        // return everything on every keystroke. 20 was low enough that a search
        // could hide matching products from the cashier; 200 is generous while
        // still bounded.
        $products = Product::where('name', 'LIKE', "%{$q}%")
            ->orWhere('sku', 'LIKE', "%{$q}%")
            ->orderBy('name')
            ->limit(200)
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
            // All three or none: a discount can't be applied without knowing
            // who it's for and proof of eligibility.
            'discount' => 'nullable|array',
            'discount.type' => 'required_with:discount|in:senior,pwd',
            'discount.id_number' => 'required_with:discount|string|max:100',
            'discount.customer_name' => 'required_with:discount|string|max:255',
        ], [
            'amount_tendered.required' => 'Enter the amount the customer handed over.',
            'discount.type.required_with' => 'Select Senior Citizen or PWD.',
            'discount.id_number.required_with' => 'Enter the ID number.',
            'discount.customer_name.required_with' => 'Enter the customer name.',
        ]);

        $transactionId = (string) Str::uuid();
        $tendered = round((float) $validated['amount_tendered'], 2);
        $discount = $validated['discount'] ?? null;
        $taxMode = Setting::get('tax_registration', 'Non-VAT');
        $vatRate = (float) Setting::get('vat_rate', 12);
        $updatedStock = [];
        $totalAmount = 0;
        $amountDue = 0;
        $changeDue = 0;
        $totals = [];

        try {
            DB::transaction(function () use (
                $validated,
                $transactionId,
                $tendered,
                $discount,
                $taxMode,
                $vatRate,
                &$updatedStock,
                &$totalAmount,
                &$amountDue,
                &$changeDue,
                &$totals
            ) {
                $products = [];
                $quantities = [];
                $calcLines = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $quantity = (int) $item['quantity'];

                    // Checks actual non-expired batch availability rather than
                    // just the product's nearest-expiry date, so a product
                    // isn't unsellable just because its oldest batch expired
                    // while a newer batch still has valid stock.
                    $availableNonExpired = ProductBatch::where('product_id', $product->id)
                        ->available()
                        ->where(function ($q) {
                            $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                        })
                        ->sum('quantity_remaining');

                    if ($availableNonExpired < $quantity) {
                        throw new ExpiredProductException(
                            $product->id,
                            $product->name,
                            optional($product->expiry_date)->format('Y-m-d') ?? 'unknown'
                        );
                    }

                    $products[] = $product;
                    $quantities[] = $quantity;
                    $calcLines[] = [
                        'quantity' => $quantity,
                        'unit_price' => (float) $product->price,
                        'vat_type' => $product->vat_type ?? 'T',
                        'senior_pwd_eligible' => (bool) $product->senior_pwd_eligible,
                    ];
                }

                $calc = (new VatCalculator($taxMode, $vatRate))->calculate($calcLines, (bool) $discount);
                $totals = $calc['totals'];
                $totalAmount = $totals['total_amount'];
                $amountDue = $totals['amount_due'];

                // Checked against the amount the server just computed, never
                // against a total supplied by the browser. Thrown inside the
                // transaction so a short payment rolls back before any stock
                // is deducted. Compared against amount_due (what's actually
                // owed after any Senior/PWD discount), not the gross total.
                if ($tendered < $amountDue) {
                    throw new ShortPaymentException($amountDue, $tendered);
                }

                $changeDue = round($tendered - $amountDue, 2);

                foreach ($products as $i => $product) {
                    $quantity = $quantities[$i];
                    $line = $calc['lines'][$i];

                    $sale = Sale::create([
                        'product_id' => $product->id,
                        'quantity_sold' => $quantity,
                        'unit_price' => $product->price,
                        'total_amount' => $line['total_amount'],
                        'amount_tendered' => $tendered,
                        'change_due' => $changeDue,
                        'sale_date' => now()->toDateString(),
                        'month_year' => now()->format('Y-m'),
                        'pos_transaction_id' => $transactionId,
                        // The cashier, recorded on the sale itself rather than
                        // only in the stock ledger, so the POS log can name them
                        // without joining through stock_movements.
                        'user_id' => Auth::id(),
                        'tax_mode' => $taxMode,
                        'vat_type' => $line['vat_type'],
                        'vatable_sales' => $line['vatable_sales'],
                        'vat_amount' => $line['vat_amount'],
                        'vat_exempt_sales' => $line['vat_exempt_sales'],
                        'zero_rated_sales' => $line['zero_rated_sales'],
                        'discount_type' => $discount['type'] ?? null,
                        'discount_id_number' => $discount['id_number'] ?? null,
                        'discount_customer_name' => $discount['customer_name'] ?? null,
                        'discount_amount' => $line['discount_amount'],
                        'discount_vat_exempt_amount' => $line['discount_vat_exempt_amount'],
                        'amount_due' => $line['amount_due'],
                    ]);

                    // Locked, transactional stock deduction; guards against
                    // overselling. excludeExpired=true so FEFO can never
                    // silently sell from an already-expired batch even if one
                    // happens to be FEFO-earliest - the availability check
                    // above already confirmed enough non-expired stock exists.
                    $updated = (new InventoryService())->deductStock(
                        $product,
                        $quantity,
                        'pos_sale',
                        $sale,
                        Auth::id(),
                        null,
                        true
                    );

                    $updatedStock[$updated->id] = $updated->stock;
                }
            });
        } catch (ShortPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Amount received (₱' . number_format($e->tendered, 2) . ') is less than the amount due (₱'
                    . number_format($e->total, 2) . ').',
                'total_amount' => $e->total,
                'amount_tendered' => $e->tendered,
            ], 422);
        } catch (ExpiredProductException $e) {
            // Thrown inside the transaction closure above; catching it out
            // here lets Laravel's DB::transaction() auto-rollback the whole
            // checkout instead of only skipping this one line.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'product_id' => $e->productId,
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
            'amount_due' => $amountDue,
            'amount_tendered' => $tendered,
            'change_due' => $changeDue,
            'discount_amount' => $totals['discount_amount'] ?? 0,
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

        // Tendered/change/tax mode/discount identity are written identically
        // to every line of the transaction, so the first row carries those
        // transaction-level figures; vatable_sales and friends are per-line
        // and summed below, same as total_amount already was.
        $first = $sales->first();

        return view('pages.pos-receipt', [
            'sales' => $sales,
            'transactionId' => $transactionId,
            'totalAmount' => $sales->sum('total_amount'),
            'amountTendered' => $first?->amount_tendered,
            'changeDue' => $first?->change_due,
            'taxMode' => $first?->tax_mode ?? Setting::get('tax_registration', 'Non-VAT'),
            'vatRateLabel' => rtrim(rtrim(number_format((float) Setting::get('vat_rate', 12), 1), '0'), '.'),
            'vatableSales' => $sales->sum('vatable_sales'),
            'vatAmount' => $sales->sum('vat_amount'),
            'vatExemptSales' => $sales->sum('vat_exempt_sales'),
            'zeroRatedSales' => $sales->sum('zero_rated_sales'),
            'amountDue' => $sales->sum('amount_due'),
            'discountType' => $first?->discount_type,
            'discountIdNumber' => $first?->discount_id_number,
            'discountCustomerName' => $first?->discount_customer_name,
            'discountAmount' => $sales->sum('discount_amount'),
            'discountVatExemptAmount' => $sales->sum('discount_vat_exempt_amount'),
        ]);
    }
}
