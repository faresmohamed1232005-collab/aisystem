<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            // اسم الوحدة الوسطى (الشريط) — لو فاضي = "شريط" افتراضي
            $table->string('strip_unit_name')->default('شريط')->after('expiry_date');
            // اسم الوحدة الصغيرة (الحبة) — لو فاضي = "حبة" افتراضي
            $table->string('piece_unit_name')->default('حبة')->after('strip_unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('user_drug_inventory', function (Blueprint $table) {
            $table->dropColumn(['strip_unit_name', 'piece_unit_name']);
        });
    }
};