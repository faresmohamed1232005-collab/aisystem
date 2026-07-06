<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * StockTransferItem — بند تحويل (دواء + صلاحية + كمية + تكلفة الباتش).
 * cost_price/expiry يُحملان من المصدر لحفظ سلامة الباتش عند الاستلام في الوجهة.
 */
class StockTransferItem extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id', 'transfer_id', 'drug_id', 'expiry_date',
        'quantity', 'cost_price', 'received_quantity', 'damaged_quantity', 'branch_id',
    ];

    protected $casts = [
        'expiry_date'       => 'date',
        'quantity'          => 'float',
        'cost_price'        => 'float',
        'received_quantity' => 'float',
        'damaged_quantity'  => 'float',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
