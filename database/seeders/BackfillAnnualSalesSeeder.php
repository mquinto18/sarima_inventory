<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

/**
 * Extends sales history back to a full 12 real calendar months per product.
 *
 * BackfillRecentSalesSeeder only fills the last 6 months (for
 * calculateForecastAccuracy()). SARIMA's seasonal component (s=12) needs a
 * full year of real months to have any seasonal pattern to anchor on —
 * with only 6 real months, forecasts several steps out extrapolate wildly
 * instead of following a yearly cycle. This seeder fills months 7-12
 * (i.e. the 7th through 12th month before now), using the same
 * seasonal/trend/random approach as BackfillRecentSalesSeeder so the two
 * halves of the year read as one continuous series.
 */
class BackfillAnnualSalesSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->info('No products found.');
            return;
        }

        foreach ($products as $product) {
            $baseQuantity = $this->historicalAverageMonthlyQuantity($product->id);

            for ($i = 11; $i >= 6; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthKey = $month->format('Y-m');

                $alreadyHasData = Sale::where('product_id', $product->id)
                    ->whereYear('sale_date', $month->year)
                    ->whereMonth('sale_date', $month->month)
                    ->exists();

                if ($alreadyHasData) {
                    continue;
                }

                $seasonalFactor = 1 + (sin($i * M_PI / 6) * 0.15);
                $trendFactor = 1 + ((11 - $i) * 0.03);
                $randomFactor = 1 + (rand(-15, 15) / 100);

                $monthlyQuantity = max(5, round($baseQuantity * $seasonalFactor * $trendFactor * $randomFactor));

                $numTransactions = rand(4, 8);
                $quantityPerTransaction = max(1, (int) round($monthlyQuantity / $numTransactions));

                $daysInMonth = $month->daysInMonth;

                for ($j = 0; $j < $numTransactions; $j++) {
                    $quantity = max(1, $quantityPerTransaction + rand(-2, 3));
                    $saleDate = $month->copy()->startOfMonth()->addDays(rand(0, max(0, $daysInMonth - 1)));

                    Sale::create([
                        'product_id' => $product->id,
                        'quantity_sold' => $quantity,
                        'unit_price' => $product->price,
                        'total_amount' => $quantity * $product->price,
                        'sale_date' => $saleDate->toDateString(),
                        'month_year' => $monthKey,
                    ]);
                }

                $this->command->info("  ✓ Backfilled {$product->name} for {$monthKey} ({$numTransactions} transactions)");
            }
        }

        $this->command->info('✓ Annual sales history backfilled for all products.');
    }

    /**
     * Average units sold per calendar month across this product's existing
     * history, so newly backfilled months look continuous with real data
     * instead of following an arbitrary made-up quantity.
     */
    private function historicalAverageMonthlyQuantity(int $productId): int
    {
        $monthly = Sale::where('product_id', $productId)
            ->selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as month, SUM(quantity_sold) as qty')
            ->groupBy('month')
            ->pluck('qty');

        if ($monthly->isEmpty()) {
            return rand(15, 35);
        }

        return max(5, (int) round($monthly->avg()));
    }
}
