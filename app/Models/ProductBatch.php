<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'purchase_order_item_id',
        'quantity_received',
        'quantity_remaining',
        'expiry_date',
        'received_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }

    /**
     * FEFO order: soonest expiry first. Dateless batches sort last - a
     * tracked expiry is a real deadline, a null one is this app's existing
     * "indefinite shelf life" semantics, so it shouldn't jump the queue.
     */
    public function scopeFefoOrder($query)
    {
        return $query->orderByRaw('expiry_date IS NULL, expiry_date ASC, received_at ASC, id ASC');
    }
}
