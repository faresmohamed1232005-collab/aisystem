<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P4 — سجلّ المزامنة (Sync log).
 *
 * يسجّل كل محاولة مزامنة (push/pull): نجاحها، عدد الصفوف، الرسالة، المدة.
 * يراه المستخدم في لوحة حالة المزامنة، ويساعد في تشخيص الأعطال.
 *
 * يُستخدم على الفرع (الذي يبادر بالمزامنة).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 10);            // push | pull
            $table->string('status', 10);               // success | failed
            $table->unsignedInteger('rows')->default(0); // عدد الصفوف المتأثرة
            $table->text('message')->nullable();        // رسالة النتيجة أو الخطأ
            $table->unsignedInteger('duration_ms')->nullable(); // مدة الدورة بالمللي ثانية
            $table->timestamp('created_at')->nullable();
            $table->index(['direction', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
