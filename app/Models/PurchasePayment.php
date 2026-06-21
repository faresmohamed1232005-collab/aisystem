<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class PurchasePayment extends Model
{
    use Syncable;

    protected $fillable = [
        'purchase_invoice_id','supplier_id','user_id','amount','payment_method','notes',
    ];
 
    public function purchaseInvoice() { return $this->belongsTo(PurchaseInvoice::class); }
    public function supplier()        { return $this->belongsTo(Supplier::class); }
}
