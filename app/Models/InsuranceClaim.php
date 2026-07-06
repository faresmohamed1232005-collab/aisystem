<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Model;

/**
 * مطالبة تأمين — تجمع فواتير التأمين غير المرسلة لشركة وتكوّن ملف مطالبة.
 * بيانات تشغيلية: تُنشأ على الفرع وتُدفع للسيرفر (push).
 */
class InsuranceClaim extends Model
{
    use Syncable;

    public const STATUSES = ['draft', 'submitted', 'paid', 'rejected'];

    protected $fillable = [
        'user_id', 'contract_id', 'claim_number', 'amount', 'status',
        'rejection_reason', 'claim_date', 'notes',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'claim_date' => 'date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(InsuranceClaimItem::class, 'claim_id');
    }

    public static function generateNumber(int $userId): string
    {
        $last = InsuranceClaim::withTrashed()
            ->where('user_id', $userId)
            ->where('claim_number', 'like', 'CLM-%')
            ->orderByRaw(Sql::castInt(Sql::substr('claim_number', 5)) . ' DESC')
            ->value('claim_number');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        do {
            $number = 'CLM-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (InsuranceClaim::withTrashed()->where('user_id', $userId)->where('claim_number', $number)->exists());

        return $number;
    }
}
