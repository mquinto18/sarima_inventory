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
        'category',
        'stock',
        'status',
        'price',
        'reorder_level',
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
}
