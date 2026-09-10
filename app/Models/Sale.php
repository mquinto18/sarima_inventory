<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity_sold',
        'unit_price',
        'total_amount',
        'amount_tendered',
        'change_due',
        'sale_date',
        'month_year',
        'notes',
        'pos_transaction_id',
        'user_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_due' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get monthly sales data for SARIMA analysis
    public static function getMonthlySalesData($productId = null, $months = 12)
    {
        $query = self::selectRaw('
            sale_month,
            SUM(quantity_sold) as total_quantity,
            SUM(total_amount) as total_revenue,
            COUNT(*) as transaction_count
        ')
            ->groupBy('sale_month')
            ->orderBy('sale_month');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->limit($months)->get();
    }

    /**
     * withTrashed: an archived cashier must still be named on their past sales.
     * Null for legacy rows recorded before the POS captured a cashier.
     */
    public function cashier()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id')->withTrashed();
    }
}
