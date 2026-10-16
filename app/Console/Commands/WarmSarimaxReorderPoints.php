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
    protected $description = 'Pre-fit the SARIMAX forecast (forecasted demand + reorder point) for every product with enough history and cache the result for a day. Run this out of band (not on a web request): each fit spawns a Python subprocess and takes a few seconds per product.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::all();
        $fitted = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $result = ProductController::warmSarimaxForecast($product->id);

            if ($result === null) {
                $skipped++;
            } else {
                $fitted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("SARIMAX reorder points fitted: {$fitted}. Skipped (insufficient history or Python unavailable): {$skipped}.");
        Log::info('WarmSarimaxReorderPoints: run complete', ['fitted' => $fitted, 'skipped' => $skipped]);
    }
}
