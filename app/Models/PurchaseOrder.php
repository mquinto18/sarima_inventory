<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'status',
        'is_auto_generated',
        'total_value',
        'expected_delivery_date',
        'sent_at',
        'delivered_at',
        'created_by',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'is_auto_generated' => 'boolean',
        'total_value' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Individual deliveries received against this order, newest first.
     */
    public function receipts()
    {
        return $this->hasMany(PurchaseOrderReceipt::class)->latest('received_at');
    }

    /**
     * withTrashed: purchase orders keep showing their creator after archiving.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->withTrashed();
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['sent', 'confirmed'])
            ->whereNotNull('expected_delivery_date')
            ->orderBy('expected_delivery_date');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'confirmed']);
    }
}
