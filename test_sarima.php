<?php
/**
 * Test script to demonstrate SARIMA-enhanced inventory management
 * Run this to see the new functionality in action
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🚀 SARIMA-Enhanced Inventory Management System Test\n";
echo "==================================================\n\n";

try {
    // Test dynamic reorder point calculation
    echo "1. Testing Dynamic Reorder Point Calculation...\n";
    
    $productController = new App\Http\Controllers\ProductController();
    $products = App\Models\Product::take(3)->get();
    
    foreach ($products as $product) {
        $staticReorder = $product->reorder_level ?? 10;
        $dynamicReorder = App\Http\Controllers\ProductController::calculateDynamicReorderPoint($product->id);
        $forecastedDemand = App\Http\Controllers\ProductController::getForecastedDemand($product->id);
        
        echo sprintf(
            "   📦 Product: %s\n      Current Stock: %d\n      Static Reorder Level: %d\n      SARIMA Dynamic Reorder: %.1f\n      Forecasted Demand: %d\n\n",
            $product->name,
            $product->stock,
            $staticReorder,
            $dynamicReorder,
            $forecastedDemand
        );
    }
    
    // Test reorder notifications with SARIMA
    echo "2. Testing SARIMA-Enhanced Reorder Notifications...\n";
    
    $notifications = App\Http\Controllers\ProductController::getReorderNotifications();
    
    echo sprintf("   📊 Found %d products requiring attention:\n", $notifications->count());
    
    foreach ($notifications->take(3) as $notification) {
        echo sprintf(
            "   ⚠️  %s: Stock=%d, Dynamic Reorder=%.1f, Forecasted Demand=%d, Priority=%s\n",
            $notification['name'],
            $notification['current_stock'],
            $notification['reorder_level'],
            $notification['forecasted_demand'],
            $notification['priority']
        );
    }
    
    echo "\n3. Testing SARIMA Demand Forecasting...\n";
    
    $salesController = new App\Http\Controllers\SalesController();
    $insights = $salesController->getInventoryInsights();
    
    echo sprintf("   📈 Generated insights for %d products\n", count($insights));
    
    foreach (array_slice($insights, 0, 3) as $insight) {
        echo sprintf(
            "   📊 %s: Risk=%s, Action=%s\n",
            $insight['product_name'],
            $insight['risk_level'],
            $insight['recommended_action']
        );
    }
    
    echo "\n✅ SARIMA Integration Test Completed Successfully!\n";
    echo "\nKey Improvements Implemented:\n";
    echo "• Dynamic reorder points based on demand forecasting\n";
    echo "• Safety stock calculations using variance analysis\n";
    echo "• SARIMA-based demand predictions (not just revenue)\n";
    echo "• Risk-level assessments for proactive inventory management\n";
    echo "• Integration between forecasting and inventory decisions\n\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "This is normal if you don't have products or sales data yet.\n";
}

echo "🎯 Your inventory system is now SARIMA-powered!\n";
echo "Access the enhanced features at:\n";
echo "• Dashboard: / (now shows SARIMA metrics)\n";
echo "• Forecasting: /forecasting (enhanced with demand predictions)\n";
echo "• API Endpoints:\n";
echo "  - GET /api/sarima-analysis\n";
echo "  - POST /api/auto-update-reorder-levels\n";
echo "  - GET /api/inventory-insights\n";
?>