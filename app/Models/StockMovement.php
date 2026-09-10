<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * withTrashed: the stock audit trail must keep naming who made the change,
     * even after that account is archived.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class)->withTrashed();
    }
}
