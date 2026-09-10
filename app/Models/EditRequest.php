<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'status',
        'completed',
        'request_details',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * withTrashed: archiving a user must not erase who filed a request.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
