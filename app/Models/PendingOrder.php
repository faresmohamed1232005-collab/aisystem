<?php
// app/Models/PendingOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class PendingOrder extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id',
        'customer_id',
        'order_number',
        'delivery_type',
        'delivery_address',
        'delivery_phone',
        'total',
        'discount',
        'notes',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function items()
    {
        return $this->hasMany(PendingOrderItem::class);
    }



    public function getNetTotalAttribute(): float
    {
        return max(0, $this->total - $this->discount);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }


    public static function generateNumber(): string
    {
        $prefix = 'PND-';

        $last = \DB::table('pending_orders')
            ->where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->value('order_number');

        if ($last) {
            $lastNumber = (int) str_replace($prefix, '', $last);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}