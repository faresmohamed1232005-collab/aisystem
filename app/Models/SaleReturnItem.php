<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class SaleReturnItem extends Model
{
    use Syncable;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'drug_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }
}