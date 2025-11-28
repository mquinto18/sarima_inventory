<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalancedSalesSeeder extends Seeder
{
    /**
     * Run the database seeds - Creates balanced sales data for all products.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->count() == 0) {
            $this->command->info('No products found. Please add products first.');
            return;
        }

        $this->command->info('Creating balanced historical sales data for all products...');

        // Clear existing sales for products with insufficient data
        $productsWithLowData = [];
        foreach ($products as $product) {
            $monthsCount = Sale::where('product_id', $product->id)
                ->distinct('sale_month')
                ->count('sale_month');
            
            if ($monthsCount < 6) {
                $productsWithLowData[] = $product;
                $this->command->info("Product '{$product->name}' has only {$monthsCount} months of data. Adding more...");
            }
        }

        // Generate sales data for the last 6 months for products with insufficient data
        foreach ($productsWithLowData as $product) {
            $baseQuantity = rand(15, 35); // Base sales quantity per month for this product
            
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                
                // Add seasonality and trend for realistic data
                $seasonalFactor = 1 + (sin($i * M_PI / 6) * 0.15); // Seasonal variation ±15%
                $monthsAgo = 5 - $i;
                $trendFactor = 1 + ($monthsAgo * 0.03); // Slight upward trend over time
                $randomFactor = 1 + (rand(-15, 15) / 100); // Random noise ±15%
                
                $monthlyQuantity = round($baseQuantity * $seasonalFactor * $trendFactor * $randomFactor);
                $monthlyQuantity = max(5, $monthlyQuantity); // At least 5 units per month
                
                // Create multiple sales transactions throughout the month
                $numTransactions = rand(4, 10);
                $quantityPerTransaction = max(1, round($monthlyQuantity / $numTransactions));
                
                for ($j = 0; $j < $numTransactions; $j++) {
                    $quantity = $quantityPerTransaction + rand(-2, 3);
                    $quantity = max(1, $quantity); // Ensure at least 1
                    
                    $saleDate = $month->copy()->addDays(rand(0, $month->daysInMonth - 1));
                    
                    // Check if sale already exists for this product on this date
                    $exists = Sale::where('product_id', $product->id)
                        ->whereDate('sale_date', $saleDate->toDateString())
                        ->exists();
                    
                    if (!$exists) {
                        Sale::create([
                            'product_id' => $product->id,
                            'quantity_sold' => $quantity,
                            'unit_price' => $product->price,
                            'total_amount' => $quantity * $product->price,
                            'sale_date' => $saleDate->toDateString(),
                            'sale_month' => $saleDate->format('Y-m')
                        ]);
                    }
                }
                
                $this->command->info("  ✓ Added sales for {$product->name} in {$month->format('Y-m')}");
            }
        }

        // Show summary
        $this->command->info("\n=== Sales Data Summary ===");
        foreach ($products as $product) {
            $monthsCount = Sale::where('product_id', $product->id)
                ->selectRaw('COUNT(DISTINCT sale_month) as months')
                ->value('months');
            
            $totalQty = Sale::where('product_id', $product->id)->sum('quantity_sold');
            
            $this->command->info("  {$product->name}: {$monthsCount} months, {$totalQty} units sold");
        }

        $totalSales = Sale::count();
        $this->command->info("\n✓ Successfully balanced sales data!");
        $this->command->info("✓ Total sales records: {$totalSales}");
        $this->command->info('✓ Forecast accuracy calculation now has sufficient data for all products!');
    }
}
