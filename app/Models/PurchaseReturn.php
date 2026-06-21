<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id',
        'purchase_invoice_id',
        'supplier_id',
        'return_number',
        'total',
        'refund_method',
        'reason',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    /* ── Relations ── */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Generate unique return number ── */
    public static function generateNumber(): string  // ✅ public static
    {
        $today  = now()->format('Ymd');
        $prefix = 'PR-' . $today . '-';

        $last = static::where('return_number', 'like', $prefix . '%')  // ✅ static:: بدل PurchaseReturn::
            ->orderBy('return_number', 'desc')
            ->value('return_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /* ── Refund method labels ── */
    public function getRefundMethodLabelAttribute(): string
    {
        return match ($this->refund_method) {
            'cash'    => 'رد نقدي من المورد',
            'balance' => 'خصم من رصيد المورد',
            'none'    => 'بدون رد',
            default   => $this->refund_method,
        };
    }
}