<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Syncable;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Model;

/**
 * StockTransfer — تحويل مخزون بين فرعين لنفس المالك.
 *
 * دورة الحياة: draft (إنشاء بلا خصم) → approved (اعتماد المصدر) → sent (خصم مخزون
 * المصدر) → received (إضافة للوجهة) / rejected. المصدر يعتمد ويرسل؛ الوجهة تستلم/ترفض.
 * bidirectional: الفرع المصدر ينشئه ويدفعه؛ الوجهة تسحبه وتضغط استلام وتدفعه؛ المصدر
 * يسحب التحديث. from/to_branch_id = branch_id الثابت (نص) لا FK.
 */
class StockTransfer extends Model
{
    use Syncable;
    use Auditable;

    public const STATUSES = ['draft', 'approved', 'sent', 'received', 'rejected'];

    protected $fillable = [
        'user_id', 'from_branch_id', 'to_branch_id', 'transfer_number',
        'status', 'notes', 'reject_reason', 'approved_at', 'sent_at', 'received_at', 'branch_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    /** رقم تحويل متسلسل ببادئة فرع المصدر (TR-A-0001). */
    public static function generateNumber(int $userId, string $branchCode): string
    {
        $prefix = 'TR-' . strtoupper($branchCode) . '-';
        $last = static::withTrashed()
            ->where('user_id', $userId)
            ->where('transfer_number', 'like', $prefix . '%')
            ->orderByRaw(Sql::castInt(Sql::substr('transfer_number', strlen($prefix) + 1)) . ' DESC')
            ->value('transfer_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (static::withTrashed()->where('user_id', $userId)->where('transfer_number', $number)->exists());

        return $number;
    }
}
