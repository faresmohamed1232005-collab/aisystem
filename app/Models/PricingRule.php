<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * قاعدة تسعير خاصة بعقد حسب فئة المنتج (خصم نسبة أو مبلغ ثابت).
 */
class PricingRule extends Model
{
    use Syncable;

    public const CATEGORIES = ['medicines', 'cosmetics', 'medical_devices', 'baby_care'];

    protected $fillable = [
        'user_id', 'contract_id', 'product_category', 'discount_type', 'discount_value',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
