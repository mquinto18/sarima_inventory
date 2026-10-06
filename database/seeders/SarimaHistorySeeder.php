<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates 24 months of coherent sales history so the SARIMA model has
 * something real to learn from.
 *
 * Run with:  php artisan db:seed --class=SarimaHistorySeeder
 *
 * Why 24: python/sarima_forecast.py fits SARIMAX(1,1,1)(1,1,1,12). Seasonal
 * differencing at s=12 consumes 12 observations and d=1 consumes one more, so
 * anything under two full cycles leaves almost nothing to estimate from. With
 * the previous 12-real-plus-12-zero series the model returned confidence
 * intervals 148-412% of the forecast, with negative lower bounds.
 *
 * Two guarantees this seeder must keep:
 *
 *  1. Real POS sales are never touched. `pos_transaction_id IS NOT NULL` is an
 *     exact predicate for them, and those rows are the only ones with a
 *     stock_movements entry — deleting them would orphan the audit ledger.
 *     Generated rows deliberately leave that column NULL so the predicate stays
 *     true for future runs.
 *
 *  2. Stock is never mutated. These are historical records; deducting today's
 *     stock for a sale dated 2024 would corrupt live inventory. Consistent with
 *     BackfillRecentSalesSeeder / BackfillAnnualSalesSeeder, which also only
 *     write Sale rows.
 */
class SarimaHistorySeeder extends Seeder
{
    /** Two full seasonal cycles at s=12. */
    private const MONTHS = 24;

    /**
     * Per-category demand by calendar month (index 0 = January).
     *
     * The same profile is applied in both cycles, which is the entire point: a
     * pattern that repeats annually is the only thing the seasonal term can
     * learn. Shapes follow a Philippine pharmacy — respiratory lines climbing
     * through the wet months, vitamins spiking in January.
     */
    private const SEASONAL_PROFILES = [
        'Medicine' => [1.05, 0.95, 0.92, 0.90, 0.98, 1.12, 1.20, 1.18, 1.14, 1.08, 1.02, 1.10],
        'Vitamins & Supplements' => [1.30, 1.10, 0.98, 0.92, 0.90, 0.95, 1.02, 1.05, 1.04, 1.00, 1.02, 1.12],
        'Personal Care' => [1.06, 0.98, 0.96, 1.00, 1.04, 1.02, 0.98, 0.96, 0.98, 1.02, 1.08, 1.18],
        'Medical Supplies' => [1.02, 0.96, 0.94, 0.96, 1.00, 1.08, 1.14, 1.12, 1.08, 1.04, 1.00, 1.06],
    ];

    /** Fallback for any category without an explicit profile. */
    private const DEFAULT_PROFILE = [1.04, 0.98, 0.95, 0.94, 0.99, 1.05, 1.10, 1.09, 1.06, 1.02, 1.01, 1.08];

    /**
     * Monthly unit baseline per velocity class.
     *
     * Calibrated against the real price list for a small neighbourhood pharmacy:
     * roughly ₱3,000 a day / ₱90,000 a month over ~50 units a day. The figures
     * are a little under the flat arithmetic because the trend and seasonal
     * factors above lift the later months another ~11%.
     */
    private const VELOCITY_BASE = ['fast' => 43, 'medium' => 17, 'slow' => 6];

    public function run(): void
    {
        // Deterministic: re-running produces the same history rather than a
        // different one, so the forecast does not move for no reason.
        mt_srand(20260917);

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found — nothing to generate.');
            return;
        }

        $start = Carbon::now()->subMonths(self::MONTHS - 1)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $removed = Sale::whereNull('pos_transaction_id')
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->delete();

        $this->command->info("Removed {$removed} previously seeded sales in the window (real POS sales untouched).");

        $rows = [];
        $now = now();

        foreach ($products as $product) {
            $profile = self::SEASONAL_PROFILES[trim((string) $product->category)] ?? self::DEFAULT_PROFILE;
            $base = self::VELOCITY_BASE[$this->velocityFor($product)];

            for ($i = self::MONTHS - 1; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i)->startOfMonth();
                $monthsElapsed = self::MONTHS - 1 - $i;

                $seasonal = $profile[$month->month - 1];
                $trend = 1 + ($monthsElapsed * 0.005);   // ~6% a year
                $noise = 1 + (mt_rand(-6, 6) / 100);   // kept below the seasonal amplitude

                $monthlyQuantity = max(3, (int) round($base * $seasonal * $trend * $noise));

                // Never date a sale in the future.
                $lastDay = $month->isCurrentMonth()
                    ? min($month->daysInMonth, Carbon::now()->day)
                    : $month->daysInMonth;

                $transactions = min(mt_rand(4, 10), $monthlyQuantity);

                foreach ($this->splitQuantity($monthlyQuantity, $transactions) as $quantity) {
                    $saleDate = $month->copy()->addDays(mt_rand(0, max(0, $lastDay - 1)));

                    $rows[] = [
                        'product_id' => $product->id,
                        'quantity_sold' => $quantity,
                        'unit_price' => $product->price,
                        'total_amount' => round($quantity * (float) $product->price, 2),
                        'sale_date' => $saleDate->toDateString(),
                        'month_year' => $month->format('Y-m'),
                        // Left NULL on purpose — this is what marks a row as
                        // generated rather than a real POS sale.
                        'pos_transaction_id' => null,
                        'user_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Chunked insert: one row at a time across 63 products x 24 months is
        // tens of thousands of queries.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('sales')->insert($chunk);
        }

        $this->command->info(sprintf(
            '✓ Generated %s sales rows across %d products over %d months (%s to %s).',
            number_format(count($rows)),
            $products->count(),
            self::MONTHS,
            $start->format('Y-m'),
            Carbon::now()->format('Y-m')
        ));
    }

    /**
     * Split a month's units across transactions so the parts sum **exactly** to
     * the intended total.
     *
     * The obvious approach — pick a per-transaction size and jitter each one —
     * multiplies that jitter by the transaction count. Measured, it moved a
     * slow mover's realised monthly total by up to 169% of the intended figure,
     * which completely buried the ±15% seasonal signal this data exists to
     * carry. Partitioning keeps the seasonality intact while still giving
     * transactions realistically uneven sizes.
     *
     * @return list<int>
     */
    private function splitQuantity(int $total, int $parts): array
    {
        $parts = max(1, min($parts, $total));
        $out = [];
        $remaining = $total;

        for ($i = 0; $i < $parts; $i++) {
            $slotsLeft = $parts - $i;

            if ($slotsLeft === 1) {
                $out[] = $remaining;
                break;
            }

            // Leave at least 1 unit for every remaining transaction.
            $maxHere = $remaining - ($slotsLeft - 1);
            $average = intdiv($remaining, $slotsLeft);
            $spread = max(1, (int) round($average * 0.35));

            $quantity = $average + mt_rand(-$spread, $spread);
            $out[] = max(1, min($quantity, $maxHere));
            $remaining -= end($out);
        }

        return $out;
    }

    /**
     * Stable velocity class per product, so a given product is always a fast or
     * slow mover rather than changing character between runs. Cheap products
     * lean fast, expensive ones slow, which is how a pharmacy actually sells.
     */
    private function velocityFor(Product $product): string
    {
        $price = (float) $product->price;

        if ($price <= 20) {
            $candidates = ['fast', 'fast', 'medium'];
        } elseif ($price <= 100) {
            $candidates = ['medium', 'medium', 'slow'];
        } else {
            $candidates = ['slow', 'slow', 'medium'];
        }

        return $candidates[$product->id % count($candidates)];
    }
}
