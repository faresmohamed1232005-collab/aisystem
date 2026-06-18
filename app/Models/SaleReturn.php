<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'user_id',
        'sale_id',
        'customer_id',
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
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Generate unique return number ── */
    public static function generateNumber(): string  // ✅ حذف $userId — مش محتاجه
    {
        $today  = now()->format('Ymd');
        $prefix = 'SR-' . $today . '-';

        // ✅ فلترة على بريفيكس اليوم بالظبط — مش آخر record في الداتابيز كلها
        $last = static::where('return_number', 'like', $prefix . '%')
            ->orderBy('return_number', 'desc')
            ->value('return_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /* ── Refund method labels ── */
    public function getRefundMethodLabelAttribute(): string
    {
        return match ($this->refund_method) {
            'cash'    => 'رد نقدي',
            'balance' => 'رصيد العميل',
            'none'    => 'بدون رد',
            default   => $this->refund_method,
        };
    }
}