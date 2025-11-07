# SARIMA Database Architecture & Data Flow

## 🏗️ **Database Schema with SARIMA Integration**

### **Complete Database Structure:**

```sql
-- =====================================================
-- SARIMA INVENTORY MANAGEMENT DATABASE SCHEMA
-- =====================================================

-- 1. PRODUCTS TABLE - Inventory Master Data
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    stock INT NOT NULL DEFAULT 0,              -- 🎯 SARIMA MONITORS
    status ENUM('active', 'inactive') DEFAULT 'active',
    price DECIMAL(10,2) NOT NULL,             -- 🎯 REVENUE CALCULATIONS
    reorder_level INT DEFAULT 0,              -- 🎯 SARIMA UPDATES
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    -- SARIMA Optimization Indexes
    INDEX idx_sarima_inventory (stock, reorder_level, status),
    INDEX idx_sarima_category (category, status),
    INDEX idx_sarima_pricing (price, category)
);

-- 2. SALES TABLE - SARIMA Primary Data Source
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,      -- 🎯 PRODUCT LINKING
    quantity_sold INT NOT NULL,               -- 🎯 DEMAND ANALYSIS
    unit_price DECIMAL(10,2) NOT NULL,       -- 🎯 PRICING TRENDS
    total_amount DECIMAL(10,2) NOT NULL,     -- 🎯 PRIMARY SARIMA INPUT
    sale_date DATE NOT NULL,                 -- 🎯 TIME SERIES DIMENSION
    sale_month VARCHAR(7) NOT NULL,          -- 🎯 SARIMA GROUPING (YYYY-MM)
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    -- Foreign Key Relationships
    CONSTRAINT fk_sales_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE,

    -- SARIMA Performance Indexes
    INDEX idx_sarima_time_series (sale_month, total_amount),
    INDEX idx_sarima_product_analysis (product_id, sale_date),
    INDEX idx_sarima_revenue_trend (sale_date, total_amount),
    INDEX idx_sarima_seasonal_pattern (sale_month),
    INDEX idx_sarima_comprehensive (product_id, sale_month, quantity_sold, total_amount)
);

-- 3. USERS TABLE - System Access Control
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);
```

---

## 🔄 **SARIMA Data Flow Architecture**

### **Visual Data Flow Diagram:**

```
┌─────────────────────────────────────────────────────────────────┐
│                     SARIMA DATA FLOW PIPELINE                   │
└─────────────────────────────────────────────────────────────────┘

    📊 SALES TABLE                    🤖 SARIMA ALGORITHM                  🏪 PRODUCTS TABLE
┌─────────────────────┐           ┌─────────────────────────┐           ┌─────────────────────┐
│                     │           │                         │           │                     │
│ • sale_month        │──────────▶│ 1. Data Preprocessing   │           │ • stock             │
│ • total_amount      │           │    - Outlier removal    │           │ • reorder_level     │◀─┐
│ • quantity_sold     │           │    - Missing data fill  │           │ • status            │  │
│ • product_id        │           │    - Data smoothing     │           │                     │  │
│ • sale_date         │           │                         │           └─────────────────────┘  │
│                     │           │ 2. Seasonality Analysis │                                    │
└─────────────────────┘           │    - Peak/low months    │           📈 DASHBOARD OUTPUT      │
                                  │    - Trend direction    │           ┌─────────────────────┐  │
        ▲                         │    - Growth rate        │           │                     │  │
        │                         │                         │           │ • Revenue Forecasts │  │
        │ New Sales               │ 3. Forecast Generation  │           │ • Seasonal Analysis │  │
        │ Data Input              │    - 6-month predictions │           │ • Restock Alerts    │  │
        │                         │    - Confidence intervals│           │ • Risk Assessment   │  │
                                  │    - Trend decomposition │           │ • Performance Metrics │ │
┌─────────────────────┐           │                         │           │                     │  │
│   SALES RECORDING   │           │ 4. Product Demand       │           └─────────────────────┘  │
│                     │           │    - Individual forecasts│                                   │
│ • Manual Entry      │───────────│    - Stockout risk      │──────────────────────────────────┘
│ • POS Integration   │           │    - Days until empty   │           AUTOMATED UPDATES
│ • Bulk Import       │           │                         │
│                     │           │ 5. Recommendations     │
└─────────────────────┘           │    - Urgent restock     │
                                  │    - Monitor closely    │
                                  │    - Overstock warnings │
                                  │                         │
                                  │ 6. Performance Tracking │
                                  │    - Accuracy metrics   │
                                  │    - Model validation   │
                                  │                         │
                                  └─────────────────────────┘
```

---

## 📊 **Table Relationship and SARIMA Integration**

### **1. Sales → SARIMA → Products Workflow:**

```sql
-- STEP 1: Extract Time Series Data from Sales
SELECT
    sale_month,
    SUM(total_amount) as monthly_revenue,
    SUM(quantity_sold) as monthly_volume,
    COUNT(DISTINCT product_id) as product_diversity
FROM sales
WHERE sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 24 MONTH), '%Y-%m')
GROUP BY sale_month
ORDER BY sale_month;

-- STEP 2: Product-Specific Analysis
SELECT
    p.id as product_id,
    p.name as product_name,
    p.stock as current_stock,
    p.reorder_level as current_reorder_level,
    s.sale_month,
    SUM(s.quantity_sold) as monthly_demand,
    AVG(s.unit_price) as avg_price
FROM products p
LEFT JOIN sales s ON p.id = s.product_id
WHERE p.status = 'active'
    AND s.sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
GROUP BY p.id, p.name, p.stock, p.reorder_level, s.sale_month
ORDER BY p.id, s.sale_month;

-- STEP 3: Update Products with SARIMA Results
UPDATE products
SET reorder_level = CASE
    WHEN id = 1 THEN 50  -- SARIMA calculated optimal level
    WHEN id = 2 THEN 75  -- Based on demand forecast
    WHEN id = 3 THEN 25  -- Seasonal adjustment applied
    ELSE reorder_level
END
WHERE id IN (1, 2, 3);
```

### **2. Data Validation and Quality Checks:**

```sql
-- Check data completeness for SARIMA
SELECT
    'Sales Data Quality' as check_type,
    COUNT(*) as total_records,
    COUNT(DISTINCT sale_month) as months_covered,
    MIN(sale_date) as earliest_sale,
    MAX(sale_date) as latest_sale,
    COUNT(DISTINCT product_id) as products_with_sales
FROM sales;

-- Identify products needing attention
SELECT
    p.id,
    p.name,
    p.stock,
    p.reorder_level,
    COALESCE(recent_sales.monthly_avg, 0) as avg_monthly_sales,
    CASE
        WHEN p.stock <= p.reorder_level THEN 'URGENT'
        WHEN p.stock <= p.reorder_level * 1.5 THEN 'MONITOR'
        ELSE 'NORMAL'
    END as status
FROM products p
LEFT JOIN (
    SELECT
        product_id,
        AVG(quantity_sold) as monthly_avg
    FROM sales
    WHERE sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 MONTH), '%Y-%m')
    GROUP BY product_id
) recent_sales ON p.id = recent_sales.product_id
WHERE p.status = 'active'
ORDER BY p.stock / NULLIF(p.reorder_level, 0) ASC;
```

---

## 🎯 **SARIMA Algorithm Database Queries**

### **Key Queries Used by SARIMA System:**

#### **1. Monthly Sales Aggregation (Core Time Series):**

```sql
-- SalesController::getMonthlySalesData()
SELECT
    sale_month,
    SUM(quantity_sold) as total_quantity,
    SUM(total_amount) as total_revenue,
    COUNT(DISTINCT product_id) as unique_products,
    AVG(total_amount) as avg_transaction_value,
    STDDEV(total_amount) as revenue_volatility
FROM sales
WHERE sale_month BETWEEN :start_month AND :end_month
GROUP BY sale_month
ORDER BY sale_month;
```

#### **2. Seasonal Pattern Detection:**

```sql
-- SalesController::analyzeSeasonality()
SELECT
    MONTH(sale_date) as month_number,
    MONTHNAME(sale_date) as month_name,
    AVG(total_amount) as avg_monthly_revenue,
    SUM(total_amount) as total_monthly_revenue,
    COUNT(*) as transaction_count,
    STDDEV(total_amount) as monthly_volatility
FROM sales
WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
GROUP BY MONTH(sale_date), MONTHNAME(sale_date)
ORDER BY month_number;
```

#### **3. Product Demand Forecasting:**

```sql
-- SalesController::generateProductDemandForecast()
SELECT
    p.id,
    p.name,
    p.stock,
    p.reorder_level,
    p.category,
    s.sale_month,
    SUM(s.quantity_sold) as monthly_demand,
    AVG(s.unit_price) as avg_price,
    SUM(s.total_amount) as monthly_revenue
FROM products p
INNER JOIN sales s ON p.id = s.product_id
WHERE p.status = 'active'
    AND s.sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
GROUP BY p.id, p.name, p.stock, p.reorder_level, p.category, s.sale_month
ORDER BY p.id, s.sale_month;
```

#### **4. Risk Assessment Query:**

```sql
-- Identify high-risk products for immediate attention
SELECT
    p.id,
    p.name,
    p.stock,
    p.reorder_level,
    recent.avg_monthly_demand,
    ROUND(p.stock / NULLIF(recent.avg_monthly_demand, 0), 1) as months_of_stock,
    CASE
        WHEN p.stock / NULLIF(recent.avg_monthly_demand, 0) < 0.5 THEN 'CRITICAL'
        WHEN p.stock / NULLIF(recent.avg_monthly_demand, 0) < 1.0 THEN 'HIGH'
        WHEN p.stock / NULLIF(recent.avg_monthly_demand, 0) < 2.0 THEN 'MEDIUM'
        ELSE 'LOW'
    END as risk_level
FROM products p
LEFT JOIN (
    SELECT
        product_id,
        AVG(quantity_sold) as avg_monthly_demand
    FROM sales
    WHERE sale_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 MONTH), '%Y-%m')
    GROUP BY product_id
) recent ON p.id = recent.product_id
WHERE p.status = 'active'
ORDER BY risk_level DESC, months_of_stock ASC;
```

---

## 🔧 **Database Optimization for SARIMA Performance**

### **1. Essential Indexes:**

```sql
-- Primary SARIMA performance indexes
CREATE INDEX idx_sarima_time_series ON sales (sale_month, total_amount);
CREATE INDEX idx_sarima_product_monthly ON sales (product_id, sale_month);
CREATE INDEX idx_sarima_date_revenue ON sales (sale_date, total_amount);
CREATE INDEX idx_sarima_seasonal ON sales (MONTH(sale_date), total_amount);

-- Inventory management indexes
CREATE INDEX idx_inventory_status ON products (status, stock, reorder_level);
CREATE INDEX idx_product_category ON products (category, status);

-- Composite indexes for complex SARIMA queries
CREATE INDEX idx_sarima_comprehensive ON sales (product_id, sale_month, quantity_sold, total_amount);
```

### **2. Query Optimization:**

```sql
-- Optimized query plan for monthly aggregation
EXPLAIN FORMAT=JSON
SELECT sale_month, SUM(total_amount) as revenue
FROM sales
WHERE sale_month >= '2023-01'
GROUP BY sale_month
ORDER BY sale_month;

-- Expected: Uses idx_sarima_time_series, no file sorting needed
```

### **3. Data Partitioning (For Large Datasets):**

```sql
-- Partition sales table by year for better performance
CREATE TABLE sales_partitioned (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_sold INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    sale_date DATE NOT NULL,
    sale_month VARCHAR(7) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id, sale_date)
)
PARTITION BY RANGE (YEAR(sale_date)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## 📈 **Data Quality and Maintenance**

### **1. Data Quality Checks:**

```sql
-- Check for missing months in sales data
SELECT
    expected_months.month_year,
    COALESCE(actual_sales.total_revenue, 0) as actual_revenue,
    CASE
        WHEN actual_sales.sale_month IS NULL THEN 'MISSING DATA'
        WHEN actual_sales.total_revenue = 0 THEN 'NO SALES'
        ELSE 'DATA AVAILABLE'
    END as data_status
FROM (
    -- Generate all months for the last 24 months
    SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL seq MONTH), '%Y-%m') as month_year
    FROM (
        SELECT 0 as seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
        SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION
        SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION
        SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23
    ) seq_table
) expected_months
LEFT JOIN (
    SELECT sale_month, SUM(total_amount) as total_revenue
    FROM sales
    GROUP BY sale_month
) actual_sales ON expected_months.month_year = actual_sales.sale_month
ORDER BY expected_months.month_year;
```

### **2. Automated Data Cleanup:**

```sql
-- Remove outliers that could skew SARIMA predictions
DELETE FROM sales
WHERE total_amount > (
    SELECT avg_amount + (3 * stddev_amount)
    FROM (
        SELECT
            AVG(total_amount) as avg_amount,
            STDDEV(total_amount) as stddev_amount
        FROM sales
    ) stats
)
AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY);  -- Only recent outliers
```

This database architecture ensures optimal performance for SARIMA algorithm while maintaining data integrity and providing comprehensive inventory management capabilities.
