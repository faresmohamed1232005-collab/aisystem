<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\Actor;
use App\Support\Branch;
use Illuminate\Support\Facades\Auth;

/**
 * Auditable — يسجّل create/update/delete للموديل في audit_logs (Phase 3B).
 *
 * يُسجَّل فقط في سياق طلب مصادَق (فعل مستخدم)؛ يتخطّى seeding/console/المزامنة (المزامنة
 * تطبّق عبر DB::table فلا تُطلق أحداث Eloquent أصلاً). القيم الضوضائية (timestamps/uuid/
 * أعمدة المزامنة/كلمات السر) تُستبعد من الفروق المسجّلة.
 */
trait Auditable
{
    /** أعمدة لا معنى لتدقيقها. */
    private static array $auditIgnore = [
        'created_at', 'updated_at', 'synced_at', 'uuid', 'branch_id',
        'password', 'remember_token', 'deleted_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created', [], $model->auditFilter($model->getAttributes())));

        static::updated(function ($model) {
            $new = $model->auditFilter($model->getChanges());
            if (empty($new)) {
                return; // تغيّر ضوضائي فقط (مثل updated_at) — لا نسجّل
            }
            $old = array_intersect_key($model->getOriginal(), $new);
            $model->writeAudit('updated', $old, $new);
        });

        static::deleted(fn ($model) => $model->writeAudit('deleted', $model->auditFilter($model->getAttributes()), []));
    }

    private function auditFilter(array $attrs): array
    {
        return array_diff_key($attrs, array_flip(self::$auditIgnore));
    }

    private function writeAudit(string $event, array $old, array $new): void
    {
        if (! Auth::check()) {
            return; // خارج سياق مستخدم (seed/console/sync) — لا تدقيق
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'actor_type'     => Actor::isOwner() ? 'owner' : 'sub_user',
            'actor_id'       => Actor::subUserId(),
            'actor_name'     => Actor::name(),
            'event'          => $event,
            'auditable_type' => static::class,
            'auditable_id'   => $this->getKey(),
            'label'          => $this->auditLabel(),
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => request()->ip(),
            'branch_id'      => Branch::id(),
            'created_at'     => now(),
        ]);
    }

    /** وصف مقروء للسجل المتأثّر (رقم فاتورة/كود/اسم…). */
    private function auditLabel(): ?string
    {
        foreach (['transfer_number', 'invoice_number', 'claim_number', 'code', 'name', 'transfer_id'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }
        return null;
    }
}
