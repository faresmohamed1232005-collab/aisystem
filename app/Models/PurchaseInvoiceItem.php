<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',       // nullable - FK قديم
        'drug_id',          // ✅ FK على drugs
        'product_name',
        'category',
        'barcode',
        'purchase_price',
        'selling_price',
        'quantity',
        'expiry_date',
        'discount',
        'tax',
        'subtotal',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax'            => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'expiry_date'    => 'date',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    // ✅ drug() - العلاقة الصح
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    // product() - للتوافق مع الكود القديم
    public function product(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }
}