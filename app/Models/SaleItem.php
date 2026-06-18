<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'drug_id',
        'quantity',
        'unit_key',
        'unit_name',
        'unit_price',
        'qty_factor',
        'price',
        'subtotal',
        'cost_price',
    ];

    protected $casts = [
        'qty_factor'  => 'float',
        'cost_price'  => 'float',
        'unit_price'  => 'float',
        'price'       => 'float',
        'subtotal'    => 'float',
        'quantity'    => 'float',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}