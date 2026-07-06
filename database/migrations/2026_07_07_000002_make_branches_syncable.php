<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Phase 2أ — D3: جعل جدول branches قابلاً للمزامنة (owner-scoped pull).
 *
 * السيرفر master لسجل الفروع. الفرع يسحب سجلات فروع نفس المالك (لعرض شجرة الفروع
 * واختيار وجهة التحويل واقتراح فرع بديل). لا يُدفع من الفرع (server-master).
 *
 * نضيف أعمدة المزامنة (uuid/synced_at/deleted_at). ملاحظة: العمود branch_id الموجود
 * هو معرّف العمل للفرع (مختلف عن uuid الذي يربط الصفوف عبر الأجهزة).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'uuid')) {
                $table->ulid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('branches', 'synced_at')) {
                $table->timestamp('synced_at')->nullable();
            }
            if (! Schema::hasColumn('branches', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // backfill uuid للصفوف الموجودة (ULID فريد ثابت للربط عبر الأجهزة).
        DB::table('branches')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('branches')->where('id', $row->id)->update(['uuid' => (string) Str::ulid()]);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('branches', 'synced_at')) {
                $table->dropColumn('synced_at');
            }
            if (Schema::hasColumn('branches', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });
    }
};
