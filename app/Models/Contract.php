<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * عقد مع جهة متعاقدة (تأمين/حكومي/شركة/نقابة/مستشفى/جامعة).
 *
 * بيانات ماستر على مستوى المالك — تُدار مركزياً وتُوزَّع على الفروع عبر pull.
 */
class Contract extends Model
{
    use Syncable;

    public const TYPES = ['insurance', 'government', 'company', 'syndicate', 'hospital', 'university'];

    protected $fillable = [
        'user_id', 'code', 'type', 'name', 'tax_number', 'contact_person',
        'mobile', 'email', 'address', 'start_date', 'end_date', 'status',
        'contract_pdf', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function insuranceRule()
    {
        return $this->hasOne(InsuranceRule::class);
    }

    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }

    public function insuredPatients()
    {
        return $this->hasMany(InsuredPatient::class);
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function isInsurance(): bool
    {
        return $this->type === 'insurance';
    }

    /** مطابقة نمط Customer::generateCode — كود متسلسل فريد داخل المالك. */
    public static function generateCode(int $userId): string
    {
        $last = Contract::withTrashed()
            ->where('user_id', $userId)
            ->where('code', 'like', 'CONT-%')
            ->orderByRaw(Sql::castInt(Sql::substr('code', 6)) . ' DESC')
            ->value('code');

        $nextNumber = $last ? ((int) substr($last, 5)) + 1 : 1;

        do {
            $code = 'CONT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Contract::withTrashed()->where('user_id', $userId)->where('code', $code)->exists());

        return $code;
    }
}
