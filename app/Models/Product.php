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
        'vat_type',
        'senior_pwd_eligible',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'senior_pwd_eligible' => 'boolean',
    ];

    /**
     * BIR VAT classifications a product's shelf price can carry.
     * T = VATable, X = VAT-exempt, Z = zero-rated.
     */
    public const VAT_TYPES = ['T', 'X', 'Z'];

    /**
     * Categories whose items never expire (e.g. gauze, alcohol, thermometers),
     * so the Add/Edit Product forms don't require an expiry date for them and
     * any submitted one is ignored rather than stored.
     */
    public const NON_EXPIRING_CATEGORIES = ['medical supplies'];

    public static function categoryNeverExpires(?string $category): bool
    {
        return in_array(strtolower(trim((string) $category)), self::NON_EXPIRING_CATEGORIES, true);
    }

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

    /**
     * Authoritative source of truth for stock/expiry is product_batches -
     * `stock` and `expiry_date` above are denormalized caches kept in sync
     * by InventoryService on every mutation. Only InventoryService should
     * ever write them directly.
     */
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}
