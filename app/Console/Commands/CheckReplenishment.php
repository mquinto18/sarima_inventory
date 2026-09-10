<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProductController;
use App\Mail\PurchaseOrderMail;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public function handle()
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
            $reorderPoint = ProductController::calculateDynamicReorderPoint($product->id);

            if ($product->stock > $reorderPoint) {
                continue;
            }

            // Open-PO guard: skip if this product already has an order in flight.
            $hasOpenOrder = PurchaseOrderItem::where('product_id', $product->id)
                ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', ['draft', 'sent', 'confirmed']))
                ->exists();

            if ($hasOpenOrder) {
                Log::info("CheckReplenishment: skipping product #{$product->id} ({$product->name}) - already has an open PO.");
                continue;
            }

            $supplier = $product->primarySupplier();

            if (!$supplier) {
                $skippedNoSupplier[] = $product->name;
                Log::warning("CheckReplenishment: product #{$product->id} ({$product->name}) is at/below reorder point but has no primary supplier.");
                continue;
            }

            $link = ProductSupplier::where('product_id', $product->id)
                ->where('supplier_id', $supplier->id)
                ->where('is_primary', true)
                ->first();

            if (!$link) {
                $skippedNoSupplier[] = $product->name;
                Log::warning("CheckReplenishment: product #{$product->id} ({$product->name}) has no primary product_supplier link despite primarySupplier() returning a supplier.");
                continue;
            }

            $forecastedDemand = ProductController::getForecastedDemand($product->id);
            $qty = (int) round(max($forecastedDemand, $reorderPoint - $product->stock));

            if ($qty <= 0) {
                continue;
            }

            $maxQty = (int) Setting::get('stp_max_qty_per_product', 500);
            $maxValue = (float) Setting::get('stp_max_order_value', 5000);

            $capNotes = [];

            if ($qty > $maxQty) {
                $qty = $maxQty;
                $capNotes[] = 'capped_qty';
            }

            $orderValue = $qty * $link->cost_price;

            if ($orderValue > $maxValue) {
                $qty = $link->cost_price > 0 ? (int) floor($maxValue / $link->cost_price) : 0;
                $capNotes[] = 'capped_value';
            }

            if ($qty <= 0) {
                Log::info("CheckReplenishment: skipped_cap - product #{$product->id} ({$product->name}) quantity fell to 0 after applying safety caps.");
                continue;
            }

            if (!empty($capNotes)) {
                $cappedProducts[] = "{$product->name} (" . implode(', ', $capNotes) . ')';
            }

            if (!isset($bySupplier[$supplier->id])) {
                $bySupplier[$supplier->id] = [
                    'supplier' => $supplier,
                    'lines' => [],
                ];
            }

            $bySupplier[$supplier->id]['lines'][] = [
                'product' => $product,
                'qty' => $qty,
                'cost_price' => $link->cost_price,
                'lead_time_days' => $link->lead_time_days,
            ];
        }

        $createdPOs = [];
        $sentPOs = [];
        $failedPOs = [];

        foreach ($bySupplier as $supplierId => $group) {
            if (empty($group['lines'])) {
                continue;
            }

            /** @var \App\Models\Supplier $supplier */
            $supplier = $group['supplier'];

            $purchaseOrder = DB::transaction(function () use ($supplier, $group) {
                $poNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad(
                    (string) (PurchaseOrder::whereDate('created_at', now())->count() + 1),
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                $leadTimeDays = null;
                foreach ($group['lines'] as $line) {
                    if (!empty($line['lead_time_days'])) {
                        $leadTimeDays = $line['lead_time_days'];
                        break;
                    }
                }
                $leadTimeDays = $leadTimeDays ?? $supplier->lead_time_days ?? Setting::get('default_lead_time_days', 7);

                $purchaseOrder = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $supplier->id,
                    'status' => 'draft',
                    'is_auto_generated' => true,
                    'total_value' => 0,
                    'expected_delivery_date' => now()->addDays($leadTimeDays),
                    'created_by' => null,
                ]);

                $totalValue = 0;

                foreach ($group['lines'] as $line) {
                    $subtotal = $line['qty'] * $line['cost_price'];
                    $totalValue += $subtotal;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $line['product']->id,
                        'quantity_ordered' => $line['qty'],
                        'quantity_received' => 0,
                        'unit_cost' => $line['cost_price'],
                        'subtotal' => $subtotal,
                    ]);
                }

                $purchaseOrder->update(['total_value' => $totalValue]);

                return $purchaseOrder;
            });

            $createdPOs[] = $purchaseOrder->po_number;

            // PDF generation + email is intentionally OUTSIDE the DB transaction
            // above, so a mail/PDF failure never rolls back the already-decided,
            // already-capped PO.
            try {
                $purchaseOrder->load('items.product', 'supplier');

                $pdf = Pdf::loadView('pdf.purchase_order', ['purchaseOrder' => $purchaseOrder]);
                $path = "purchase_orders/{$purchaseOrder->po_number}.pdf";
                Storage::put($path, $pdf->output());
                $purchaseOrder->update(['pdf_path' => $path]);

                Mail::to($supplier->email)->send(new PurchaseOrderMail($purchaseOrder));

                $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);
                $sentPOs[] = $purchaseOrder->po_number;
            } catch (\Throwable $e) {
                Log::error("Failed to send PO {$purchaseOrder->po_number}: " . $e->getMessage());
                $failedPOs[] = $purchaseOrder->po_number;
                // Leave status as 'draft' so it's visibly not-yet-sent for manual follow-up.
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
