<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use Syncable;

    protected $fillable = [
        'purchase_return_id',
        'purchase_invoice_item_id',
        'drug_id',          // ✅ drug_id بدل product_id
        'product_name',
        'quantity',
        'purchase_price',
        'subtotal',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceItem::class);
    }

    // ✅ drug() بدل product()
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}