<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->count() == 0) {
            $this->command->info('No products found. Please add products first.');
            return;
        }

        $this->command->info('Creating historical sales data for forecast accuracy...');

        // Generate sales data for the last 6 months with realistic patterns
        foreach ($products as $product) {
            $baseQuantity = rand(800, 1200); // Higher base sales quantity per month for this product
            
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                // Make 3 and 2 months ago very low sales months
                if ($i === 3 || $i === 2) {
                    $monthlyQuantity = 10;
                } else {
                    // Add seasonality and trend
                    $seasonalFactor = 1 + (sin($i * M_PI / 6) * 0.2); // Seasonal variation ±20%
                    $trendFactor = 1 + ($i * 0.05); // Slight upward trend
                    $randomFactor = 1 + (rand(-10, 10) / 100); // Random noise ±10%
                    $monthlyQuantity = round($baseQuantity * $seasonalFactor * $trendFactor * $randomFactor);
                }
                // Create multiple sales transactions throughout the month
                $numTransactions = rand(3, 8);
                $quantityPerTransaction = max(1, round($monthlyQuantity / $numTransactions));
                for ($j = 0; $j < $numTransactions; $j++) {
                    $quantity = $quantityPerTransaction + rand(-2, 2);
                    $quantity = max(1, $quantity); // Ensure at least 1
                    $saleDate = $month->copy()->addDays(rand(0, $month->daysInMonth - 1));
                    Sale::create([
                        'product_id' => $product->id,
                        'quantity_sold' => $quantity,
                        'unit_price' => $product->price,
                        'total_amount' => $quantity * $product->price,
                        'sale_date' => $saleDate->toDateString(),
                        'month_year' => $saleDate->format('Y-m')
                    ]);
                }
                $this->command->info("Created sales for {$product->name} in {$month->format('Y-m')}: ~{$monthlyQuantity} units");
            }
        }

        $totalSales = Sale::count();
        $this->command->info("✓ Successfully created {$totalSales} sales records across 6 months!");
        $this->command->info('✓ Forecast accuracy calculation now has sufficient data!');
    }
}
