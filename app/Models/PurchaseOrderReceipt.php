<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One delivery actually received against a purchase order line.
 *
 * purchase_order_items.quantity_received only holds a running total, so this is
 * the only place the individual deliveries — and who took them in, and when —
 * are recorded.
 */
class PurchaseOrderReceipt extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_received',
        'received_by',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'quantity_received' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * withTrashed: archiving a user must not erase who received a delivery.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }
}
