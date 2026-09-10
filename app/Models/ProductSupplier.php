<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    protected $table = 'product_supplier';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'cost_price',
        'lead_time_days',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'cost_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
