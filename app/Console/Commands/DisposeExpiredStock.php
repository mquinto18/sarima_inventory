<?php

namespace App\Console\Commands;

use App\Models\ProductBatch;
use App\Services\InventoryService;
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
    protected $description = 'Automatically write off leftover stock for batches that have been expired for at least one full day.';

    /**
     * Execute the console command.
     *
     * Operates on batches, not products: a product can have one expired
     * batch and one perfectly good one, and only the expired batch's
     * remaining quantity should be written off - see
     * InventoryService::disposeBatch() for how the race against a
     * concurrent POS sale draining this same batch is handled.
     */
    public function handle()
    {
        $expiredBatches = ProductBatch::with('product')
            ->available()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->subDay())
            ->orderBy('product_id')
            ->get();

        $disposed = [];
        $failed = [];

        foreach ($expiredBatches as $batch) {
            $product = $batch->product;
            $quantity = $batch->quantity_remaining;
            $expiryDate = $batch->expiry_date->format('Y-m-d');

            try {
                (new InventoryService())->disposeBatch(
                    $batch,
                    $quantity,
                    null,
                    "Auto-disposed: batch received {$batch->received_at->format('Y-m-d')} expired on {$expiryDate}, past the 1-day grace period"
                );

                $disposed[] = "{$product->name} ({$quantity} units, batch #{$batch->id})";
            } catch (\Throwable $e) {
                Log::error("DisposeExpiredStock: failed to auto-dispose batch #{$batch->id} for product #{$product->id} ({$product->name}): " . $e->getMessage());
                $failed[] = "{$product->name} (batch #{$batch->id})";
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
