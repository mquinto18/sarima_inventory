<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [ProductController::class, 'dashboard']);
    Route::get('/forecasting', [\App\Http\Controllers\SalesController::class, 'index'])->name('forecasting');
    Route::post('/sales', [\App\Http\Controllers\SalesController::class, 'store']);
    Route::get('/inventory', [ProductController::class, 'index']);

    Route::get('/analytics', function () {
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();
        return view('pages.analytics', compact('reorderCount', 'reorderNotifications'));
    });

    Route::get('/settings', function () {
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();
        return view('pages.settings', compact('reorderCount', 'reorderNotifications'));
    });

    Route::get('products/search', [ProductController::class, 'search']);
    Route::post('products/{id}/approve-reorder', [ProductController::class, 'approveReorder']);
});

// Account Management - Admin Only
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/account-management', [UserController::class, 'index']);
    Route::post('/account-management/users', [UserController::class, 'store']);
    Route::get('/account-management/users/{id}/edit', [UserController::class, 'edit']);
    Route::put('/account-management/users/{id}', [UserController::class, 'update']);
    Route::delete('/account-management/users/{id}', [UserController::class, 'destroy']);
});

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
