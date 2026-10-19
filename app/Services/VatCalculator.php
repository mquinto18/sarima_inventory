<?php

namespace App\Services;

/**
 * Computes the Philippine VAT breakdown and the Senior Citizen/PWD discount
 * for a POS cart, line by line. Shelf prices are VAT-inclusive throughout -
 * VAT is only ever broken out of a price for display/reporting, never added
 * on top of it, so amount_due always equals the sum of line totals unless a
 * discount actually reduces what's charged.
 *
 * All money math is done in integer cents (round-half-away-from-zero, same
 * as PHP's round()) and only converted back to a 2-decimal float at the very
 * end of each line, so results can never drift the way chained float
 * arithmetic (e.g. 0.1 + 0.2) can.
 */
class VatCalculator
{
    public function __construct(
        private readonly string $taxMode,
        private readonly float $vatRatePercent
    ) {
    }

    private function isVatMode(): bool
    {
        return $this->taxMode === 'VAT';
    }

    private function toCents(float $pesos): int
    {
        return (int) round($pesos * 100);
    }

    private function toPesos(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * @param array<int, array{quantity: int, unit_price: float, vat_type: string, senior_pwd_eligible: bool}> $lines
     * @param bool $discountRequested Whether a Senior Citizen/PWD discount was requested for this transaction.
     *             Only lines whose product is senior_pwd_eligible actually receive it.
     * @return array{
     *     lines: array<int, array{total_amount: float, vat_type: ?string, vatable_sales: float, vat_amount: float, vat_exempt_sales: float, zero_rated_sales: float, discount_amount: float, discount_vat_exempt_amount: float, amount_due: float}>,
     *     totals: array{total_amount: float, vatable_sales: float, vat_amount: float, vat_exempt_sales: float, zero_rated_sales: float, discount_amount: float, discount_vat_exempt_amount: float, amount_due: float}
     * }
     */
    public function calculate(array $lines, bool $discountRequested): array
    {
        $results = [];
        $totals = [
            'total_amount' => 0,
            'vatable_sales' => 0,
            'vat_amount' => 0,
            'vat_exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'discount_amount' => 0,
            'discount_vat_exempt_amount' => 0,
            'amount_due' => 0,
        ];

        foreach ($lines as $line) {
            $result = $this->calculateLine($line, $discountRequested);
            $results[] = $result;

            foreach ($totals as $key => $value) {
                $totals[$key] += $this->toCents($result[$key]);
            }
        }

        foreach ($totals as $key => $cents) {
            $totals[$key] = $this->toPesos($cents);
        }

        return ['lines' => $results, 'totals' => $totals];
    }

    private function calculateLine(array $line, bool $discountRequested): array
    {
        $quantity = (int) $line['quantity'];
        $unitPrice = (float) $line['unit_price'];
        $vatType = $line['vat_type'] ?? 'T';
        $eligible = (bool) ($line['senior_pwd_eligible'] ?? false);

        $totalCents = $this->toCents($quantity * $unitPrice);

        $empty = [
            'total_amount' => $this->toPesos($totalCents),
            'vat_type' => $vatType,
            'vatable_sales' => 0.0,
            'vat_amount' => 0.0,
            'vat_exempt_sales' => 0.0,
            'zero_rated_sales' => 0.0,
            'discount_amount' => 0.0,
            'discount_vat_exempt_amount' => 0.0,
            'amount_due' => 0.0,
        ];

        $discounted = $discountRequested && $eligible;

        if ($discounted) {
            return $this->calculateDiscountedLine($totalCents, $vatType, $empty);
        }

        return $this->calculateUndiscountedLine($totalCents, $vatType, $empty);
    }

    private function calculateUndiscountedLine(int $totalCents, string $vatType, array $result): array
    {
        $result['amount_due'] = $this->toPesos($totalCents);

        if (!$this->isVatMode()) {
            return $result;
        }

        if ($vatType === 'T') {
            $vatExclusiveCents = (int) round($totalCents / (1 + $this->vatRatePercent / 100));
            $result['vatable_sales'] = $this->toPesos($vatExclusiveCents);
            $result['vat_amount'] = $this->toPesos($totalCents - $vatExclusiveCents);
        } elseif ($vatType === 'X') {
            $result['vat_exempt_sales'] = $this->toPesos($totalCents);
        } elseif ($vatType === 'Z') {
            $result['zero_rated_sales'] = $this->toPesos($totalCents);
        }

        return $result;
    }

    /**
     * Senior Citizen/PWD items are exempt from VAT outright (RA 9994/10754),
     * so they never land in the VATable/Exempt/Zero-rated breakdown above -
     * they're reported separately via discount_amount/discount_vat_exempt_amount
     * instead, to avoid double-counting the same peso in two places.
     */
    private function calculateDiscountedLine(int $totalCents, string $vatType, array $result): array
    {
        $vatExemptCents = 0;
        $baseCents = $totalCents;

        if ($this->isVatMode() && $vatType === 'T') {
            $vatExclusiveCents = (int) round($totalCents / (1 + $this->vatRatePercent / 100));
            $vatExemptCents = $totalCents - $vatExclusiveCents;
            $baseCents = $vatExclusiveCents;
        }

        $discountCents = (int) round($baseCents * 0.20);

        $result['discount_vat_exempt_amount'] = $this->toPesos($vatExemptCents);
        $result['discount_amount'] = $this->toPesos($discountCents);
        $result['amount_due'] = $this->toPesos($baseCents - $discountCents);

        return $result;
    }
}
