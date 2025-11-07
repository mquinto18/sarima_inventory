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

        // Generate sales data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $salesInMonth = rand(10, 30); // Random number of sales per month

            for ($j = 0; $j < $salesInMonth; $j++) {
                $product = $products->random();
                $quantity = rand(1, 5);
                $saleDate = $month->copy()->addDays(rand(0, $month->daysInMonth - 1));

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

        $this->command->info('Sample sales data created successfully!');
    }
}
