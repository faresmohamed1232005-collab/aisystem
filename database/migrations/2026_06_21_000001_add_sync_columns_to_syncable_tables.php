<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * P0 — تجهيز قاعدة البيانات للمزامنة (Offline-First + Multi-branch sync).
 *
 * يضيف لكل جدول قابل للمزامنة:
 *   - uuid (ULID)   : معرّف عالمي فريد للربط بين الفرع والسيرفر (لا نعتمد على id المحلي).
 *   - branch_id     : الفرع الذي أنشأ الصف (origin)، لوسم البيانات في بيئة الفروع المتعددة.
 *   - synced_at     : وقت آخر مزامنة ناجحة لهذا الصف (null = لم يُزامن بعد).
 *   - deleted_at    : حذف ناعم — المزامنة تحتاج معرفة المحذوف بدل اختفائه فجأة.
 *
 * ملاحظات:
 *   - id المحلي (auto-increment) يبقى كما هو للعلاقات الداخلية والأداء.
 *   - متوافق مع MySQL (السيرفر) و SQLite (الفرع).
 *   - الـ backfill يولّد UUID للصفوف الموجودة مسبقاً (يعمل على القاعدة الحالية).
 */
return new class extends Migration {
    /**
     * الجداول القابلة للمزامنة (بيانات الأعمال). نستثني جداول الإطار:
     * users, sessions, cache, jobs, job_batches, failed_jobs, password_reset_tokens.
     */
    private array $tables = [
        'drugs',
        'products',
        'sales',
        'sale_items',
        'sale_payments',
        'sale_returns',
        'sale_return_items',
        'customers',
        'pending_orders',
        'pending_order_items',
        'suppliers',
        'purchase_invoices',
        'purchase_invoice_items',
        'purchase_payments',
        'purchase_returns',
        'purchase_return_items',
        'user_drug_inventory',
        'expenses',
        'employees',
        'employee_transactions',
        'notifications',
        'ads',
        'sub_users',
        'drawer_locks',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'uuid')) {
                    $t->ulid('uuid')->nullable()->after('id');
                }
                if (!Schema::hasColumn($table, 'branch_id')) {
                    $t->string('branch_id', 40)->nullable()->index();
                }
                if (!Schema::hasColumn($table, 'synced_at')) {
                    $t->timestamp('synced_at')->nullable();
                }
                if (!Schema::hasColumn($table, 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            // backfill: ولّد ULID للصفوف الموجودة التي لا تملك uuid بعد
            $this->backfillUuids($table);

            // الآن اجعل uuid فريداً (بعد ملء كل الصفوف لتجنّب تعارض القيم الفارغة)
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unique('uuid', "{$table}_uuid_unique");
            });
        }
    }

    /**
     * توليد ULID لكل صف بدون uuid.
     *
     * ULID يجب أن يُولَّد في PHP (ليس في SQL)، لكن تنفيذ UPDATE لكل صف على حدة بطيء جداً
     * على الجداول الكبيرة (مثلاً drugs ~25k صف). لذا نجمع كل دفعة في عبارة UPDATE واحدة
     * باستخدام CASE WHEN id = ? THEN ? ... داخل معاملة — أسرع بآلاف المرات.
     */
    private function backfillUuids(string $table): void
    {
        DB::table($table)->whereNull('uuid')->orderBy('id')->chunkById(1000, function ($rows) use ($table) {
            $ids   = [];
            $cases = [];
            $bind  = [];
            foreach ($rows as $row) {
                $ids[]   = $row->id;
                $cases[] = 'WHEN ? THEN ?';
                $bind[]  = $row->id;                              // لمطابقة WHEN
                $bind[]  = (string) \Illuminate\Support\Str::ulid(); // قيمة uuid
            }
            if (empty($ids)) {
                return;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $caseSql      = implode(' ', $cases);
            // الترتيب: قيم CASE أولاً ثم قيم IN
            $params = array_merge($bind, $ids);
            DB::update(
                "UPDATE {$table} SET uuid = CASE id {$caseSql} END WHERE id IN ({$placeholders})",
                $params
            );
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // أسقط القيد الفريد أولاً إن وُجد
                try {
                    $t->dropUnique("{$table}_uuid_unique");
                } catch (\Throwable $e) {
                    // تجاهل لو غير موجود
                }
            });

            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['uuid', 'branch_id', 'synced_at', 'deleted_at'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
