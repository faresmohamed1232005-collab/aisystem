<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إكمال دورة حياة التحويل بخطوة موافقة (Approved).
 *
 * الدورة الجديدة: draft → approved → sent (خصم المصدر) → received/rejected.
 * approved_at = وقت اعتماد فرع المصدر للتحويل قبل إرساله. (status نصّ افتراضيه draft
 * من migration الإنشاء — لا يحتاج تعديل.)
 *
 * ملاحظة: بلا ->after() (يكسر MySQL جوّه Schema::table في بعض النسخ).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
