<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'user_id','supplier_id','invoice_number',
        'total','discount','tax','net_total',
        'paid','remaining','payment_status','payment_method',
        'invoice_date','notes',
    ];
 
    protected $casts = ['invoice_date' => 'date'];
 
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items()    { return $this->hasMany(PurchaseInvoiceItem::class); }
    public function payments() { return $this->hasMany(PurchasePayment::class); }
}
