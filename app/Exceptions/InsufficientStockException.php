<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public int $available;
    public int $requested;
    public ?int $productId;

    public function __construct(int $available, int $requested, ?int $productId = null)
    {
        $this->available = $available;
        $this->requested = $requested;
        $this->productId = $productId;

        parent::__construct("Insufficient stock: requested {$requested}, only {$available} available.");
    }
}
