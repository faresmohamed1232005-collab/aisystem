<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — RBAC: تحويل sub_users.role من enum قديم لنص مرن بالأدوار الخمسة الجديدة.
 *
 * القديم: pharmacist|cashier|supervisor|data_entry. الجديد (نص، يُتحقَّق في التطبيق):
 * area_manager|branch_manager|pharmacist|accountant (المالك دوره ضمني OWNER).
 * نبدّل لنص (لا enum) لمرونة الإضافة مستقبلاً وتوافق المزامنة، ثم نُطابق القديم بالجديد.
 */
return new class extends Migration {
    public function up(): void
    {
        // enum → string (varchar). على SQLite العمود نصّي أصلاً؛ ->change آمن على الاثنين.
        Schema::table('sub_users', function (Blueprint $table) {
            $table->string('role', 32)->default('pharmacist')->change();
        });

        // مطابقة الأدوار القديمة بالجديدة.
        DB::table('sub_users')->where('role', 'cashier')->update(['role' => 'pharmacist']);
        DB::table('sub_users')->where('role', 'supervisor')->update(['role' => 'branch_manager']);
        DB::table('sub_users')->where('role', 'data_entry')->update(['role' => 'pharmacist']);
    }

    public function down(): void
    {
        // رجّع أي دور جديد غير موجود في الـ enum القديم لقيمة صالحة قبل استعادة enum.
        DB::table('sub_users')->whereIn('role', ['area_manager', 'accountant'])->update(['role' => 'supervisor']);
        DB::table('sub_users')->where('role', 'branch_manager')->update(['role' => 'supervisor']);

        Schema::table('sub_users', function (Blueprint $table) {
            $table->enum('role', ['pharmacist', 'cashier', 'supervisor', 'data_entry'])->default('cashier')->change();
        });
    }
};
