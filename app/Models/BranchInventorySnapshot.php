<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

/**
 * BranchInventorySnapshot — صورة مجمّعة لكمية دواء بفرع معيّن، يبنيها السيرفر من
 * user_drug_inventory المدفوعة. read-only على الفرع (pull_scoped: owner_other_branches).
 *
 * تُستخدم لعرض مخزون الفروع الأخرى واقتراح فرع بديل دون كشف تفاصيل باتشاتها.
 * snapshot_branch_id = الفرع الموصوف (منفصل عن branch_id = وسم المُنشئ = السيرفر).
 */
class BranchInventorySnapshot extends Model
{
    use Syncable;

    protected $fillable = [
        'user_id', 'snapshot_branch_id', 'drug_id', 'quantity', 'branch_id',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
