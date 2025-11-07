# SARIMA Algorithm Implementation - Database Documentation

## 📊 Sales Forecasting and Inventory Management System for Retail Stores Using SARIMA Algorithm

This document explains how the SARIMA (Seasonal Autoregressive Integrated Moving Average) algorithm is implemented across the database tables for comprehensive inventory management and sales forecasting.

---

## 🗄️ Database Tables and SARIMA Implementation

### 1. **PRODUCTS TABLE** - Inventory Management Core

```sql
Schema: products
├── id (Primary Key)
├── name (Product Name)
├── category (Product Category)
├── stock (Current Inventory Level) ⭐ SARIMA INPUT
├── status (Active/Inactive)
├── price (Unit Price) ⭐ SARIMA INPUT
├── reorder_level (Minimum Stock Threshold) ⭐ SARIMA OUTPUT
├── created_at
└── updated_at
```

#### **🎯 SARIMA Role in Products Table:**

**INPUT DATA FOR SARIMA:**

-   `stock`: Current inventory levels used for demand calculation
-   `price`: Used for revenue forecasting and profitability analysis
-   `category`: Used for category-wise seasonal pattern analysis

**OUTPUT FROM SARIMA:**

-   `reorder_level`: Automatically calculated based on SARIMA demand forecasts
-   Stock recommendations updated based on seasonal predictions

**SARIMA PROCESSES:**

1. **Demand Forecasting**: Predicts future demand per product
2. **Seasonal Analysis**: Identifies peak/low seasons per product category
3. **Risk Assessment**: Calculates stockout probability
4. **Automated Reordering**: Updates reorder levels based on forecasts

```php
// Example: How SARIMA uses Products table
$currentStock = Product::find($productId)->stock;
$forecastedDemand = $sarimaForecast['predicted_demand_6_months'];
$recommendedReorderLevel = $forecastedDemand * $safetyStockMultiplier;

// Update reorder level based on SARIMA
Product::where('id', $productId)->update([
    'reorder_level' => $recommendedReorderLevel
]);
```

---

### 2. **SALES TABLE** - Historical Data Engine

```sql
Schema: sales
├── id (Primary Key)
├── product_id (Foreign Key to products) ⭐ SARIMA GROUPING
├── quantity_sold (Units Sold) ⭐ SARIMA INPUT
├── unit_price (Price per Unit) ⭐ SARIMA INPUT
├── total_amount (Revenue) ⭐ SARIMA PRIMARY INPUT
├── sale_date (Transaction Date) ⭐ SARIMA TIME SERIES
├── sale_month (YYYY-MM Format) ⭐ SARIMA GROUPING
├── created_at
└── updated_at
```

#### **🎯 SARIMA Role in Sales Table:**

**PRIMARY DATA SOURCE FOR SARIMA:**

-   `total_amount`: Main time series data for revenue forecasting
-   `quantity_sold`: Volume analysis and demand forecasting
-   `sale_month`: Temporal grouping for seasonal pattern detection
-   `sale_date`: Time series chronological ordering

**SARIMA ANALYSIS PROCESSES:**

1. **📈 Time Series Construction:**

```php
// Monthly revenue aggregation for SARIMA
$monthlySales = Sale::select(
    DB::raw('sale_month'),
    DB::raw('SUM(total_amount) as total_revenue'),
    DB::raw('SUM(quantity_sold) as total_quantity')
)
->groupBy('sale_month')
->orderBy('sale_month')
->get();
```

2. **🔄 Seasonal Pattern Detection:**

```php
// Identify seasonal indices per month
$seasonalIndices = [];
foreach($salesData as $month => $revenue) {
    $seasonalIndices[$month] = $revenue / $averageRevenue;
}
```

3. **📊 Trend Analysis:**

```php
// Calculate growth trends
$firstHalf = array_slice($revenues, 0, 12);
$secondHalf = array_slice($revenues, 12, 12);
$growthRate = (($secondHalf_avg - $firstHalf_avg) / $firstHalf_avg) * 100;
```

4. **🎯 Product-Specific Demand Forecasting:**

```php
// Per-product demand analysis
$productDemand = Sale::where('product_id', $productId)
    ->selectRaw('sale_month, SUM(quantity_sold) as demand')
    ->groupBy('sale_month')
    ->get();
```

---

## 🔍 SARIMA Algorithm Implementation Steps

### **Step 1: Data Preprocessing (`preprocessSalesData()`)**

**Data Source:** `sales.total_amount`, `sales.quantity_sold`

```php
Input: Raw monthly sales data from sales table
Process:
- Outlier detection and removal
- Missing value interpolation
- Data smoothing and normalization
Output: Clean time series ready for SARIMA analysis
```

### **Step 2: Seasonality Analysis (`analyzeSeasonality()`)**

**Data Source:** `sales.sale_month`, `sales.total_amount`

```php
Input: Monthly aggregated sales data
Process:
- Calculate seasonal indices for each month
- Identify peak and low sales months
- Determine trend direction and growth rate
- Calculate seasonality strength
Output: Seasonal patterns and trend analysis
```

### **Step 3: Enhanced SARIMA Forecasting (`generateEnhancedSarimaForecast()`)**

**Data Source:** Preprocessed sales data + seasonality analysis

```php
Input: Clean time series + seasonal patterns
Process:
- Apply SARIMA(p,d,q)(P,D,Q)s model
- Generate 6-month forecasts
- Calculate 95% confidence intervals
- Decompose trend and seasonal components
Output: Revenue forecasts with confidence bands
```

### **Step 4: Product Demand Forecasting (`generateProductDemandForecast()`)**

**Data Source:** `sales.product_id`, `sales.quantity_sold`, `products.stock`

```php
Input: Product-specific sales history + current stock
Process:
- Individual product demand forecasting
- Risk assessment (HIGH/MEDIUM/LOW)
- Days until stockout calculation
- Demand volatility analysis
Output: Product-specific demand forecasts and alerts
```

### **Step 5: Automated Restocking (`generateRestockingRecommendations()`)**

**Data Source:** Demand forecasts + `products.stock` + `products.reorder_level`

```php
Input: Demand forecasts + current inventory levels
Process:
- Compare forecasted demand vs current stock
- Categorize products by urgency level
- Generate actionable recommendations
- Calculate optimal reorder quantities
Output: Automated restocking recommendations
```

### **Step 6: Performance Evaluation (`calculateForecastAccuracy()`)**

**Data Source:** Historical forecasts vs actual `sales.total_amount`

```php
Input: Previous forecasts + actual sales data
Process:
- Calculate MAPE (Mean Absolute Percentage Error)
- Measure forecast bias and accuracy trends
- Validate model performance
Output: Accuracy metrics and model validation
```

---

## 🎯 Database Indexes for SARIMA Performance

```sql
-- Optimized indexes for SARIMA queries
CREATE INDEX idx_sales_product_date ON sales(product_id, sale_date);
CREATE INDEX idx_sales_month ON sales(sale_month);
CREATE INDEX idx_sales_amount_date ON sales(total_amount, sale_date);
CREATE INDEX idx_products_stock_reorder ON products(stock, reorder_level);
```

---

## 📈 SARIMA Data Flow Diagram

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   SALES TABLE   │───▶│  SARIMA ENGINE   │───▶│ PRODUCTS TABLE  │
│                 │    │                  │    │                 │
│ • total_amount  │    │ 1. Preprocessing │    │ • reorder_level │
│ • quantity_sold │    │ 2. Seasonality   │    │ • stock alerts  │
│ • sale_month    │    │ 3. Forecasting   │    │                 │
│ • product_id    │    │ 4. Confidence    │    │                 │
└─────────────────┘    │ 5. Recommendations │   └─────────────────┘
                       └──────────────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │   DASHBOARD OUTPUT  │
                    │                     │
                    │ • Forecast Charts   │
                    │ • Restock Alerts    │
                    │ • Seasonal Analysis │
                    │ • Performance Metrics │
                    └─────────────────────┘
```

---

## 🔧 Implementation Examples

### **Example 1: Monthly Revenue Forecasting**

```php
// Input from sales table
$monthlySales = Sale::selectRaw('sale_month, SUM(total_amount) as revenue')
    ->where('sale_month', '>=', '2024-01')
    ->groupBy('sale_month')
    ->orderBy('sale_month')
    ->get();

// SARIMA processing
$forecast = $this->generateEnhancedSarimaForecast($monthlySales, $seasonalityAnalysis);

// Output: 6-month revenue predictions with confidence intervals
```

### **Example 2: Product Stockout Prevention**

```php
// Input: Current stock + sales history
$currentStock = Product::find($productId)->stock;
$salesHistory = Sale::where('product_id', $productId)
    ->where('sale_date', '>=', Carbon::now()->subMonths(12))
    ->get();

// SARIMA analysis
$demandForecast = $this->generateProductDemandForecast($salesHistory);

// Output: Automated restock alert if stockout risk detected
if($demandForecast['days_until_stockout'] <= 7) {
    // Trigger urgent restock alert
}
```

### **Example 3: Seasonal Pattern Detection**

```php
// Input: 24 months of sales data
$seasonalAnalysis = $this->analyzeSeasonality($preprocessedData);

// Output: Peak months identification
$peakMonths = $seasonalAnalysis['peak_months']; // [11, 12, 1] (Nov, Dec, Jan)
$lowMonths = $seasonalAnalysis['low_months'];   // [6, 7, 8] (Jun, Jul, Aug)
```

---

## 📊 Key Performance Indicators (KPIs)

| **Metric**          | **Source Table**                         | **SARIMA Application**                |
| ------------------- | ---------------------------------------- | ------------------------------------- |
| Forecast Accuracy   | `sales.total_amount`                     | MAPE calculation vs predictions       |
| Stockout Prevention | `products.stock` + forecasts             | Days until stockout prediction        |
| Revenue Growth      | `sales.total_amount`                     | Trend analysis and future projections |
| Seasonal Variance   | `sales.sale_month`                       | Seasonal indices and patterns         |
| Inventory Turnover  | `products.stock` + `sales.quantity_sold` | Optimal reorder level calculation     |

---

## 🎯 Business Impact

1. **Predictive Inventory Management**: Prevents stockouts and overstock situations
2. **Seasonal Planning**: Optimizes inventory for peak and low seasons
3. **Revenue Forecasting**: Provides accurate financial planning capabilities
4. **Automated Decision Support**: Reduces manual inventory management effort
5. **Risk Mitigation**: Early warning system for potential inventory issues

This SARIMA implementation transforms raw transactional data into intelligent, automated inventory management decisions.
