<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProductController;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmSarimaxReorderPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:warm-sarimax-reorder-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-fit the SARIMAX forecast (forecasted demand + reorder point) and the SARIMAX backtest (for the accuracy metrics) for every product with enough history, caching both for a day. Run this out of band (not on a web request): each fit spawns a Python subprocess and takes a few seconds per product - two fits per product, so roughly double the runtime of warming forecasts alone.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::all();
        $forecastsFitted = 0;
        $forecastsSkipped = 0;
        $backtestsFitted = 0;
        $backtestsSkipped = 0;

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            if (ProductController::warmSarimaxForecast($product->id) === null) {
                $forecastsSkipped++;
            } else {
                $forecastsFitted++;
            }

            if (ProductController::warmSarimaxBacktest($product->id) === null) {
                $backtestsSkipped++;
            } else {
                $backtestsFitted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("SARIMAX forecasts fitted: {$forecastsFitted}. Skipped: {$forecastsSkipped}.");
        $this->info("SARIMAX backtests fitted: {$backtestsFitted}. Skipped: {$backtestsSkipped}.");
        Log::info('WarmSarimaxReorderPoints: run complete', [
            'forecasts_fitted' => $forecastsFitted,
            'forecasts_skipped' => $forecastsSkipped,
            'backtests_fitted' => $backtestsFitted,
            'backtests_skipped' => $backtestsSkipped,
        ]);
    }
}
