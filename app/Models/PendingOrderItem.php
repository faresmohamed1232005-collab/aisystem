<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class PendingOrderItem extends Model
{
    use Syncable;

    protected $fillable = [
        'pending_order_id',
        'drug_id',
        'quantity',
        'qty_factor',
        'unit_key',
        'unit_name',
        'unit_price',
        'price',
        'subtotal',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class); // ✅ أضف دي
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pendingOrder()
    {
        return $this->belongsTo(PendingOrder::class);
    }
}