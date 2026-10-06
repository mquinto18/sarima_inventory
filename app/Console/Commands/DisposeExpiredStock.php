<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProductController;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DisposeExpiredStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispose-expired-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically write off leftover stock for products that have been expired for at least one full day.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->subDay())
            ->where('stock', '>', 0)
            ->get();

        $disposed = [];
        $failed = [];

        foreach ($products as $product) {
            $expiryDate = $product->expiry_date->format('Y-m-d');
            $quantity = $product->stock;

            try {
                ProductController::writeOffExpiredStock(
                    $product,
                    $quantity,
                    null,
                    "Auto-disposed: expired on {$expiryDate}, past the 1-day grace period"
                );

                $disposed[] = "{$product->name} ({$quantity} units)";
            } catch (\Throwable $e) {
                Log::error("DisposeExpiredStock: failed to auto-dispose product #{$product->id} ({$product->name}): " . $e->getMessage());
                $failed[] = $product->name;
            }
        }

        $summary = [
            'disposed' => $disposed,
            'failed' => $failed,
        ];

        $this->info('Expired stock disposal complete.');
        $this->info('Disposed: ' . (count($disposed) ?: 'none') . (count($disposed) ? ' (' . implode(', ', $disposed) . ')' : ''));

        if (!empty($failed)) {
            $this->warn('Failed to dispose: ' . implode(', ', $failed));
        }

        Log::info('DisposeExpiredStock: run summary', $summary);
    }
}
