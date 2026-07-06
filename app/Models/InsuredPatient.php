<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * مريض مؤمّن عليه مرتبط بعقد تأمين. يُختار أثناء البيع لتطبيق التغطية.
 */
class InsuredPatient extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id', 'contract_id', 'name', 'card_number', 'membership_number', 'coverage_end_date',
    ];

    protected $casts = [
        'coverage_end_date' => 'date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function isCoverageExpired(): bool
    {
        return $this->coverage_end_date !== null && $this->coverage_end_date->isPast();
    }
}
