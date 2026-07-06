<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * بند مطالبة — يربط فاتورة بيع (sale) بمطالبة، ويحمل المبلغ المطالَب به عنها.
 */
class InsuranceClaimItem extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id', 'claim_id', 'sale_id', 'covered_amount',
    ];

    protected $casts = [
        'covered_amount' => 'decimal:2',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'claim_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
