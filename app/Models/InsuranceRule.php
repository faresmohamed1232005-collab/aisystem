<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * قواعد التأمين لعقد (1:1 مع عقد نوعه insurance): التغطية/التحمّل/السقف/الموافقات.
 */
class InsuranceRule extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id', 'contract_id', 'coverage_percent', 'patient_contribution_percent',
        'max_per_prescription', 'approval_required', 'approval_amount_limit',
    ];

    protected $casts = [
        'coverage_percent'             => 'decimal:2',
        'patient_contribution_percent' => 'decimal:2',
        'max_per_prescription'         => 'decimal:2',
        'approval_required'            => 'boolean',
        'approval_amount_limit'        => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
