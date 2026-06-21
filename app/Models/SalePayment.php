<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class SalePayment extends Model
{
    use Syncable;

    protected $fillable = [
        'sale_id', 'customer_id', 'user_id', 'amount', 'payment_method', 'notes',
    ];

    public function sale()     { return $this->belongsTo(Sale::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}