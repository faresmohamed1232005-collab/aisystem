<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * سجل تدقيق واحد — غير قابل للتعديل بعد الإنشاء (append-only).
 */
class AuditLog extends Model
{
    public $timestamps = false; // created_at فقط يُضبط يدوياً عند الإنشاء

    protected $fillable = [
        'user_id', 'actor_type', 'actor_id', 'actor_name', 'event',
        'auditable_type', 'auditable_id', 'label', 'old_values', 'new_values',
        'ip_address', 'branch_id', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /** اسم الموديل المتأثّر بالعربية (آخر جزء من الكلاس). */
    public function getModelLabelAttribute(): string
    {
        $map = [
            'Sale' => 'فاتورة بيع', 'PurchaseInvoice' => 'فاتورة شراء', 'Contract' => 'عقد',
            'InsuranceClaim' => 'مطالبة تأمين', 'StockTransfer' => 'تحويل مخزون',
            'BranchModel' => 'فرع', 'SubUser' => 'مستخدم', 'Expense' => 'مصروف',
            'Employee' => 'موظف', 'Customer' => 'عميل', 'InsuredPatient' => 'مريض مؤمّن',
        ];
        $short = class_basename($this->auditable_type);
        return $map[$short] ?? $short;
    }
}
