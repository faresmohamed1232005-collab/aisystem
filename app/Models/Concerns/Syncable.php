<?php

namespace App\Models\Concerns;

use App\Support\Branch;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Syncable — يُطبَّق على كل Model قابل للمزامنة بين الفرع والسيرفر.
 *
 * يوفّر:
 *   - uuid (ULID) يُولَّد تلقائياً عند الإنشاء — المعرّف العالمي للربط عبر الأجهزة.
 *   - branch_id يُضبط تلقائياً على الفرع المُنشئ (origin) إن لم يُحدَّد.
 *   - SoftDeletes — لأن المزامنة تحتاج معرفة الصفوف المحذوفة (deleted_at) لا اختفاءها.
 *
 * المتطلبات: الجدول يحتوي الأعمدة uuid, branch_id, synced_at, deleted_at
 * (تضيفها migration: add_sync_columns_to_syncable_tables).
 *
 * استخدام: في الـ Model أضف `use Syncable;` فقط.
 */
trait Syncable
{
    use SoftDeletes;

    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            // ولّد UUID عالمي إن لم يُضبط
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::ulid();
            }
            // وسم الفرع المُنشئ (origin) إن لم يُضبط
            if (empty($model->branch_id)) {
                $model->branch_id = Branch::id();
            }
        });
    }

    /**
     * نستخدم uuid كمفتاح للربط في عمليات المزامنة (لا id المحلي).
     */
    public function getSyncKey(): ?string
    {
        return $this->uuid;
    }

    /**
     * هل صُودِق على هذا الصف مع السيرفر بعد آخر تعديل؟
     */
    public function isSynced(): bool
    {
        return $this->synced_at !== null
            && $this->updated_at !== null
            && $this->synced_at >= $this->updated_at;
    }

    /**
     * نطاق: الصفوف التي تحتاج دفعها للسيرفر (لم تُزامن أو عُدّلت بعد آخر مزامنة).
     */
    public function scopePendingSync($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('synced_at')
              ->orWhereColumn('synced_at', '<', 'updated_at');
        });
    }
}
