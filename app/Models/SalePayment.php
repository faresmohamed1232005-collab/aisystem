<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'user_id', 'amount', 'payment_method', 'notes',
    ];

    public function sale()     { return $this->belongsTo(Sale::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}