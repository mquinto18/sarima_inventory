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
     * Display sales dashboard with Enhanced SARIMA forecasting
     * Implements comprehensive data preprocessing, seasonality analysis, and predictive restocking
     */
    public function index()
    {
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();

        // Step 1: Data Preprocessing - Get and clean historical sales data
        $monthlySales = $this->getMonthlySalesData();
        $preprocessedData = $this->preprocessSalesData($monthlySales);

        // Step 2: Seasonality Analysis - Identify seasonal patterns
        $seasonalityAnalysis = $this->analyzeSeasonality($preprocessedData);

        // Step 3: Get top selling products with demand patterns
        $topProducts = $this->getTopSellingProducts();

        // Step 4: Calculate comprehensive sales statistics
        $salesStats = $this->getSalesStatistics();

        // Step 5: Generate Enhanced SARIMA forecast with confidence intervals
        $forecast = $this->generateEnhancedSarimaForecast($preprocessedData, $seasonalityAnalysis);

        // Step 6: Generate product-specific demand forecasts for inventory management
        $demandForecast = $this->generateProductDemandForecast($preprocessedData);

        // Step 7: Generate automated restocking recommendations
        $restockingRecommendations = $this->generateRestockingRecommendations($demandForecast);

        // Step 8: Performance metrics for system evaluation
        $forecastAccuracy = $this->calculateForecastAccuracy();

        // Step 9: Get sales trend data for charts
        $salesTrend = \App\Http\Controllers\ProductController::getSalesTrendData();

        return view('pages.forecasting', compact(
            'reorderCount',
            'reorderNotifications',
            'monthlySales',
            'topProducts',
            'salesStats',
            'forecast',
            'demandForecast',
            'seasonalityAnalysis',
            'restockingRecommendations',
            'forecastAccuracy',
            'preprocessedData',
            'salesTrend'
        ));
    }

    /**
     * Store a new sale record
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity_sold' => 'required|integer|min:1',
                'sale_date' => 'required|date'
            ]);

            $product = Product::findOrFail($request->product_id);
            $totalAmount = $request->quantity_sold * $product->price;
            $monthYear = Carbon::parse($request->sale_date)->format('Y-m');
            $sale = Sale::create([
                'product_id' => $request->product_id,
                'quantity_sold' => $request->quantity_sold,
                'unit_price' => $product->price,
                'total_amount' => $totalAmount,
                'sale_date' => $request->sale_date,
                'month_year' => $monthYear
            ]);

            // Update product stock
            $product->decrement('stock', $request->quantity_sold);

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
                    // 'sale_month' => $saleMonth, // No longer stored in DB
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
            return response()->json([
                'success' => false,
                'message' => 'Error recording sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly sales data for the last 12 months
     */
    public function getMonthlySalesData()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        return Sale::select(
            DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as sale_month'),
            DB::raw('SUM(quantity_sold) as total_quantity'),
            DB::raw('SUM(total_amount) as total_revenue'),
            DB::raw('COUNT(DISTINCT product_id) as unique_products')
        )
            ->where('sale_date', '>=', $startDate)
            ->where('sale_date', '<=', $endDate)
            ->groupBy(DB::raw('DATE_FORMAT(sale_date, "%Y-%m")'))
            ->orderBy(DB::raw('DATE_FORMAT(sale_date, "%Y-%m")'))
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
            'products.id',
            'products.name',
            DB::raw('SUM(sales.quantity_sold) as total_sold'),
            DB::raw('SUM(sales.total_amount) as total_revenue')
        )
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->whereYear('sales.sale_date', substr($currentMonth, 0, 4))
            ->whereMonth('sales.sale_date', substr($currentMonth, 5, 2))
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
        $thisMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $thisMonthSales = Sale::whereYear('sale_date', $thisMonth->year)
            ->whereMonth('sale_date', $thisMonth->month)
            ->sum('total_amount');
        $lastMonthSales = Sale::whereYear('sale_date', $lastMonth->year)
            ->whereMonth('sale_date', $lastMonth->month)
            ->sum('total_amount');

        if ($lastMonthSales === null || $lastMonthSales == 0) {
            $growth = 0;
        } else {
            $growth = (($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100;
        }

        $totalSalesCount = Sale::whereYear('sale_date', $thisMonth->year)
            ->whereMonth('sale_date', $thisMonth->month)
            ->sum('quantity_sold');
        $averageOrderValue = Sale::whereYear('sale_date', $thisMonth->year)
            ->whereMonth('sale_date', $thisMonth->month)
            ->avg('total_amount') ?? 0;

        return [
            'current_month_revenue' => $thisMonthSales,
            'last_month_revenue' => $lastMonthSales,
            'growth_percentage' => round($growth, 2),
            'total_sales_count' => $totalSalesCount,
            'average_order_value' => $averageOrderValue
        ];
    }

    /**
     * Generate SARIMA forecast based on historical sales data
     */
    public function generateSarimaForecast($salesData)
    {
        // Create array of last 12 months for complete dataset
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[$month] = $salesData->get($month, (object)[
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
    public function generateDemandForecast($monthlySales)
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
     * Step 1: Data Preprocessing - Clean and prepare historical sales data
     * Ensures precision of forecasting results as per project requirements
     */
    private function preprocessSalesData($monthlySales)
    {
        $preprocessed = [];

        // Fill missing months with zero values for complete time series
        for ($i = 23; $i >= 0; $i--) { // Extended to 24 months for better analysis
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $salesData = $monthlySales->get($month, (object)[
                'total_quantity' => 0,
                'total_revenue' => 0,
                'sales_count' => 0
            ]);

            $preprocessed[$month] = [
                'month' => $month,
                'revenue' => (float) $salesData->total_revenue,
                'quantity' => (int) $salesData->total_quantity,
                'transactions' => (int) $salesData->sales_count,
                'average_order_value' => $salesData->sales_count > 0 ?
                    $salesData->total_revenue / $salesData->sales_count : 0
            ];
        }

        // Data cleaning: Handle outliers and smooth data
        $preprocessed = $this->handleOutliers($preprocessed);

        // Calculate moving averages for trend analysis
        $preprocessed = $this->calculateMovingAverages($preprocessed);

        return $preprocessed;
    }

    /**
     * Step 2: Seasonality Analysis - Identify seasonal patterns and trends
     */
    private function analyzeSeasonality($preprocessedData)
    {
        $analysis = [
            'seasonal_indices' => [],
            'trend_direction' => 'stable',
            'seasonality_strength' => 0,
            'peak_months' => [],
            'low_months' => [],
            'yearly_growth_rate' => 0
        ];

        $revenues = array_column($preprocessedData, 'revenue');
        
        // Filter out zero revenues for better seasonality calculation
        $nonZeroRevenues = array_filter($revenues, function($rev) {
            return $rev > 0;
        });

        // Calculate seasonal indices for each month
        $monthlyTotals = [];
        foreach ($preprocessedData as $data) {
            // Only include months with actual sales data
            if ($data['revenue'] > 0) {
                $month = Carbon::parse($data['month'])->format('m');
                if (!isset($monthlyTotals[$month])) {
                    $monthlyTotals[$month] = [];
                }
                $monthlyTotals[$month][] = $data['revenue'];
            }
        }

        // Calculate overall average from non-zero revenues only
        $overallAverage = count($nonZeroRevenues) > 0 ? 
            array_sum($nonZeroRevenues) / count($nonZeroRevenues) : 0;

        // Calculate seasonal indices based on actual data
        foreach ($monthlyTotals as $month => $values) {
            $monthAverage = array_sum($values) / count($values);
            $analysis['seasonal_indices'][$month] = $overallAverage > 0 ?
                $monthAverage / $overallAverage : 1;
        }
        
        // Fill in missing months with neutral seasonal index (1.0)
        for ($m = 1; $m <= 12; $m++) {
            $monthStr = str_pad($m, 2, '0', STR_PAD_LEFT);
            if (!isset($analysis['seasonal_indices'][$monthStr])) {
                $analysis['seasonal_indices'][$monthStr] = 1.0;
            }
        }

        // Identify peak and low months
        arsort($analysis['seasonal_indices']);
        $analysis['peak_months'] = array_slice(array_keys($analysis['seasonal_indices']), 0, 3, true);

        asort($analysis['seasonal_indices']);
        $analysis['low_months'] = array_slice(array_keys($analysis['seasonal_indices']), 0, 3, true);

        // Calculate trend direction (only if we have enough data)
        if (count($revenues) >= 24) {
            $firstHalf = array_slice($revenues, 0, 12);
            $secondHalf = array_slice($revenues, 12, 12);
        } elseif (count($revenues) >= 12) {
            // If we have at least 12 months, compare first half with second half
            $midPoint = intval(count($revenues) / 2);
            $firstHalf = array_slice($revenues, 0, $midPoint);
            $secondHalf = array_slice($revenues, $midPoint);
        } else {
            // Insufficient data for trend analysis
            $analysis['trend_direction'] = 'insufficient_data';
            $analysis['yearly_growth_rate'] = 0;
            $firstHalf = $secondHalf = [];
        }

        if (!empty($firstHalf) && !empty($secondHalf)) {
            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);

            // Prevent division by zero error
            if ($firstAvg > 0) {
                if ($secondAvg > $firstAvg * 1.05) {
                    $analysis['trend_direction'] = 'increasing';
                    $analysis['yearly_growth_rate'] = (($secondAvg - $firstAvg) / $firstAvg) * 100;
                } elseif ($secondAvg < $firstAvg * 0.95) {
                    $analysis['trend_direction'] = 'decreasing';
                    $analysis['yearly_growth_rate'] = (($secondAvg - $firstAvg) / $firstAvg) * 100;
                } else {
                    $analysis['trend_direction'] = 'stable';
                    $analysis['yearly_growth_rate'] = 0;
                }
            } else {
                // Handle case when first half average is zero
                $analysis['trend_direction'] = $secondAvg > 0 ? 'increasing' : 'stable';
                $analysis['yearly_growth_rate'] = $secondAvg > 0 ? 100 : 0; // 100% growth from zero or no change
            }
        }

        // Calculate seasonality strength
        $analysis['seasonality_strength'] = $this->calculateSeasonalityStrength($analysis['seasonal_indices']);

        return $analysis;
    }

    /**
     * Step 3: Enhanced SARIMA Forecast with confidence intervals
     * Utilizes advanced SARIMA algorithm for reliable predictions
     */
    private function generateEnhancedSarimaForecast($preprocessedData, $seasonalityAnalysis)
    {
        $forecast = [
            'predicted' => [],
            'confidence_intervals' => [],
            'trend_component' => [],
            'seasonal_component' => [],
            'months' => [],
            'historical' => [],
            'forecast_horizon' => 6,
            'model_parameters' => [
                'p' => 1,
                'd' => 1,
                'q' => 1,
                'P' => 1,
                'D' => 1,
                'Q' => 1,
                's' => 12
            ]
        ];

        // Prepare sales data for Python script
        $sales = [];
        foreach ($preprocessedData as $data) {
            $forecast['months'][] = $data['month'];
            $forecast['historical'][] = round($data['revenue'], 2);
            $sales[] = [
                'month' => $data['month'],
                'revenue' => (float)$data['revenue']
            ];
        }

        $input = [
            'sales' => $sales,
            'forecast_period' => 6
        ];

        // Call the Python SARIMA script
        $pythonScript = base_path('python/sarima_forecast.py');
        $process = proc_open(
            'python "' . $pythonScript . '"',
            [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w']  // stderr
            ],
            $pipes
        );

        if (is_resource($process)) {
            fwrite($pipes[0], json_encode($input));
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);

            if ($returnCode === 0 && $output) {
                $sarima = json_decode($output, true);
                if ($sarima && isset($sarima['months'], $sarima['predicted'])) {
                    foreach ($sarima['months'] as $idx => $month) {
                        $forecast['predicted'][$month] = round($sarima['predicted'][$idx], 2);
                        $forecast['confidence_intervals'][$month] = [
                            'lower' => isset($sarima['conf_lower'][$idx]) ? round($sarima['conf_lower'][$idx], 2) : null,
                            'upper' => isset($sarima['conf_upper'][$idx]) ? round($sarima['conf_upper'][$idx], 2) : null,
                            'confidence_level' => 95
                        ];
                    }
                }
            } else {
                // Log error if Python script fails
                Log::error('SARIMA Python error: ' . $error);
            }
        } else {
            Log::error('Could not start SARIMA Python process.');
        }

        return $forecast;
    }

    /**
     * Step 4: Product-specific demand forecasting for inventory management
     */
    private function generateProductDemandForecast($preprocessedData)
    {
        $products = Product::all();
        $demandForecasts = [];

        foreach ($products as $product) {
            $productSales = $this->getProductSalesHistory($product->id);

            $demandForecasts[$product->id] = [
                'product_name' => $product->name,
                'current_stock' => $product->stock,
                'forecasted_demand' => $this->calculateProductDemand($productSales),
                'recommended_order_quantity' => $this->calculateReorderQuantity($product, $productSales),
                'risk_level' => $this->assessStockRisk($product, $productSales),
                'days_until_stockout' => $this->calculateDaysUntilStockout($product, $productSales)
            ];
        }

        return $demandForecasts;
    }

    /**
     * Step 5: Automated restocking recommendations
     * Supports efficient stock monitoring and replenishment
     */
    private function generateRestockingRecommendations($demandForecasts)
    {
        $recommendations = [
            'urgent_restock' => [],
            'monitor_closely' => [],
            'normal_stock' => [],
            'overstock_risk' => []
        ];

        foreach ($demandForecasts as $productId => $forecast) {
            $recommendation = [
                'product_id' => $productId,
                'product_name' => $forecast['product_name'],
                'current_stock' => $forecast['current_stock'],
                'forecasted_demand' => $forecast['forecasted_demand'],
                'recommended_action' => '',
                'priority_level' => 0
            ];

            // Categorize based on risk and demand
            if ($forecast['risk_level'] === 'HIGH' || $forecast['days_until_stockout'] <= 7) {
                $recommendation['recommended_action'] = 'URGENT: Reorder immediately';
                $recommendation['priority_level'] = 3;
                $recommendations['urgent_restock'][] = $recommendation;
            } elseif ($forecast['risk_level'] === 'MEDIUM' || $forecast['days_until_stockout'] <= 14) {
                $recommendation['recommended_action'] = 'Monitor closely and prepare reorder';
                $recommendation['priority_level'] = 2;
                $recommendations['monitor_closely'][] = $recommendation;
            } elseif ($forecast['current_stock'] > $forecast['forecasted_demand'] * 3) {
                $recommendation['recommended_action'] = 'Consider reducing orders - potential overstock';
                $recommendation['priority_level'] = 1;
                $recommendations['overstock_risk'][] = $recommendation;
            } else {
                $recommendation['recommended_action'] = 'Normal stock levels';
                $recommendation['priority_level'] = 0;
                $recommendations['normal_stock'][] = $recommendation;
            }
        }

        return $recommendations;
    }

    /**
     * Step 6: System accuracy evaluation
     * Calculates forecast accuracy for continuous improvement
     */
    private function calculateForecastAccuracy()
    {
        // Use the same calculation from ProductController for consistency
        return \App\Http\Controllers\ProductController::calculateForecastAccuracy();
    }

    // Helper methods for enhanced SARIMA implementation

    private function handleOutliers($data)
    {
        // Simple outlier detection and smoothing
        $revenues = array_column($data, 'revenue');
        $mean = array_sum($revenues) / count($revenues);
        $stdDev = $this->calculateStandardDeviation($revenues, $mean);

        foreach ($data as $key => &$item) {
            if (abs($item['revenue'] - $mean) > 2 * $stdDev) {
                // Replace outlier with moving average
                $item['revenue'] = $mean;
            }
        }

        return $data;
    }

    private function calculateMovingAverages($data, $window = 3)
    {
        $dataArray = array_values($data);
        for ($i = 0; $i < count($dataArray); $i++) {
            $start = max(0, $i - $window + 1);
            $subset = array_slice($dataArray, $start, min($window, $i + 1));
            $dataArray[$i]['moving_average'] = array_sum(array_column($subset, 'revenue')) / count($subset);
        }
        return $dataArray;
    }

    private function calculateSeasonalityStrength($seasonalIndices)
    {
        $variance = 0;
        $mean = array_sum($seasonalIndices) / count($seasonalIndices);

        foreach ($seasonalIndices as $index) {
            $variance += pow($index - $mean, 2);
        }

        return sqrt($variance / count($seasonalIndices));
    }

    private function calculateVolatility($data)
    {
        if (empty($data) || count($data) == 0) {
            return 0;
        }
        $mean = array_sum($data) / count($data);
        $variance = 0;

        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($data));
    }

    private function calculateStandardDeviation($data, $mean)
    {
        $variance = 0;
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        return sqrt($variance / count($data));
    }

    private function getProductSalesHistory($productId, $months = 12)
    {
        return Sale::where('product_id', $productId)
            ->where('sale_date', '>=', Carbon::now()->subMonths($months))
            ->orderBy('sale_date')
            ->get();
    }

    private function calculateProductDemand($productSales)
    {
        if ($productSales->isEmpty()) return 0;

        // Calculate monthly average demand
        $totalQuantity = $productSales->sum('quantity_sold');
        $months = max(1, $productSales->count() / 4); // Approximate months

        return round($totalQuantity / $months, 2);
    }

    private function calculateReorderQuantity($product, $salesHistory)
    {
        $avgDemand = $this->calculateProductDemand($salesHistory);
        $leadTime = 7; // Assumed lead time in days
        $safetyStock = $avgDemand * 0.5; // 50% safety stock

        return round(($avgDemand * $leadTime / 30) + $safetyStock);
    }

    private function assessStockRisk($product, $salesHistory)
    {
        $avgDemand = $this->calculateProductDemand($salesHistory);
        $daysOfStock = $avgDemand > 0 ? ($product->stock / $avgDemand) * 30 : 999;

        if ($daysOfStock <= 7) return 'HIGH';
        if ($daysOfStock <= 14) return 'MEDIUM';
        return 'LOW';
    }

    private function calculateDaysUntilStockout($product, $salesHistory)
    {
        $avgDailyDemand = $this->calculateProductDemand($salesHistory) / 30;

        if ($avgDailyDemand <= 0) return 999;

        return round($product->stock / $avgDailyDemand);
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
