<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\SalesController;
use ReflectionClass;

echo "=== TESTING SARIMA FORECAST DATA ===\n\n";

$controller = new SalesController();

// Get monthly sales
$monthlySales = $controller->getMonthlySalesData();
echo "1. Monthly Sales Data Points: " . $monthlySales->count() . "\n";

// Get preprocessed data
$reflection = new ReflectionClass($controller);
$preprocessMethod = $reflection->getMethod('preprocessSalesData');
$preprocessMethod->setAccessible(true);
$preprocessed = $preprocessMethod->invoke($controller, $monthlySales);

echo "2. Preprocessed Data Points: " . count($preprocessed) . "\n";

// Count non-zero months
$nonZeroCount = 0;
$nonZeroData = [];
foreach ($preprocessed as $month => $data) {
    if ($data['revenue'] > 0) {
        $nonZeroCount++;
        $nonZeroData[$month] = $data['revenue'];
    }
}
echo "3. Months with Sales Data: {$nonZeroCount}\n";
echo "\nNon-zero revenue months:\n";
foreach ($nonZeroData as $month => $revenue) {
    echo "   {$month}: ₱" . number_format($revenue, 2) . "\n";
}

// Test seasonality analysis
$seasonalityMethod = $reflection->getMethod('analyzeSeasonality');
$seasonalityMethod->setAccessible(true);
$seasonality = $seasonalityMethod->invoke($controller, $preprocessed);

echo "\n4. Seasonality Analysis:\n";
echo "   Seasonal indices count: " . count($seasonality['seasonal_indices']) . "\n";
echo "   Trend direction: " . $seasonality['trend_direction'] . "\n";

// Test forecast generation
$forecastMethod = $reflection->getMethod('generateEnhancedSarimaForecast');
$forecastMethod->setAccessible(true);
$forecast = $forecastMethod->invoke($controller, $preprocessed, $seasonality);

echo "\n5. Forecast Generation:\n";
echo "   Historical months: " . count($forecast['months']) . "\n";
echo "   Historical revenue count: " . count($forecast['historical']) . "\n";
echo "   Predicted months: " . count($forecast['predicted']) . "\n";

echo "\n6. Historical Data (should match sales):\n";
if (!empty($forecast['months'])) {
    foreach ($forecast['months'] as $i => $month) {
        $revenue = $forecast['historical'][$i] ?? 0;
        echo "   {$month}: ₱" . number_format($revenue, 2) . "\n";
    }
} else {
    echo "   ⚠️ WARNING: No historical data in forecast!\n";
}

echo "\n7. Predicted Data (next 6 months):\n";
if (!empty($forecast['predicted'])) {
    foreach ($forecast['predicted'] as $month => $prediction) {
        echo "   {$month}: ₱" . number_format($prediction, 2) . "\n";
    }
} else {
    echo "   ⚠️ WARNING: No predictions generated!\n";
}

echo "\n8. Confidence Intervals:\n";
if (!empty($forecast['confidence_intervals'])) {
    foreach ($forecast['confidence_intervals'] as $month => $interval) {
        echo "   {$month}: ₱" . number_format($interval['lower'], 0) . " - ₱" . number_format($interval['upper'], 0) . "\n";
    }
} else {
    echo "   ⚠️ WARNING: No confidence intervals!\n";
}

echo "\n=== TEST COMPLETE ===\n";
