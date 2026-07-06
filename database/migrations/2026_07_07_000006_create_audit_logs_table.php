<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B — سجل التدقيق (Audit Log): من فعل ماذا ومتى (قيم قديمة/جديدة).
 *
 * غير قابل للتعديل/الحذف (append-only): لا route حذف، ولا يُلمَس بعد الإنشاء. محلّي
 * لكل تثبيت (لا يُزامَن — auditable_id يشير لـ id محلي متعدّد الأنواع؛ التجميع المركزي
 * مؤجّل لـ Phase 4). يُكتب عبر Auditable trait على الموديلات الحسّاسة.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // المالك (tenant)
            $table->string('actor_type', 16)->default('owner'); // owner | sub_user
            $table->unsignedBigInteger('actor_id')->nullable();  // sub_user.id لو موظف
            $table->string('actor_name')->nullable();
            $table->string('event', 16);                          // created | updated | deleted
            $table->string('auditable_type');                     // كلاس الموديل
            $table->unsignedBigInteger('auditable_id');
            $table->string('label')->nullable();                  // وصف مقروء للسجل المتأثّر
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('branch_id', 40)->nullable();          // فرع التثبيت (للسياق)
            $table->timestamp('created_at')->nullable();          // لا updated_at (غير قابل للتعديل)

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
