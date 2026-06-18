<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            // ① شيل الـ Foreign Keys الأول عشان يسمحلنا نغير الـ index
            $table->dropForeign(['user_id']);
            $table->dropForeign(['drug_id']);

            // ② شيل الـ unique القديم
            $table->dropUnique('user_drug_inventory_user_id_drug_id_unique');

            // ③ ضيف الـ unique الجديد (دواء + صلاحية = باتش مستقل)
            $table->unique(['user_id', 'drug_id', 'expiry_date'], 'udrug_inv_user_drug_expiry_unique');

            // ④ رجّع الـ Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['drug_id']);

            $table->dropUnique('udrug_inv_user_drug_expiry_unique');

            $table->unique(['user_id', 'drug_id'], 'user_drug_inventory_user_id_drug_id_unique');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
        });
    }
};