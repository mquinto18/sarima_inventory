<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when the cash tendered is less than the server-computed total.
 *
 * Raised inside the checkout DB transaction so Laravel rolls the whole thing
 * back: no Sale rows, no stock deducted, nothing half-charged.
 */
class ShortPaymentException extends Exception
{
    public float $total;
    public float $tendered;

    public function __construct(float $total, float $tendered)
    {
        $this->total = $total;
        $this->tendered = $tendered;

        parent::__construct("Short payment: tendered {$tendered}, total {$total}.");
    }
}
