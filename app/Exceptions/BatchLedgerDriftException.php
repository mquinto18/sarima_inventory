<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when products.stock (the cached total) says enough stock is
 * available, but the product_batches rows backing it couldn't actually
 * cover the requested deduction. Unlike InsufficientStockException, this is
 * never a normal business case - it means the cache and the batch ledger
 * have drifted out of sync, which should only happen if something wrote to
 * products.stock directly instead of through InventoryService.
 */
class BatchLedgerDriftException extends Exception
{
    public int $productId;
    public int $requested;
    public int $actuallyAvailable;

    public function __construct(int $productId, int $requested, int $actuallyAvailable)
    {
        $this->productId = $productId;
        $this->requested = $requested;
        $this->actuallyAvailable = $actuallyAvailable;

        parent::__construct(
            "Batch ledger drift on product #{$productId}: cache allowed {$requested} units, "
            . "but batches only had {$actuallyAvailable} remaining."
        );
    }
}
