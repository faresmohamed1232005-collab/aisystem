<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | user_drug_inventory
        |--------------------------------------------------------------------------
        | كل يوزر ليه كميات خاصة بيه على كل دواء.
        | الأدوية نفسها في جدول drugs المشترك.
        | لو اليوزر مش موجود في الجدول ده يبقى الكمية صفر.
        |--------------------------------------------------------------------------
        */
        Schema::create('user_drug_inventory', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();

            // ── الكميات ──
            $table->decimal('quantity', 12, 4)->default(0);         // الكمية الحالية (بالوحدة الكبرى)
            $table->integer('min_quantity')->default(5);             // حد التنبيه

            // ── سعر خاص بالصيدلية (لو اليوزر عايز يخالف السعر العام) ──
            $table->decimal('custom_price', 10, 2)->nullable();     // سعر البيع الخاص — null = يستخدم new_price من drugs
            $table->decimal('cost_price', 10, 2)->nullable();       // سعر الشراء الخاص

            // ── تاريخ الانتهاء (بيختلف من يوزر للتاني) ──
            $table->date('expiry_date')->nullable();

            // ── ملاحظة خاصة باليوزر ──
            $table->text('notes')->nullable();

            $table->timestamps();

            // كل يوزر ليه سجل واحد بس لكل دواء
            $table->unique(['user_id', 'drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_drug_inventory');
    }
};