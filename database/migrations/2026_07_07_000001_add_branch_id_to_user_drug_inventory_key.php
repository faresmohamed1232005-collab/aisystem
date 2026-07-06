<?php

use App\Support\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2أ — D1: branch_id بُعد أساسي في مفتاح المخزون.
 *
 * فروع المالك الواحد تتشارك نفس user_id؛ من غير branch_id في المفتاح، مخزون فرعين
 * لنفس (الدواء + الصلاحية) بيتصادم على السيرفر (unique violation عند الـ push الثاني).
 * فنبدّل الـ unique من (user_id, drug_id, expiry_date) إلى
 * (user_id, branch_id, drug_id, expiry_date).
 *
 * الترتيب حرج: (1) backfill branch_id للصفوف الناجية بلا فرع، (2) NOT NULL (لأن NULL
 * في مفتاح unique يُعامَل كقيمة مميّزة فيكسر التفرّد)، (3) تبديل المفتاح. يتبع نمط
 * drop/re-add للـ FK من migration 2026_05_10_145159.
 *
 * ⚠️ لازم يتنشر على السيرفر قبل تفعيل أي فرع تانٍ.
 */
return new class extends Migration {
    public function up(): void
    {
        // ① backfill: أي صف بلا فرع يُنسب لهوية هذا التثبيت (السيرفر='server'، الفرع=معرّفه).
        //    الجدول فعليًا فاضٍ الآن، فهذا دفاع لو وُجدت صفوف قديمة.
        $branchId = Branch::id();
        DB::table('user_drug_inventory')
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', ''))
            ->update(['branch_id' => $branchId]);

        // ② branch_id NOT NULL (بعد ضمان صفر NULL).
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->string('branch_id', 40)->nullable(false)->default('')->change();
        });

        // ③ تبديل مفتاح التفرّد (بنمط drop/re-add للـ FK).
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['drug_id']);

            $table->dropUnique('udrug_inv_user_drug_expiry_unique');
            $table->unique(
                ['user_id', 'branch_id', 'drug_id', 'expiry_date'],
                'udrug_inv_user_branch_drug_expiry_unique'
            );

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['drug_id']);

            $table->dropUnique('udrug_inv_user_branch_drug_expiry_unique');
            $table->unique(['user_id', 'drug_id', 'expiry_date'], 'udrug_inv_user_drug_expiry_unique');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
        });

        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->string('branch_id', 40)->nullable()->default(null)->change();
        });
    }
};
