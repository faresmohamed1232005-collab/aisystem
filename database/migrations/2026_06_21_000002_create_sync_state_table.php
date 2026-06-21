<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3/Pull — جدول حالة المزامنة على الفرع.
 *
 * يخزّن لكل جدول آخر «نقطة سحب» (cursor): أحدث updated_at استلمناه من السيرفر.
 * في السحب التالي نطلب فقط ما تغيّر بعد هذا الوقت، فنتجنّب إعادة تنزيل كل شيء.
 *
 * يُستخدم على الفرع فقط (السيرفر master لا يسحب).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->unique();      // اسم الجدول الذي نتتبّعه
            $table->timestamp('last_pulled_at')->nullable(); // أحدث updated_at استُلم من السيرفر
            $table->timestamp('last_pull_run_at')->nullable(); // وقت آخر محاولة سحب
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_state');
    }
};
