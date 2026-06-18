<?php
// database/migrations/xxxx_add_units_to_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // الوحدة الرئيسية (علبة / عبوة)
            $table->string('unit_name')->default('علبة')->after('image');         // اسم الوحدة الكبيرة
            $table->string('sub_unit_name')->nullable()->after('unit_name');       // اسم الوحدة الوسطى (شريط)
            $table->string('smallest_unit_name')->nullable()->after('sub_unit_name'); // أصغر وحدة (حبة)

            // كم وحدة وسطى في الوحدة الكبيرة
            $table->integer('units_per_pack')->default(1)->after('smallest_unit_name');
            // كم أصغر وحدة في الوحدة الوسطى
            $table->integer('sub_units_per_unit')->default(1)->after('units_per_pack');

            // السعر المحفوظ دايماً هو سعر الوحدة الكبيرة (الأصلية)
            // سعر الوحدة الوسطى والصغيرة بيتحسبوا تلقائي
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'unit_name', 'sub_unit_name', 'smallest_unit_name',
                'units_per_pack', 'sub_units_per_unit',
            ]);
        });
    }
};