<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EditRequestController;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [ProductController::class, 'dashboard']);
    Route::get('/forecasting', [\App\Http\Controllers\SalesController::class, 'index'])->name('forecasting');
    // Manual sale recording removed: sales are captured through Point of Sale,
    // which is the single path that deducts stock and issues a receipt.
    Route::get('/inventory', [ProductController::class, 'index']);

    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    Route::get('products/search', [ProductController::class, 'search']);
    Route::post('/products/{id}/dispose-expired', [ProductController::class, 'disposeExpired'])->name('products.dispose-expired');
    // Reorder approval removed: stock must arrive through a supplier purchase
    // order (Suppliers -> Purchase Orders -> Receive Delivery), not by adding
    // stock directly from a recommendation.
    Route::post('/edit-requests', [EditRequestController::class, 'store'])->name('edit-requests.store');

    // Combined Forecasting + Analytics PDF, previewed in an in-page drawer.
    // The drawer fetches both pages' chart datasets, rasterises them, and posts
    // them back to be embedded in the PDF it then previews.
    Route::get('/reports/datasets', [\App\Http\Controllers\ReportController::class, 'datasets'])->name('reports.datasets');
    Route::post('/reports/full', [\App\Http\Controllers\ReportController::class, 'generate'])->name('reports.generate');

    // Point of Sale
    Route::get('/pos', [\App\Http\Controllers\PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/lookup', [\App\Http\Controllers\PosController::class, 'lookup'])->name('pos.lookup');
    Route::post('/pos/checkout', [\App\Http\Controllers\PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{transactionId}', [\App\Http\Controllers\PosController::class, 'receipt'])->name('pos.receipt');

    // Suppliers
    Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');
    // Registered before the /suppliers/{supplier} routes below: those single-
    // segment patterns would otherwise swallow /suppliers/link-product and
    // /suppliers/product-links/{id}, treating "link-product"/"product-links"
    // as the supplier identifier.
    Route::post('/suppliers/link-product', [\App\Http\Controllers\SupplierController::class, 'linkProduct'])->name('suppliers.link-product');
    Route::post('/suppliers/link-products', [\App\Http\Controllers\SupplierController::class, 'linkProducts'])->name('suppliers.link-products');
    Route::delete('/suppliers/product-links/{productSupplier}', [\App\Http\Controllers\SupplierController::class, 'unlinkProduct'])->name('suppliers.unlink-product');
    Route::put('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Purchase Orders
    Route::get('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::post('/purchase-orders/{purchaseOrder}/send', [\App\Http\Controllers\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [\App\Http\Controllers\PurchaseOrderController::class, 'receiveDelivery'])->name('purchase-orders.receive');
    // Manual overrides: let an admin record the supplier's response directly
    // when the email confirm link is unreachable or the supplier responded
    // some other way (phone, in person), instead of only via that link.
    Route::post('/purchase-orders/{purchaseOrder}/mark-confirmed', [\App\Http\Controllers\PurchaseOrderController::class, 'markConfirmed'])->name('purchase-orders.mark-confirmed');
    Route::post('/purchase-orders/{purchaseOrder}/mark-cancelled', [\App\Http\Controllers\PurchaseOrderController::class, 'markCancelled'])->name('purchase-orders.mark-cancelled');

    // Transaction logs (read-only). Role enforced in the controller via
    // denyStaff(), matching Suppliers / Purchase Orders / Settings.
    Route::get('/logs/pos', [\App\Http\Controllers\TransactionLogController::class, 'pos'])->name('logs.pos');
    Route::get('/logs/deliveries', [\App\Http\Controllers\TransactionLogController::class, 'deliveries'])->name('logs.deliveries');
});

// Account Management - Admin Only
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/account-management', [UserController::class, 'index']);
    Route::post('/account-management/users', [UserController::class, 'store']);
    Route::get('/account-management/users/{id}/edit', [UserController::class, 'edit']);
    Route::put('/account-management/users/{id}', [UserController::class, 'update']);
    // Archives the account rather than deleting it; restored from Settings.
    Route::delete('/account-management/users/{id}', [UserController::class, 'destroy']);
    Route::post('/settings/archived-users/{id}/restore', [UserController::class, 'restore'])
        ->name('users.restore');
    Route::get('/new-approval-requests', [EditRequestController::class, 'index'])->name('edit-requests.index');
    Route::post('/approval-requests/{id}/approve', [EditRequestController::class, 'approve'])->name('edit-requests.approve');
    Route::post('/approval-requests/{id}/reject', [EditRequestController::class, 'reject'])->name('edit-requests.reject');
});

// Postmark inbound webhook: a supplier's reply to a purchase order email
// lands here and, once matched to a PO and sender-verified, confirms it.
// Public (no auth) - protected instead by the shared token query param and
// exempted from CSRF in bootstrap/app.php.
Route::post('/webhooks/postmark/inbound', [\App\Http\Controllers\Webhooks\PostmarkInboundController::class, 'handle'])
    ->name('webhooks.postmark.inbound');

// Supplier-facing Approve/Decline landing page, reached from the PO email.
// Public (no auth) - identified by the per-PO confirmation_token instead of
// a login. The GET page only ever renders (safe for email "Safe Links"
// scanners to prefetch); the POST actions are the ones that actually change
// status, and use normal form-submitted CSRF tokens so no exemption is
// needed in bootstrap/app.php.
Route::get('/po-confirm/{token}', [\App\Http\Controllers\PurchaseOrderConfirmationController::class, 'show'])
    ->name('purchase-orders.confirm.show');
Route::post('/po-confirm/{token}/approve', [\App\Http\Controllers\PurchaseOrderConfirmationController::class, 'approve'])
    ->name('purchase-orders.confirm.approve');
Route::post('/po-confirm/{token}/decline', [\App\Http\Controllers\PurchaseOrderConfirmationController::class, 'decline'])
    ->name('purchase-orders.confirm.decline');

// Test SARIMA functionality
Route::get('test/sarima', function () {
    $salesController = new \App\Http\Controllers\SalesController();

    // Get the same data that the forecasting page uses
    $monthlySales = $salesController->getMonthlySalesData();
    $salesStats = $salesController->getSalesStatistics();
    $topProducts = $salesController->getTopSellingProducts();

    // Generate SARIMA forecasts
    $forecast = $salesController->generateSarimaForecast($monthlySales);
    $demandForecast = $salesController->generateDemandForecast($monthlySales);

    return response()->json([
        'sarima_status' => 'functional',
        'monthly_sales_data' => $monthlySales->toArray(),
        'current_month_revenue' => $salesStats['current_month_revenue'],
        'current_month_sales' => $salesStats['total_sales_count'],
        'top_products' => $topProducts->toArray(),
        'revenue_forecast' => $forecast,
        'demand_forecast' => $demandForecast,
        'forecast_months' => count($forecast['predicted'] ?? []),
        'message' => 'SARIMA forecasting is working!'
    ]);
});

// SARIMA-enhanced inventory management routes
Route::get('api/sarima-analysis', [ProductController::class, 'getSarimaAnalysis']);
Route::post('api/auto-update-reorder-levels', [ProductController::class, 'autoUpdateReorderLevels']);
Route::get('api/inventory-insights', [\App\Http\Controllers\SalesController::class, 'getInventoryInsights']);

// Debug routes
Route::get('debug/sales-stats', function () {
    $salesController = new \App\Http\Controllers\SalesController();

    // Get current month data
    $thisMonth = \Carbon\Carbon::now()->format('Y-m');
    $allSales = \App\Models\Sale::all();
    $thisMonthSales = \App\Models\Sale::where('sale_month', $thisMonth)->get();

    return response()->json([
        'current_month' => $thisMonth,
        'all_sales_count' => $allSales->count(),
        'this_month_sales_count' => $thisMonthSales->count(),
        'this_month_sales' => $thisMonthSales->toArray(),
        'this_month_revenue' => $thisMonthSales->sum('total_amount'),
        'sales_stats' => $salesController->getSalesStatistics(),
        'top_products' => $salesController->getTopSellingProducts()
    ]);
});

// Database connection test
Route::get('test/db', function () {
    try {
        $productsCount = \App\Models\Product::count();
        $salesCount = \App\Models\Sale::count();

        return response()->json([
            'database_connected' => true,
            'products_count' => $productsCount,
            'sales_count' => $salesCount,
            'current_time' => now(),
            'message' => 'Database connection working!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'database_connected' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Quick test route to verify sales
Route::get('test/sales', function () {
    $sales = \App\Models\Sale::with('product')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    return response()->json([
        'total_sales_count' => \App\Models\Sale::count(),
        'recent_sales' => $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'product_name' => $sale->product->name ?? 'Unknown',
                'quantity_sold' => $sale->quantity_sold,
                'total_amount' => $sale->total_amount,
                'sale_date' => $sale->sale_date,
                'sale_month' => $sale->sale_month,
                'created_at' => $sale->created_at
            ];
        }),
        'current_month_total' => \App\Models\Sale::where('sale_month', \Carbon\Carbon::now()->format('Y-m'))->sum('total_amount')
    ]);
});

Route::resource('products', ProductController::class);
