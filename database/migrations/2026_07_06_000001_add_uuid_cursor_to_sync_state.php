<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pull cursor tiebreaker — يضيف last_pulled_uuid إلى sync_state.
 *
 * السبب: cursor السحب كان يخزّن updated_at فقط (بدقّة ثانية). لو أكثر من batch_size
 * صف يتشاركون نفس الثانية، كانت الصفوف بعد الحدّ تُتخطّى وتضيع (خطير عند أول تحميل
 * لجدول drugs الكبير). الحل: keyset pagination بمفتاح مركّب (updated_at, uuid).
 * هذا العمود يخزّن uuid آخر صف مستلَم عند نفس الطابع الزمني.
 *
 * migration إضافي غير كاسر (يعمل بأمان بعد التحديث التلقائي على الفروع).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('sync_state', function (Blueprint $table) {
            $table->string('last_pulled_uuid', 26)->nullable()->after('last_pulled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_state', function (Blueprint $table) {
            $table->dropColumn('last_pulled_uuid');
        });
    }
};
