<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SalesController extends Controller
{
    /**
     * Display sales dashboard with SARIMA forecasting
     */
    public function index()
    {
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();

        // Get monthly sales data for last 12 months
        $monthlySales = $this->getMonthlySalesData();

        // Get top selling products
        $topProducts = $this->getTopSellingProducts();

        // Calculate sales statistics
        $salesStats = $this->getSalesStatistics();

        // Generate SARIMA forecast for revenue and demand
        $forecast = $this->generateSarimaForecast($monthlySales);
        $demandForecast = $this->generateDemandForecast($monthlySales);

        return view('pages.forecasting', compact(
            'reorderCount',
            'reorderNotifications',
            'monthlySales',
            'topProducts',
            'salesStats',
            'forecast',
            'demandForecast'
        ));
    }

    /**
     * Store a new sale record
     */
    public function store(Request $request)
    {
        // Add debugging
        Log::info('Sale store method called', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity_sold' => 'required|integer|min:1',
                'sale_date' => 'required|date'
            ]);

            $product = Product::findOrFail($request->product_id);
            $totalAmount = $request->quantity_sold * $product->price;
            $saleMonth = Carbon::parse($request->sale_date)->format('Y-m');

            Log::info('Sale creation data', [
                'product_id' => $request->product_id,
                'product_name' => $product->name,
                'quantity_sold' => $request->quantity_sold,
                'unit_price' => $product->price,
                'total_amount' => $totalAmount,
                'sale_date' => $request->sale_date,
                'sale_month' => $saleMonth,
                'current_month' => Carbon::now()->format('Y-m')
            ]);

            $sale = Sale::create([
                'product_id' => $request->product_id,
                'quantity_sold' => $request->quantity_sold,
                'unit_price' => $product->price,
                'total_amount' => $totalAmount,
                'sale_date' => $request->sale_date,
                'sale_month' => $saleMonth
            ]);

            Log::info('Sale created successfully', ['sale_id' => $sale->id]);

            // Update product stock
            $product->decrement('stock', $request->quantity_sold);

            Log::info('Product stock updated', [
                'product_id' => $product->id,
                'new_stock' => $product->fresh()->stock
            ]);

            // Get updated statistics after sale
            $updatedStats = $this->getSalesStatistics();
            $updatedTopProducts = $this->getTopSellingProducts();

            return response()->json([
                'success' => true,
                'message' => 'Sale recorded successfully',
                'sale_id' => $sale->id,
                'sale_details' => [
                    'product_name' => $product->name,
                    'quantity_sold' => $request->quantity_sold,
                    'unit_price' => $product->price,
                    'total_amount' => $totalAmount,
                    'sale_date' => $request->sale_date,
                    'sale_month' => $saleMonth,
                    'remaining_stock' => $product->fresh()->stock
                ],
                'updated_statistics' => [
                    'current_month_revenue' => $updatedStats['current_month_revenue'],
                    'total_sales_count' => $updatedStats['total_sales_count'],
                    'growth_percentage' => $updatedStats['growth_percentage'],
                    'top_products' => $updatedTopProducts
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating sale', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error recording sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display sales history
     */
    public function history()
    {
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();

        // Get all sales with product details, paginated
        $sales = Sale::with('product')
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate summary statistics
        $totalSales = Sale::count();
        $totalRevenue = Sale::sum('total_amount');
        $averageOrderValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        return view('pages.sales-history', compact(
            'reorderCount',
            'reorderNotifications',
            'sales',
            'totalSales',
            'totalRevenue',
            'averageOrderValue'
        ));
    }

    /**
     * Get monthly sales data for the last 12 months
     */
    private function getMonthlySalesData()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Use sale_month for both filtering and grouping for consistency
        $startMonth = $startDate->format('Y-m');
        $endMonth = $endDate->format('Y-m');

        return Sale::select(
            DB::raw('sale_month'),
            DB::raw('SUM(quantity_sold) as total_quantity'),
            DB::raw('SUM(total_amount) as total_revenue'),
            DB::raw('COUNT(DISTINCT product_id) as unique_products')
        )
            ->where('sale_month', '>=', $startMonth)
            ->where('sale_month', '<=', $endMonth)
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get()
            ->keyBy('sale_month');
    }

    /**
     * Get top selling products for current month
     */
    public function getTopSellingProducts($limit = 5)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        return Sale::select(
            'products.name',
            'products.id',
            DB::raw('SUM(sales.quantity_sold) as total_sold'),
            DB::raw('SUM(sales.total_amount) as total_revenue')
        )
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.sale_month', $currentMonth)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate sales statistics
     */
    public function getSalesStatistics()
    {
        $thisMonth = Carbon::now()->format('Y-m');
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');

        $thisMonthSales = Sale::where('sale_month', $thisMonth)->sum('total_amount');
        $lastMonthSales = Sale::where('sale_month', $lastMonth)->sum('total_amount');

        $growth = $lastMonthSales > 0
            ? (($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100
            : 0;

        return [
            'current_month_revenue' => $thisMonthSales,
            'last_month_revenue' => $lastMonthSales,
            'growth_percentage' => round($growth, 2),
            'total_sales_count' => Sale::where('sale_month', $thisMonth)->count(),
            'average_order_value' => Sale::where('sale_month', $thisMonth)->avg('total_amount') ?? 0
        ];
    }

    /**
     * Generate SARIMA forecast (simplified version)
     */
    private function generateSarimaForecast($monthlySales)
    {
        // Create array of last 12 months for complete dataset
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[$month] = $monthlySales->get($month, (object)[
                'total_quantity' => 0,
                'total_revenue' => 0
            ]);
        }

        // Extract revenue values for forecasting
        $revenues = array_map(function ($data) {
            return is_object($data) && isset($data->total_revenue)
                ? (float)$data->total_revenue
                : 0;
        }, $months);

        // Simple SARIMA-like prediction (moving average with trend and seasonality)
        $forecast = [];
        $n = count($revenues);

        try {
            if ($n >= 3) {
                // Calculate trend (simple linear regression slope)
                $trend = $this->calculateTrend($revenues);

                // Calculate seasonal component (using 3-month cycle for simplicity)
                $seasonal = $this->calculateSeasonality($revenues, 3);

                // Generate 6-month forecast
                for ($i = 1; $i <= 6; $i++) {
                    $baseValue = count($revenues) > 0 ? (float)end($revenues) : 0;
                    $trendComponent = $trend * $i;
                    $seasonalIndex = ($n + $i - 1) % 3;
                    $seasonalComponent = isset($seasonal[$seasonalIndex]) ? $seasonal[$seasonalIndex] : 0;

                    $predictedValue = max(0, $baseValue + $trendComponent + $seasonalComponent);

                    $forecastMonth = Carbon::now()->addMonths($i)->format('Y-m');
                    $forecast[$forecastMonth] = round($predictedValue, 2);
                }
            }
        } catch (Exception $e) {
            // If forecasting fails, return empty forecast
            $forecast = [];
        }

        return [
            'historical' => $revenues,
            'predicted' => $forecast,
            'months' => array_keys($months)
        ];
    }

    /**
     * Calculate trend component
     */
    private function calculateTrend($data)
    {
        // Filter out null values and ensure we have numeric data
        $data = array_filter($data, function ($value) {
            return is_numeric($value);
        });

        $n = count($data);
        if ($n < 2) return 0;

        // Reindex array to ensure consecutive indices
        $data = array_values($data);

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = (float)$data[$i]; // Ensure numeric value

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) return 0; // Avoid division by zero

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        return $slope;
    }

    /**
     * Calculate seasonal component
     */
    private function calculateSeasonality($data, $period)
    {
        // Filter out null values and ensure we have numeric data
        $data = array_filter($data, function ($value) {
            return is_numeric($value);
        });

        $n = count($data);
        if ($n == 0) return array_fill(0, $period, 0);

        // Reindex array to ensure consecutive indices
        $data = array_values($data);

        $seasonal = array_fill(0, $period, 0);
        $counts = array_fill(0, $period, 0);

        // Calculate average for each season
        for ($i = 0; $i < $n; $i++) {
            $seasonIndex = $i % $period;
            $seasonal[$seasonIndex] += (float)$data[$i];
            $counts[$seasonIndex]++;
        }

        // Calculate deviations from overall mean
        $validValues = array_map('floatval', $data);
        $overallMean = $n > 0 ? array_sum($validValues) / $n : 0;

        for ($i = 0; $i < $period; $i++) {
            if ($counts[$i] > 0) {
                $seasonal[$i] = ($seasonal[$i] / $counts[$i]) - $overallMean;
            }
        }

        return $seasonal;
    }

    /**
     * Generate demand quantity forecast (not just revenue)
     */
    private function generateDemandForecast($monthlySales)
    {
        // Create array of last 12 months for demand quantities
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[$month] = $monthlySales->get($month, (object)[
                'total_quantity' => 0,
                'total_revenue' => 0
            ]);
        }

        // Extract quantity values for forecasting
        $quantities = array_map(function ($data) {
            return is_object($data) && isset($data->total_quantity)
                ? (float)$data->total_quantity
                : 0;
        }, $months);

        // SARIMA-like prediction for demand quantities
        $forecast = [];
        $n = count($quantities);

        try {
            if ($n >= 3) {
                // Calculate trend and seasonality for quantities
                $trend = $this->calculateTrend($quantities);
                $seasonal = $this->calculateSeasonality($quantities, 3);

                // Generate 6-month demand forecast
                for ($i = 1; $i <= 6; $i++) {
                    $baseValue = count($quantities) > 0 ? (float)end($quantities) : 0;
                    $trendComponent = $trend * $i;
                    $seasonalIndex = ($n + $i - 1) % 3;
                    $seasonalComponent = isset($seasonal[$seasonalIndex]) ? $seasonal[$seasonalIndex] : 0;

                    $predictedQuantity = max(0, $baseValue + $trendComponent + $seasonalComponent);

                    $forecastMonth = Carbon::now()->addMonths($i)->format('Y-m');
                    $forecast[$forecastMonth] = round($predictedQuantity);
                }
            }
        } catch (Exception $e) {
            $forecast = [];
        }

        return [
            'historical' => $quantities,
            'predicted' => $forecast,
            'months' => array_keys($months),
            'type' => 'demand_quantity'
        ];
    }

    /**
     * Get aggregated inventory insights based on SARIMA predictions
     */
    public function getInventoryInsights()
    {
        $products = Product::all();
        $insights = [];

        foreach ($products as $product) {
            $demandHistory = $this->getProductDemandHistory($product->id);
            $forecastedDemand = $this->forecastProductDemand($product->id);
            $dynamicReorderPoint = \App\Http\Controllers\ProductController::calculateDynamicReorderPoint($product->id);
            
            $insights[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $product->stock,
                'static_reorder_level' => $product->reorder_level,
                'dynamic_reorder_level' => $dynamicReorderPoint,
                'forecasted_demand' => $forecastedDemand,
                'recommended_action' => $this->getRecommendedAction($product, $dynamicReorderPoint, $forecastedDemand),
                'risk_level' => $this->calculateRiskLevel($product, $forecastedDemand)
            ];
        }

        return $insights;
    }

    /**
     * Get product demand history for individual product analysis
     */
    private function getProductDemandHistory($productId, $months = 6)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        return Sale::select(
            DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as month'),
            DB::raw('SUM(quantity_sold) as total_demand')
        )
        ->where('product_id', $productId)
        ->where('sale_date', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total_demand')
        ->toArray();
    }

    /**
     * Forecast demand for individual product
     */
    private function forecastProductDemand($productId)
    {
        $demandData = $this->getProductDemandHistory($productId);
        
        if (count($demandData) < 2) {
            return count($demandData) > 0 ? $demandData[0] : 10;
        }

        $trend = $this->calculateTrend($demandData);
        $lastValue = end($demandData);
        
        return max(0, round($lastValue + $trend));
    }

    /**
     * Get recommended action based on SARIMA analysis
     */
    private function getRecommendedAction($product, $dynamicReorderPoint, $forecastedDemand)
    {
        if ($product->stock <= 0) {
            return 'URGENT: Out of Stock - Immediate Reorder Required';
        } elseif ($product->stock <= $dynamicReorderPoint) {
            return 'REORDER: Stock below dynamic threshold';
        } elseif ($forecastedDemand > $product->stock * 0.8) {
            return 'MONITOR: High demand forecast - consider stocking up';
        } else {
            return 'NORMAL: Stock levels adequate';
        }
    }

    /**
     * Calculate risk level based on SARIMA predictions
     */
    private function calculateRiskLevel($product, $forecastedDemand)
    {
        $stockRatio = $product->stock > 0 ? $forecastedDemand / $product->stock : 999;
        
        if ($stockRatio >= 1.5) return 'HIGH';
        if ($stockRatio >= 1.0) return 'MEDIUM';
        if ($stockRatio >= 0.5) return 'LOW';
        return 'MINIMAL';
    }
}
