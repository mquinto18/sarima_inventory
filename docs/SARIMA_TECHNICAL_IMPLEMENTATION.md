# SARIMA Technical Implementation Guide

## 🔬 Technical Deep Dive: How SARIMA Algorithm Works with Database Tables

### **Table-by-Table SARIMA Implementation Analysis**

---

## 📊 **1. SALES TABLE - The SARIMA Data Engine**

### **Database Schema:**

```sql
CREATE TABLE sales (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    quantity_sold INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,    -- 🎯 SARIMA PRIMARY INPUT
    sale_date DATE NOT NULL,                -- 🎯 TIME SERIES DIMENSION
    sale_month VARCHAR(7) NOT NULL,         -- 🎯 SARIMA GROUPING KEY
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_sarima_analysis (product_id, sale_date),
    INDEX idx_monthly_grouping (sale_month),
    INDEX idx_time_series (sale_date, total_amount)
);
```

### **SARIMA Data Extraction Process:**

#### **Step A: Time Series Construction**

```php
/**
 * Extracts monthly aggregated sales data for SARIMA time series analysis
 * Location: SalesController::getMonthlySalesData()
 */
public function getMonthlySalesData()
{
    // Get 12 months of historical data for SARIMA
    $startDate = Carbon::now()->subMonths(11)->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();

    return Sale::select(
        DB::raw('sale_month'),                    // Time dimension
        DB::raw('SUM(total_amount) as total_revenue'),     // Primary SARIMA variable
        DB::raw('SUM(quantity_sold) as total_quantity'),   // Volume analysis
        DB::raw('COUNT(DISTINCT product_id) as unique_products') // Diversity metric
    )
    ->where('sale_month', '>=', $startDate->format('Y-m'))
    ->where('sale_month', '<=', $endDate->format('Y-m'))
    ->groupBy('sale_month')          // Monthly aggregation for seasonal analysis
    ->orderBy('sale_month')          // Chronological ordering for time series
    ->get()
    ->keyBy('sale_month');           // Indexed by time period
}
```

#### **Step B: Data Preprocessing for SARIMA**

```php
/**
 * Cleans and prepares sales data for SARIMA algorithm
 * Location: SalesController::preprocessSalesData()
 */
private function preprocessSalesData($rawSalesData)
{
    $processedData = [
        'monthly_revenues' => [],
        'monthly_quantities' => [],
        'outliers_detected' => [],
        'missing_months_filled' => [],
        'data_quality_score' => 0
    ];

    // 1. Fill missing months with interpolated values
    $allMonths = $this->generateMonthlySequence($startMonth, $endMonth);
    foreach ($allMonths as $month) {
        if (!isset($rawSalesData[$month])) {
            // SARIMA preprocessing: Fill missing data points
            $processedData['monthly_revenues'][$month] = $this->interpolateValue($month, $rawSalesData);
            $processedData['missing_months_filled'][] = $month;
        } else {
            $processedData['monthly_revenues'][$month] = $rawSalesData[$month]->total_revenue;
        }
    }

    // 2. Outlier detection using statistical methods
    $revenues = array_values($processedData['monthly_revenues']);
    $mean = array_sum($revenues) / count($revenues);
    $stdDev = $this->calculateStandardDeviation($revenues, $mean);

    foreach ($processedData['monthly_revenues'] as $month => $revenue) {
        // Detect outliers using 2-sigma rule
        if (abs($revenue - $mean) > 2 * $stdDev) {
            $processedData['outliers_detected'][] = [
                'month' => $month,
                'value' => $revenue,
                'expected_range' => [$mean - 2*$stdDev, $mean + 2*$stdDev]
            ];
            // Smooth outliers for better SARIMA performance
            $processedData['monthly_revenues'][$month] = $mean;
        }
    }

    // 3. Calculate data quality metrics
    $processedData['data_quality_score'] = $this->calculateDataQuality($processedData);

    return $processedData;
}
```

#### **Step C: Seasonality Detection**

```php
/**
 * Analyzes seasonal patterns in sales data for SARIMA modeling
 * Location: SalesController::analyzeSeasonality()
 */
private function analyzeSeasonality($preprocessedData)
{
    $analysis = [
        'seasonal_indices' => [],
        'peak_months' => [],
        'low_months' => [],
        'trend_direction' => '',
        'yearly_growth_rate' => 0,
        'seasonality_strength' => 0
    ];

    $revenues = $preprocessedData['monthly_revenues'];
    $totalRevenue = array_sum($revenues);
    $averageRevenue = $totalRevenue / count($revenues);

    // Calculate seasonal indices (SARIMA seasonal component)
    foreach ($revenues as $month => $revenue) {
        $monthNumber = date('n', strtotime($month . '-01'));
        if (!isset($analysis['seasonal_indices'][$monthNumber])) {
            $analysis['seasonal_indices'][$monthNumber] = [];
        }
        // Seasonal index = actual / average (detrended)
        $analysis['seasonal_indices'][$monthNumber][] = $revenue / $averageRevenue;
    }

    // Average seasonal indices across years
    foreach ($analysis['seasonal_indices'] as $monthNumber => $indices) {
        $analysis['seasonal_indices'][$monthNumber] = array_sum($indices) / count($indices);
    }

    // Identify peak and low seasons
    arsort($analysis['seasonal_indices']);
    $analysis['peak_months'] = array_slice(array_keys($analysis['seasonal_indices']), 0, 3, true);

    asort($analysis['seasonal_indices']);
    $analysis['low_months'] = array_slice(array_keys($analysis['seasonal_indices']), 0, 3, true);

    return $analysis;
}
```

---

## 🏪 **2. PRODUCTS TABLE - SARIMA Output Integration**

### **Database Schema:**

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    stock INT NOT NULL,                     -- 🎯 SARIMA MONITORS
    status ENUM('active', 'inactive') DEFAULT 'active',
    price DECIMAL(10,2) NOT NULL,          -- 🎯 SARIMA USES FOR REVENUE
    reorder_level INT DEFAULT 0,           -- 🎯 SARIMA UPDATES THIS
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_inventory_management (stock, reorder_level),
    INDEX idx_category_analysis (category, status)
);
```

### **SARIMA Integration with Products:**

#### **Step A: Demand Forecasting Per Product**

```php
/**
 * Generates individual product demand forecasts using SARIMA
 * Location: SalesController::generateProductDemandForecast()
 */
private function generateProductDemandForecast($preprocessedData)
{
    $demandForecasts = [];

    // Get all active products
    $products = Product::where('status', 'active')->get();

    foreach ($products as $product) {
        // Extract product-specific sales history
        $productSales = Sale::where('product_id', $product->id)
            ->selectRaw('sale_month, SUM(quantity_sold) as monthly_demand')
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get();

        // Apply SARIMA forecasting to product demand
        $forecast = $this->applySarimaToProduct($productSales, $product);

        $demandForecasts[$product->id] = [
            'product_name' => $product->name,
            'current_stock' => $product->stock,           // Current inventory
            'reorder_level' => $product->reorder_level,   // Current threshold
            'forecasted_demand' => $forecast['demand_6_months'],
            'confidence_interval' => $forecast['confidence_bands'],
            'risk_level' => $this->assessStockoutRisk($product, $forecast),
            'days_until_stockout' => $this->calculateDaysUntilStockout($product, $forecast),
            'recommended_reorder_level' => $forecast['optimal_reorder_level']
        ];
    }

    return $demandForecasts;
}
```

#### **Step B: Automated Reorder Level Updates**

```php
/**
 * Updates product reorder levels based on SARIMA forecasts
 * Location: SalesController::updateReorderLevels()
 */
private function updateReorderLevels($demandForecasts)
{
    foreach ($demandForecasts as $productId => $forecast) {
        // Calculate optimal reorder level using SARIMA forecast
        $safetyStock = $forecast['forecasted_demand'] * 0.2; // 20% safety buffer
        $leadTimeDemand = $forecast['average_daily_demand'] * 7; // 7 days lead time
        $optimalReorderLevel = $leadTimeDemand + $safetyStock;

        // Update product reorder level in database
        Product::where('id', $productId)->update([
            'reorder_level' => ceil($optimalReorderLevel)
        ]);

        Log::info("SARIMA: Updated reorder level for product {$productId} to {$optimalReorderLevel}");
    }
}
```

#### **Step C: Risk Assessment Integration**

```php
/**
 * Assesses stockout risk using SARIMA predictions
 * Location: SalesController::assessStockoutRisk()
 */
private function assessStockoutRisk($product, $sarimaForecast)
{
    $currentStock = $product->stock;
    $forecastedDemand = $sarimaForecast['demand_next_month'];
    $demandVolatility = $sarimaForecast['volatility_index'];

    // Risk calculation based on SARIMA confidence intervals
    $lowerConfidenceBound = $sarimaForecast['confidence_interval']['lower'];
    $upperConfidenceBound = $sarimaForecast['confidence_interval']['upper'];

    if ($currentStock < $lowerConfidenceBound) {
        return 'HIGH';    // Stock below even optimistic demand forecast
    } elseif ($currentStock < $forecastedDemand) {
        return 'MEDIUM';  // Stock below expected demand
    } elseif ($currentStock < $upperConfidenceBound) {
        return 'LOW';     // Stock adequate for most scenarios
    } else {
        return 'NORMAL';  // Stock sufficient for all forecasted scenarios
    }
}
```

---

## 🔄 **3. SARIMA ALGORITHM WORKFLOW**

### **Complete Database-to-Algorithm Pipeline:**

```php
/**
 * Master SARIMA workflow integrating all database tables
 * Location: SalesController::index()
 */
public function index()
{
    // === STEP 1: DATA EXTRACTION FROM DATABASE ===

    // Extract time series from sales table
    $monthlySales = $this->getMonthlySalesData();

    // Get current inventory state from products table
    $currentInventory = Product::select('id', 'name', 'stock', 'reorder_level')
        ->where('status', 'active')
        ->get()
        ->keyBy('id');

    // === STEP 2: SARIMA PREPROCESSING ===

    // Clean and prepare data for SARIMA algorithm
    $preprocessedData = $this->preprocessSalesData($monthlySales);

    // Detect seasonal patterns
    $seasonalityAnalysis = $this->analyzeSeasonality($preprocessedData);

    // === STEP 3: SARIMA FORECASTING ===

    // Generate revenue forecasts with confidence intervals
    $revenueForecast = $this->generateEnhancedSarimaForecast($preprocessedData, $seasonalityAnalysis);

    // Generate product-specific demand forecasts
    $demandForecast = $this->generateProductDemandForecast($preprocessedData);

    // === STEP 4: AUTOMATED INVENTORY DECISIONS ===

    // Generate restocking recommendations
    $restockingRecommendations = $this->generateRestockingRecommendations($demandForecast);

    // Update reorder levels in products table
    $this->updateReorderLevels($demandForecast);

    // === STEP 5: PERFORMANCE MONITORING ===

    // Calculate forecast accuracy vs actual sales
    $forecastAccuracy = $this->calculateForecastAccuracy();

    // === STEP 6: DASHBOARD OUTPUT ===

    return view('pages.forecasting', compact(
        'revenueForecast',
        'demandForecast',
        'restockingRecommendations',
        'seasonalityAnalysis',
        'forecastAccuracy'
    ));
}
```

---

## 📈 **4. SARIMA MATHEMATICAL IMPLEMENTATION**

### **Core SARIMA Algorithm (Simplified Implementation):**

```php
/**
 * Simplified SARIMA(p,d,q)(P,D,Q)s implementation
 * Location: SalesController::applySarimaModel()
 */
private function applySarimaModel($timeSeries, $parameters = null)
{
    // Default SARIMA parameters for retail sales
    $p = 1; // Autoregressive order
    $d = 1; // Differencing order
    $q = 1; // Moving average order
    $P = 1; // Seasonal autoregressive order
    $D = 1; // Seasonal differencing order
    $Q = 1; // Seasonal moving average order
    $s = 12; // Seasonal period (monthly data)

    $forecast = [
        'predicted' => [],
        'confidence_intervals' => [],
        'model_parameters' => compact('p','d','q','P','D','Q','s')
    ];

    // Step 1: Differencing to achieve stationarity
    $stationarySeries = $this->applyDifferencing($timeSeries, $d);
    $seasonalDifferenced = $this->applySeasonalDifferencing($stationarySeries, $D, $s);

    // Step 2: Estimate ARMA parameters
    $armaParams = $this->estimateArmaParameters($seasonalDifferenced, $p, $q);
    $seasonalParams = $this->estimateSeasonalParameters($seasonalDifferenced, $P, $Q, $s);

    // Step 3: Generate forecasts
    for ($h = 1; $h <= 6; $h++) { // 6-month forecast horizon
        $predicted_value = $this->calculateSarimaForecast($h, $armaParams, $seasonalParams, $timeSeries);
        $confidence_interval = $this->calculateConfidenceInterval($predicted_value, $h, $timeSeries);

        $forecast['predicted'][] = $predicted_value;
        $forecast['confidence_intervals'][] = $confidence_interval;
    }

    return $forecast;
}
```

---

## 🎯 **5. DATABASE PERFORMANCE OPTIMIZATION FOR SARIMA**

### **Optimized Queries for SARIMA:**

```sql
-- Monthly sales aggregation (optimized for SARIMA time series)
EXPLAIN SELECT
    sale_month,
    SUM(total_amount) as revenue,
    SUM(quantity_sold) as volume,
    COUNT(*) as transactions,
    AVG(total_amount) as avg_transaction
FROM sales
WHERE sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 24 MONTH), '%Y-%m')
GROUP BY sale_month
ORDER BY sale_month;

-- Product-specific demand analysis
EXPLAIN SELECT
    p.id,
    p.name,
    p.stock,
    p.reorder_level,
    s.sale_month,
    SUM(s.quantity_sold) as monthly_demand,
    AVG(s.unit_price) as avg_price
FROM products p
LEFT JOIN sales s ON p.id = s.product_id
WHERE p.status = 'active'
    AND s.sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
GROUP BY p.id, p.name, p.stock, p.reorder_level, s.sale_month
ORDER BY p.id, s.sale_month;

-- Seasonal pattern analysis
EXPLAIN SELECT
    MONTH(sale_date) as month_number,
    AVG(total_amount) as avg_revenue,
    STDDEV(total_amount) as revenue_volatility,
    COUNT(*) as transaction_count
FROM sales
WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
GROUP BY MONTH(sale_date)
ORDER BY month_number;
```

### **Database Indexes for SARIMA Performance:**

```sql
-- Primary SARIMA analysis indexes
CREATE INDEX idx_sarima_time_series ON sales (sale_month, total_amount);
CREATE INDEX idx_sarima_product_analysis ON sales (product_id, sale_date, quantity_sold);
CREATE INDEX idx_sarima_seasonal_pattern ON sales (MONTH(sale_date), total_amount);

-- Inventory management indexes
CREATE INDEX idx_inventory_status ON products (status, stock, reorder_level);
CREATE INDEX idx_product_category_analysis ON products (category, status);

-- Composite indexes for complex SARIMA queries
CREATE INDEX idx_sarima_comprehensive ON sales (product_id, sale_month, total_amount, quantity_sold);
```

---

This technical documentation shows exactly how the SARIMA algorithm integrates with each database table to provide intelligent inventory management and sales forecasting capabilities.
