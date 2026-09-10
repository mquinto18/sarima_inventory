<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Get reorder recommendations count for notifications.
     */
    public static function getReorderCount()
    {
        $products = Product::all();
        $count = 0;
        
        foreach ($products as $product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            if ($product->stock <= $dynamicReorderPoint) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get reorder notifications for dropdown.
     */
    public static function getReorderNotifications()
    {
        $products = Product::all();
        return $products->filter(function ($product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            return $product->stock <= $dynamicReorderPoint;
        })->map(function ($product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            $forecastedDemand = self::getForecastedDemand($product->id);
            $recommendedQuantity = max($forecastedDemand, $dynamicReorderPoint);
            $priority = $product->stock <= Setting::get('critical_stock_level', 5) ? 'High' : ($product->stock <= Setting::get('low_stock_threshold', 10) ? 'Medium' : 'Low');
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'current_stock' => $product->stock,
                'reorder_level' => $dynamicReorderPoint, // For backward compatibility
                'dynamic_reorder_level' => $dynamicReorderPoint,
                'static_reorder_level' => $product->reorder_level,
                'recommended_quantity' => $recommendedQuantity,
                'forecasted_demand' => $forecastedDemand,
                'priority' => $priority,
                'algorithm' => 'SARIMA-Enhanced'
            ];
        })->sortByDesc(function ($item) {
            return $item['priority'] === 'High' ? 3 : ($item['priority'] === 'Medium' ? 2 : 1);
        });
    }

    /**
     * Calculate dynamic reorder point based on SARIMA forecast
     */
    public static function calculateDynamicReorderPoint($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 10; // Default fallback
        }

        // Get historical demand data for the product
        $demandData = self::getProductDemandHistory($productId);
        
        if (count($demandData) < 3) {
            // Not enough data, use static reorder level
            return $product->reorder_level ?? 10;
        }

        // Calculate average monthly demand
        $averageMonthlyDemand = array_sum($demandData) / count($demandData);
        
        // Calculate demand variance for safety stock
        $variance = self::calculateVariance($demandData);
        $standardDeviation = sqrt($variance);
        
        // Assume 1 month lead time and 95% service level (1.65 z-score)
        $leadTime = 1; // months
        $serviceLevel = 1.65; // 95% service level
        
        $safetyStock = $serviceLevel * $standardDeviation * sqrt($leadTime);
        $dynamicReorderPoint = ($averageMonthlyDemand * $leadTime) + $safetyStock;

        // Ensure minimum reorder point. Rounded up to a whole unit here at the
        // source: stock is only ever counted in whole units, and every caller
        // either displays this or compares it against an integer stock level.
        // ceil() rather than round() so a fractional safety margin is never
        // discarded — under-ordering is the costlier mistake.
        return (int) ceil(max($dynamicReorderPoint, $product->reorder_level ?? 5));
    }

    /**
     * Get product demand history for SARIMA analysis
     */
    private static function getProductDemandHistory($productId, $months = 6)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        $monthlySales = Sale::select(
            DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as month'),
            DB::raw('SUM(quantity_sold) as total_demand')
        )
        ->where('product_id', $productId)
        ->where('sale_date', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total_demand')
        ->toArray();
        
        return array_map('floatval', $monthlySales);
    }

    /**
     * Calculate variance of demand data
     */
    private static function calculateVariance($data)
    {
        $n = count($data);
        if ($n <= 1) return 0;
        
        $mean = array_sum($data) / $n;
        $sumSquares = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data));
        
        return $sumSquares / ($n - 1);
    }

    /**
     * Get forecasted demand for next month using simplified SARIMA
     */
    public static function getForecastedDemand($productId)
    {
        $demandData = self::getProductDemandHistory($productId, 6);
        
        if (count($demandData) < 3) {
            // Not enough data, return average of existing data or default
            return count($demandData) > 0
                ? (int) ceil(array_sum($demandData) / count($demandData))
                : 20;
        }

        // Simple trend calculation
        $trend = self::calculateSimpleTrend($demandData);
        
        // Simple seasonality (using 3-month cycle)
        $seasonal = self::calculateSimpleSeasonality($demandData, 3);
        
        $lastValue = end($demandData);
        $seasonalIndex = (count($demandData)) % 3;
        $seasonalComponent = isset($seasonal[$seasonalIndex]) ? $seasonal[$seasonalIndex] : 0;
        
        $forecast = max(0, $lastValue + $trend + $seasonalComponent);

        // round() returns a float in PHP; cast so callers get a real integer.
        return (int) round($forecast);
    }

    /**
     * Calculate simple trend for demand forecasting
     */
    private static function calculateSimpleTrend($data)
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = (float)$data[$i];
            $sumX += $x; $sumY += $y; $sumXY += $x * $y; $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        return $denominator == 0 ? 0 : ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    /**
     * Calculate simple seasonality for demand forecasting
     */
    private static function calculateSimpleSeasonality($data, $period)
    {
        $n = count($data);
        if ($n == 0) return array_fill(0, $period, 0);

        $seasonal = array_fill(0, $period, 0);
        $counts = array_fill(0, $period, 0);
        $overallMean = array_sum($data) / $n;

        for ($i = 0; $i < $n; $i++) {
            $seasonIndex = $i % $period;
            $seasonal[$seasonIndex] += (float)$data[$i];
            $counts[$seasonIndex]++;
        }

        for ($i = 0; $i < $period; $i++) {
            if ($counts[$i] > 0) {
                $seasonal[$i] = ($seasonal[$i] / $counts[$i]) - $overallMean;
            }
        }

        return $seasonal;
    }

    /**
     * Calculate forecast accuracy based on SARIMA predictions vs actual sales.
     *
     * Evaluated the same way for every metric: for each product, the last
     * month of history is held out as "actual" and forecast from the
     * preceding months (single-holdout, not a rolling backtest).
     *  - MAE  = average |actual - forecast|, in units
     *  - RMSE = sqrt(average (actual - forecast)^2), in units — penalizes
     *           large misses harder than MAE
     *  - MAPE = average |actual - forecast| / actual, as a % (existing metric,
     *           unchanged; still what "Forecast Accuracy %" is derived from)
     *  - MASE = MAE scaled against an in-sample naive (previous-period)
     *           forecast's error; < 1 means the model beats a naive
     *           forecast, > 1 means it's worse. Skipped per-product when the
     *           naive baseline has zero error (flat training data), since
     *           that ratio is undefined.
     */
    public static function calculateForecastAccuracy()
    {
        $products = Product::all();
        $totalMape = 0;
        $totalAbsoluteError = 0;
        $totalSquaredError = 0;
        $totalMase = 0;
        $validProducts = 0;
        $validMaseProducts = 0;

        foreach ($products as $product) {
            // Get historical data (last 6 months)
            $historicalData = self::getProductDemandHistory($product->id, 6);

            // Need at least 4 data points to compare (3 for training, 1 for testing)
            if (count($historicalData) >= 4) {
                // Use last value as "actual" and previous values to forecast
                $actualDemand = array_pop($historicalData); // Take last month as actual

                if ($actualDemand > 0) {
                    // Calculate forecast using remaining historical data
                    $trend = self::calculateSimpleTrend($historicalData);
                    $seasonal = self::calculateSimpleSeasonality($historicalData, 3);
                    $lastValue = end($historicalData);
                    $seasonalIndex = count($historicalData) % 3;
                    $seasonalComponent = isset($seasonal[$seasonalIndex]) ? $seasonal[$seasonalIndex] : 0;
                    $forecastedDemand = max(0, $lastValue + $trend + $seasonalComponent);

                    // Note: forecastedDemand can legitimately be 0 (a product
                    // the model predicted no demand for) - that's still a
                    // real, countable error against actualDemand > 0, so it
                    // must not be excluded from the metrics below. Neither
                    // MAPE (divides by actualDemand) nor MAE/RMSE has a
                    // division-by-zero risk from forecastedDemand being 0.
                    $absoluteError = abs($actualDemand - $forecastedDemand);

                    // MAPE (existing)
                    $productMape = ($absoluteError / $actualDemand) * 100;
                    $totalMape += min($productMape, 100); // Cap at 100% error

                    // MAE / RMSE accumulators
                    $totalAbsoluteError += $absoluteError;
                    $totalSquaredError += pow($actualDemand - $forecastedDemand, 2);
                    $validProducts++;

                    // MASE: scale this product's error against its own
                    // in-sample naive (previous-month) forecast error.
                    $naiveDiffs = [];
                    for ($i = 1; $i < count($historicalData); $i++) {
                        $naiveDiffs[] = abs($historicalData[$i] - $historicalData[$i - 1]);
                    }
                    $naiveMae = count($naiveDiffs) > 0 ? array_sum($naiveDiffs) / count($naiveDiffs) : 0;

                    if ($naiveMae > 0) {
                        $totalMase += $absoluteError / $naiveMae;
                        $validMaseProducts++;
                    }
                }
            }
        }

        // Calculate average MAPE across all products
        // If no products with enough data, show a default good accuracy
        if ($validProducts > 0) {
            $avgMape = $totalMape / $validProducts;
        } else {
            // No data available yet - show pending status
            return [
                'mape' => 0,
                'mae' => 0,
                'rmse' => 0,
                'mase' => null,
                'accuracy_percentage' => 0,
                'status' => 'Pending Data',
                'products_analyzed' => 0
            ];
        }

        $accuracyPercentage = round(max(0, 100 - $avgMape), 1);

        // Determine status based on accuracy
        $status = 'Excellent';
        if ($accuracyPercentage < 95) {
            $status = 'Good';
        }
        if ($accuracyPercentage < 85) {
            $status = 'Fair';
        }
        if ($accuracyPercentage < 75) {
            $status = 'Needs Improvement';
        }

        return [
            'mape' => round($avgMape, 2),
            'mae' => round($totalAbsoluteError / $validProducts, 2),
            'rmse' => round(sqrt($totalSquaredError / $validProducts), 2),
            'mase' => $validMaseProducts > 0 ? round($totalMase / $validMaseProducts, 2) : null,
            'accuracy_percentage' => $accuracyPercentage,
            'status' => $status,
            'products_analyzed' => $validProducts
        ];
    }

        /**
     * Display the dashboard with SARIMA-enhanced statistics.
     */
    public function dashboard()
    {
        $products = Product::all();
        $totalProducts = $products->count();
        
        // SARIMA-enhanced metrics
        $lowStockCount = 0;
        $criticalStockCount = 0;
        $dynamicReorderCount = 0;
        
        foreach ($products as $product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            
            if ($product->stock <= Setting::get('critical_stock_level', 5)) {
                $criticalStockCount++;
            } elseif ($product->stock <= Setting::get('low_stock_threshold', 10)) {
                $lowStockCount++;
            }
            
            if ($product->stock <= $dynamicReorderPoint) {
                $dynamicReorderCount++;
            }
        }
        
        $totalValue = $products->sum(function ($product) {
            return $product->price * $product->stock;
        });
        
        $reorderNotifications = self::getReorderNotifications();
        $reorderCount = self::getReorderCount();

        // Get forecast accuracy
        $forecastAccuracy = self::calculateForecastAccuracy();

        // Get monthly revenue
        $monthlyRevenue = self::calculateMonthlyRevenue();

        // Get sales trend data for chart (last 6 months)
        $salesTrend = self::getSalesTrendData();

        // Add SARIMA insights summary
        $sarimaInsights = [
            'dynamic_reorders' => $dynamicReorderCount,
            'static_reorders' => $products->filter(function ($product) {
                return $product->stock <= $product->reorder_level;
            })->count(),
            'high_risk_products' => $products->filter(function ($product) {
                $forecastedDemand = self::getForecastedDemand($product->id);
                return $product->stock > 0 && ($forecastedDemand / $product->stock) >= 1.5;
            })->count()
        ];

        // Products expiring soon (or already expired)
        $expiringProducts = self::getExpiringProducts();
        $expiringCount = $expiringProducts->count();

        // Fast/slow-moving teaser widgets (full lists live on the Analytics page)
        $fastMovingProducts = AnalyticsController::getFastMovingProducts(3);
        $slowMovingProducts = AnalyticsController::getSlowMovingProducts(3);

        // Upcoming supplier deliveries
        $upcomingDeliveries = PurchaseOrder::upcoming()->with('supplier')->get();

        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;
        return view('dashboard', compact(
            'totalProducts',
            'lowStockCount',
            'criticalStockCount',
            'dynamicReorderCount',
            'totalValue',
            'reorderNotifications',
            'reorderCount',
            'sarimaInsights',
            'forecastAccuracy',
            'monthlyRevenue',
            'salesTrend',
            'expiringProducts',
            'expiringCount',
            'fastMovingProducts',
            'slowMovingProducts',
            'upcomingDeliveries',
            'pendingApprovalCount',
            'notificationCount'
        ));
    }

    /**
     * Products whose expiry_date is already past, or falls within the
     * configured alert window (Settings -> expiry_alert_days).
     */
    public static function getExpiringProducts()
    {
        $days = (int) Setting::get('expiry_alert_days', 30);

        return Product::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date')
            ->get(['id', 'name', 'stock', 'expiry_date']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        
        // Update product status based on stock levels
        foreach ($products as $product) {
            $newStatus = Product::statusForStock($product->stock);

            // Only update if status has changed to avoid unnecessary queries
            if ($product->status !== $newStatus) {
                $product->update(['status' => $newStatus]);
            }
        }
        
        // Refresh products after potential updates
        $products = Product::all();
        
        // Calculate statistics based on stock levels. These bands must match
        // Product::statusForStock() exactly, or the summary cards will disagree
        // with the status badges in the table below them.
        $criticalLevel = (int) Setting::get('critical_stock_level', 5);
        $lowThreshold = (int) Setting::get('low_stock_threshold', 10);

        $totalProducts = $products->count();
        $criticalStockCount = $products->where('stock', '<=', $criticalLevel)->count();
        $lowStockCount = $products->where('stock', '<=', $lowThreshold)
            ->where('stock', '>', $criticalLevel)
            ->count();
        $totalValue = $products->sum(function ($product) {
            return $product->price * $product->stock;
        });
        
        // Generate SARIMA-enhanced reorder recommendations
        $reorderRecommendations = $products->filter(function ($product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            return $product->stock <= $dynamicReorderPoint;
        })->map(function ($product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            $forecastedDemand = self::getForecastedDemand($product->id);
            $recommendedQuantity = max($forecastedDemand * 2, $dynamicReorderPoint);
            $priority = $product->stock <= Setting::get('critical_stock_level', 5) ? 'High' : ($product->stock <= Setting::get('low_stock_threshold', 10) ? 'Medium' : 'Low');
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'current_stock' => $product->stock,
                'static_reorder_level' => $product->reorder_level,
                'dynamic_reorder_level' => round($dynamicReorderPoint),
                'forecasted_demand' => $forecastedDemand,
                'recommended_quantity' => $recommendedQuantity,
                'priority' => $priority,
                'estimated_cost' => $product->price * $recommendedQuantity,
                'algorithm' => 'SARIMA-Enhanced'
            ];
        })->sortByDesc(function ($item) {
            // Sort by priority: High=3, Medium=2, Low=1
            return $item['priority'] === 'High' ? 3 : ($item['priority'] === 'Medium' ? 2 : 1);
        });
        
        $reorderCount = $reorderRecommendations->count();
        $reorderNotifications = self::getReorderNotifications();
        
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;
        return view('pages.inventory', compact('products', 'totalProducts', 'lowStockCount', 'criticalStockCount', 'totalValue', 'reorderRecommendations', 'reorderCount', 'reorderNotifications', 'pendingApprovalCount', 'notificationCount', 'criticalLevel', 'lowThreshold'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:64|unique:products,sku',
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'price' => 'nullable|numeric',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        // Auto-calculate status based on stock level
        $validated['status'] = Product::statusForStock($validated['stock']);

        $product = Product::create($validated);

        // For AJAX: return JSON
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'product' => $product]);
        }

        // For normal form submit
        return redirect()->back()->with('success', 'Product added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:64|unique:products,sku,' . $product->id,
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'price' => 'nullable|numeric',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        // Stock changes go through InventoryService so every manual edit still
        // gets a stock_movements ledger row; other fields update normally.
        // Both writes happen in one transaction and share the same
        // catch, so a concurrent sale that pushes the requested stock delta
        // negative rolls back the whole edit instead of leaving name/price/
        // status committed against a stock value that was never applied.
        $stockDelta = $validated['stock'] - $product->stock;
        unset($validated['stock']);
        $validated['status'] = Product::statusForStock($request->input('stock'));

        try {
            $product = DB::transaction(function () use ($product, $validated, $stockDelta) {
                $product->update($validated);

                if ($stockDelta !== 0) {
                    $product = (new InventoryService())->adjustStock($product, $stockDelta, 'adjustment', null, Auth::id(), 'Manual edit via product form');
                }

                return $product;
            });
        } catch (InsufficientStockException $e) {
            $message = "Cannot reduce stock below zero: only {$e->available} available, tried to remove {$e->requested}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        // If staff edits a product, mark their latest approved edit request as completed
        if (Auth::user()->role === 'staff') {
            $latestEditRequest = \App\Models\EditRequest::where('user_id', Auth::id())
                ->where('status', 'approved')
                ->where('completed', false)
                ->latest()
                ->first();
            if ($latestEditRequest) {
                $latestEditRequest->completed = true;
                $latestEditRequest->save();
            }
        }

        // For AJAX: return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true, 
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        }

        // For normal form submit
        return redirect()->back()->with('success', 'Product updated successfully!');
    }

    /**
     * Search products based on query.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->get('search');
        
        if (empty($searchTerm)) {
            $products = Product::all();
        } else {
            $products = Product::where('name', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('category', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('status', 'LIKE', "%{$searchTerm}%")
                             ->get();
        }
        
        return response()->json(['products' => $products]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            
            // For AJAX: return JSON
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Product deleted successfully'
                ]);
            }
            
            // For normal form submit
            return redirect()->back()->with('success', 'Product deleted successfully!');
            
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Error deleting product: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error deleting product');
        }
    }

    /**
     * Get comprehensive SARIMA-based inventory analysis
     */
    public function getSarimaAnalysis()
    {
        $salesController = new \App\Http\Controllers\SalesController();
        $inventoryInsights = $salesController->getInventoryInsights();
        
        $analysis = [
            'total_products' => count($inventoryInsights),
            'products_needing_reorder' => collect($inventoryInsights)->where('current_stock', '<=', function ($item) {
                return $item['dynamic_reorder_level'];
            })->count(),
            'high_risk_products' => collect($inventoryInsights)->where('risk_level', 'HIGH')->count(),
            'medium_risk_products' => collect($inventoryInsights)->where('risk_level', 'MEDIUM')->count(),
            'insights' => $inventoryInsights,
            'recommendations' => $this->generateSarimaRecommendations($inventoryInsights)
        ];
        
        return response()->json($analysis);
    }

    /**
     * Generate SARIMA-based recommendations
     */
    private function generateSarimaRecommendations($insights)
    {
        $recommendations = [];
        
        foreach ($insights as $insight) {
            if ($insight['risk_level'] === 'HIGH') {
                $recommendations[] = [
                    'type' => 'urgent',
                    'product' => $insight['product_name'],
                    'message' => "Immediate attention needed: High demand forecast ({$insight['forecasted_demand']} units) vs current stock ({$insight['current_stock']} units)",
                    'suggested_order_quantity' => max($insight['forecasted_demand'] * 2, 50)
                ];
            } elseif ($insight['current_stock'] <= $insight['dynamic_reorder_level']) {
                $recommendations[] = [
                    'type' => 'reorder',
                    'product' => $insight['product_name'],
                    'message' => "Stock below SARIMA-calculated reorder point: {$insight['current_stock']} ≤ {$insight['dynamic_reorder_level']}",
                    'suggested_order_quantity' => $insight['forecasted_demand'] * 1.5
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Auto-update reorder levels based on SARIMA analysis
     */
    public function autoUpdateReorderLevels()
    {
        $products = Product::all();
        $updated = 0;
        
        foreach ($products as $product) {
            $dynamicReorderPoint = self::calculateDynamicReorderPoint($product->id);
            
            // Only update if the dynamic calculation suggests a significant change
            if (abs($dynamicReorderPoint - $product->reorder_level) > 2) {
                $product->update(['reorder_level' => round($dynamicReorderPoint)]);
                $updated++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Updated reorder levels for {$updated} products based on SARIMA analysis",
            'updated_count' => $updated
        ]);
    }

    /**
     * Calculate monthly revenue with comparison to previous month
     */
    public static function calculateMonthlyRevenue()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');
        
        $currentRevenue = Sale::whereYear('sale_date', substr($currentMonth, 0, 4))
            ->whereMonth('sale_date', substr($currentMonth, 5, 2))
            ->sum('total_amount');
        $lastRevenue = Sale::whereYear('sale_date', substr($lastMonth, 0, 4))
            ->whereMonth('sale_date', substr($lastMonth, 5, 2))
            ->sum('total_amount');
        
        $change = $currentRevenue - $lastRevenue;
        $changePercentage = $lastRevenue > 0 ? ($change / $lastRevenue) * 100 : 0;
        $changeDirection = $change >= 0 ? 'increase' : 'decrease';
        
        return [
            'current' => $currentRevenue,
            'previous' => $lastRevenue,
            'change' => abs($change),
            'change_percentage' => abs($changePercentage),
            'change_direction' => $changeDirection
        ];
    }

    /**
     * Get sales trend data for the last 6 months with forecast
     */
    public static function getSalesTrendData()
    {
        $months = [];
        $actualSales = [];
        $forecastedSales = [];
        
        // Get last 6 months of actual sales
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $monthLabel = Carbon::now()->subMonths($i)->format('M Y');
            
            $revenue = Sale::whereYear('sale_date', substr($month, 0, 4))
                ->whereMonth('sale_date', substr($month, 5, 2))
                ->sum('total_amount');
            
            $months[] = $monthLabel;
            $actualSales[] = $revenue;
        }
        
        // Calculate simple forecast for next 3 months based on trend
        $validSales = array_filter($actualSales, function($val) { return $val > 0; });
        
        if (count($validSales) >= 2) {
            // Calculate average growth rate
            $recentSales = array_slice($actualSales, -3); // Last 3 months
            $avgRecent = array_sum($recentSales) / count($recentSales);
            
            // Simple linear trend
            $trend = 0;
            if (count($validSales) >= 3) {
                $first = array_slice($validSales, 0, 2);
                $last = array_slice($validSales, -2);
                $avgFirst = array_sum($first) / count($first);
                $avgLast = array_sum($last) / count($last);
                $trend = ($avgLast - $avgFirst) / 2; // Monthly trend
            }
            
            // Generate forecast for next 6 months (to match SARIMA)
            $lastActual = end($actualSales);
            for ($i = 1; $i <= 6; $i++) {
                $monthLabel = Carbon::now()->addMonths($i)->format('M Y');
                $forecast = max(0, $lastActual + ($trend * $i));
                
                $months[] = $monthLabel;
                $actualSales[] = null; // No actual data for future
                $forecastedSales[] = round($forecast, 2);
            }
        } else {
            // Not enough data for forecast
            for ($i = 1; $i <= 6; $i++) {
                $monthLabel = Carbon::now()->addMonths($i)->format('M Y');
                $months[] = $monthLabel;
                $actualSales[] = null;
                $forecastedSales[] = null;
            }
        }
        
        // Fill forecast array for historical months
        $forecastFilled = array_fill(0, 6, null);
        $forecastFilled = array_merge($forecastFilled, $forecastedSales);
        
        return [
            'months' => $months,
            'actual' => $actualSales,
            'forecast' => $forecastFilled
        ];
    }
}
