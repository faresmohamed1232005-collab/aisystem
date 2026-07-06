<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * BranchModel — قراءات جدول branches على مستوى التطبيق (شجرة الفروع، اختيار وجهة
 * التحويل، اقتراح فرع بديل). منفصل عن App\Support\Branch (محلّل هوية هذا التثبيت).
 *
 * السيرفر master لهذا الجدول: يُنشأ عبر register() (query builder) ويُسحب للفرع
 * owner-scoped. لا يُنشأ عبر Eloquent، فسلوك Syncable في ضبط branch_id تلقائياً لا
 * يُفعَّل هنا (والعمود branch_id هنا = معرّف عمل الفرع لا وسم المنشئ).
 */
class BranchModel extends Model
{
    use Syncable;
    use Auditable;

    protected $table = 'branches';

    protected $fillable = [
        'branch_id', 'code', 'name', 'user_id', 'token',
        'type', 'governorate', 'address', 'phone',
        'manager_type', 'manager_id', 'status',
        'allow_transfer_out', 'allow_transfer_in', 'allow_pricing', 'allow_stocktake',
        'registered_at', 'last_seen_at',
    ];

    protected $casts = [
        'registered_at'      => 'datetime',
        'last_seen_at'       => 'datetime',
        'allow_transfer_out' => 'boolean',
        'allow_transfer_in'  => 'boolean',
        'allow_pricing'      => 'boolean',
        'allow_stocktake'    => 'boolean',
    ];

    /** الفروع الأخرى لنفس المالك (لاختيار وجهة التحويل واقتراح فرع بديل). */
    public function scopeSiblingsOf($query, int $ownerUserId, string $exceptBranchId)
    {
        return $query->where('user_id', $ownerUserId)
            ->where('branch_id', '!=', $exceptBranchId)
            ->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
