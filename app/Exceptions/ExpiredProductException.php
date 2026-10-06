<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a POS checkout line contains a product whose expiry_date has
 * already passed. Raised inside the checkout DB transaction so Laravel
 * rolls the whole thing back: no Sale rows, no stock deducted.
 */
class ExpiredProductException extends Exception
{
    public int $productId;
    public string $productName;
    public string $expiryDate;

    public function __construct(int $productId, string $productName, string $expiryDate)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->expiryDate = $expiryDate;

        parent::__construct("Cannot sell {$productName}: expired on {$expiryDate}.");
    }
}
