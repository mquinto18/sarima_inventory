<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ReplenishmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckReplenishment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-replenishment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan products at/below reorder point and auto-generate + email purchase orders to primary suppliers (straight-through processing).';

    /**
     * Execute the console command.
     */
    public function handle(ReplenishmentService $replenishment)
    {
        if (!Setting::get('stp_enabled', false)) {
            $this->info('STP disabled, exiting.');
            Log::info('CheckReplenishment: STP disabled, skipping run.');
            return;
        }

        $skippedNoSupplier = [];
        $cappedProducts = [];
        $bySupplier = [];

        foreach (Product::all() as $product) {
            $result = $replenishment->evaluateForOrder($product);

            if (!$result['eligible']) {
                if ($result['reason'] === 'no_supplier') {
                    $skippedNoSupplier[] = $product->name;
                }
                continue;
            }

            if ($result['capped']) {
                $cappedProducts[] = $product->name;
            }

            $supplierId = $result['supplier']->id;

            if (!isset($bySupplier[$supplierId])) {
                $bySupplier[$supplierId] = [
                    'supplier' => $result['supplier'],
                    'lines' => [],
                ];
            }

            $bySupplier[$supplierId]['lines'][] = $result['line'];
        }

        $createdPOs = [];
        $sentPOs = [];
        $failedPOs = [];

        foreach ($bySupplier as $supplierId => $group) {
            if (empty($group['lines'])) {
                continue;
            }

            // PO creation and the supplier email both happen inside
            // createAndSendPO(); a send failure there is logged internally
            // and leaves the PO as 'draft' rather than throwing, so we tell
            // success from failure via the returned PO's status. A null
            // return means every line in this group got claimed by a
            // concurrent run (e.g. an event-driven trigger) between this
            // command's evaluate pass and its own lock acquisition - nothing
            // left to create here.
            $purchaseOrder = $replenishment->createAndSendPO($group['supplier'], $group['lines']);

            if (!$purchaseOrder) {
                continue;
            }

            $createdPOs[] = $purchaseOrder->po_number;

            if ($purchaseOrder->status === 'sent') {
                $sentPOs[] = $purchaseOrder->po_number;
            } else {
                $failedPOs[] = $purchaseOrder->po_number;
            }
        }

        $summary = [
            'pos_created' => $createdPOs,
            'pos_sent' => $sentPOs,
            'pos_failed_to_send' => $failedPOs,
            'skipped_no_supplier' => $skippedNoSupplier,
            'capped_products' => $cappedProducts,
        ];

        $this->info('Replenishment check complete.');
        $this->info('POs created: ' . (count($createdPOs) ?: 'none') . (count($createdPOs) ? ' (' . implode(', ', $createdPOs) . ')' : ''));
        $this->info('POs sent: ' . (count($sentPOs) ?: 'none') . (count($sentPOs) ? ' (' . implode(', ', $sentPOs) . ')' : ''));

        if (!empty($failedPOs)) {
            $this->warn('POs created but failed to send (left as draft): ' . implode(', ', $failedPOs));
        }

        if (!empty($skippedNoSupplier)) {
            $this->warn('Skipped (no primary supplier): ' . implode(', ', $skippedNoSupplier));
        }

        if (!empty($cappedProducts)) {
            $this->warn('Capped by safety limits: ' . implode(', ', $cappedProducts));
        }

        Log::info('CheckReplenishment: run summary', $summary);
    }
}
