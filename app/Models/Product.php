<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'stock',
        'status',
        'price',
        'reorder_level',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function approvedEditRequest()
    {
        return $this->hasOne(\App\Models\EditRequest::class)
            ->where('status', 'approved');
    }

    // Returns the edit request for this product and user
    public function editRequestForUser($userId)
    {
        return \App\Models\EditRequest::where('product_id', $this->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Single source of truth for the stock-level status label, so every
     * caller (dashboard, inventory list, InventoryService, reorder approval)
     * agrees on the same thresholds.
     */
    public static function statusForStock(int $stock): string
    {
        if ($stock <= \App\Models\Setting::get('critical_stock_level', 5)) {
            return 'Critical';
        }

        if ($stock <= \App\Models\Setting::get('low_stock_threshold', 10)) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['id', 'cost_price', 'lead_time_days', 'is_primary'])
            ->withTimestamps();
    }

    public function primarySupplierLink()
    {
        return $this->hasOne(ProductSupplier::class)->where('is_primary', true);
    }

    public function primarySupplier(): ?Supplier
    {
        $link = $this->primarySupplierLink()->first();

        return $link ? $link->supplier : null;
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
