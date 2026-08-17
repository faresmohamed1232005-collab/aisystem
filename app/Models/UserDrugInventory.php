<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;
use App\Support\ActiveBranch;
use App\Support\Branch;


class UserDrugInventory extends Model
{
    use Syncable;

    protected $table = 'user_drug_inventory'; // ✅ أضف السطر ده

    /**
     * نطاق: مخزون الفرع الحالي فقط (branch_id = هوية هذا التثبيت).
     *
     * Phase 2أ: المخزون بقى مقسّماً بـ branch_id. على الفرع (جهاز واحد) كل الصفوف
     * لنفس الفرع فالفلتر دفاعي؛ على السيرفر يمنع تسرّب مخزون فرع لعمليات فرع آخر.
     */
    public function scopeCurrentBranch($query)
    {
        return $query->where($this->getTable() . '.branch_id', ActiveBranch::id());
    }

    public function scopeSaleable($query)
    {
        return $query->where(function ($query) {
            $query->whereNull($this->getTable() . '.expiry_date')
                ->orWhereDate($this->getTable() . '.expiry_date', '>=', today());
        });
    }

    protected $fillable = [
        'user_id',
        'branch_id',
        'drug_id',
        'quantity',
        'min_quantity',
        'custom_price',
        'cost_price',
        'expiry_date',
        'strip_unit_name',
        'piece_unit_name',
        'notes',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'min_quantity' => 'integer',
        'custom_price' => 'float',
        'cost_price'   => 'float',
        'expiry_date'  => 'date',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
 
