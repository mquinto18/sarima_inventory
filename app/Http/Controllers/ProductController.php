<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            $priority = $product->stock <= 5 ? 'High' : ($product->stock <= 10 ? 'Medium' : 'Low');
            
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
        
        // Ensure minimum reorder point
        return max($dynamicReorderPoint, $product->reorder_level ?? 5);
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
            return count($demandData) > 0 ? array_sum($demandData) / count($demandData) : 20;
        }

        // Simple trend calculation
        $trend = self::calculateSimpleTrend($demandData);
        
        // Simple seasonality (using 3-month cycle)
        $seasonal = self::calculateSimpleSeasonality($demandData, 3);
        
        $lastValue = end($demandData);
        $seasonalIndex = (count($demandData)) % 3;
        $seasonalComponent = isset($seasonal[$seasonalIndex]) ? $seasonal[$seasonalIndex] : 0;
        
        $forecast = max(0, $lastValue + $trend + $seasonalComponent);
        
        return round($forecast);
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
     * Calculate forecast accuracy based on SARIMA predictions vs actual sales
     */
    public static function calculateForecastAccuracy()
    {
        $products = Product::all();
        $totalMape = 0;
        $validProducts = 0;
        
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
                    
                    // Calculate MAPE for this product
                    if ($forecastedDemand > 0) {
                        $productMape = abs(($actualDemand - $forecastedDemand) / $actualDemand) * 100;
                        $totalMape += min($productMape, 100); // Cap at 100% error
                        $validProducts++;
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
            
            if ($product->stock <= 5) {
                $criticalStockCount++;
            } elseif ($product->stock <= 10) {
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
            'salesTrend'
        ));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        
        // Update product status based on stock levels
        foreach ($products as $product) {
            $newStatus = '';
            if ($product->stock <= 5) {
                $newStatus = 'Critical';
            } elseif ($product->stock <= 10) {
                $newStatus = 'Low Stock';
            } else {
                $newStatus = 'In Stock';
            }
            
            // Only update if status has changed to avoid unnecessary queries
            if ($product->status !== $newStatus) {
                $product->update(['status' => $newStatus]);
            }
        }
        
        // Refresh products after potential updates
        $products = Product::all();
        
        // Calculate statistics based on stock levels
        $totalProducts = $products->count();
        $lowStockCount = $products->where('stock', '<=', 10)->where('stock', '>', 5)->count();
        $criticalStockCount = $products->where('stock', '<=', 5)->count();
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
            $priority = $product->stock <= 5 ? 'High' : ($product->stock <= 10 ? 'Medium' : 'Low');
            
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
        
        return view('pages.inventory', compact('products', 'totalProducts', 'lowStockCount', 'criticalStockCount', 'totalValue', 'reorderRecommendations', 'reorderCount', 'reorderNotifications'));
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
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'price' => 'nullable|numeric',
            'reorder_level' => 'required|integer|min:0',
        ]);

        // Auto-calculate status based on stock level
        if ($validated['stock'] <= 5) {
            $validated['status'] = 'Critical';
        } elseif ($validated['stock'] <= 10) {
            $validated['status'] = 'Low Stock';
        } else {
            $validated['status'] = 'In Stock';
        }

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
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
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
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'price' => 'nullable|numeric',
            'reorder_level' => 'required|integer|min:0',
        ]);

        // Auto-calculate status based on stock level
        if ($validated['stock'] <= 5) {
            $validated['status'] = 'Critical';
        } elseif ($validated['stock'] <= 10) {
            $validated['status'] = 'Low Stock';
        } else {
            $validated['status'] = 'In Stock';
        }

        $product->update($validated);

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
     * Approve reorder recommendation for a product.
     */
    public function approveReorder(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $quantity = $request->input('quantity');
            
            // Update stock by adding the approved quantity
            $newStock = $product->stock + $quantity;
            $product->update(['stock' => $newStock]);
            
            // Auto-update status based on new stock level
            $newStatus = '';
            if ($newStock <= 5) {
                $newStatus = 'Critical';
            } elseif ($newStock <= 10) {
                $newStatus = 'Low Stock';
            } else {
                $newStatus = 'In Stock';
            }
            
            $product->update(['status' => $newStatus]);
            
            // For AJAX: return JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Reorder approved successfully',
                    'product' => $product,
                    'new_stock' => $newStock
                ]);
            }
            
            // For normal form submit
            return redirect()->back()->with('success', 'Reorder approved successfully!');
            
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Error approving reorder: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error approving reorder');
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
