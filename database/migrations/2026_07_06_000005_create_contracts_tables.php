<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * موديول التعاقدات والتأمين (Phase 1) — الجداول الماستر.
 *
 * contracts       : عقد مع جهة (تأمين/حكومي/شركة/نقابة/مستشفى/جامعة). بيانات ماستر
 *                   على مستوى المالك (tenant) تُسحب server→branch (owner-scoped).
 * insurance_rules : قواعد التأمين (تغطية/تحمّل/سقف/موافقات) — 1:1 مع عقد تأمين.
 * pricing_rules   : قواعد تسعير حسب فئة المنتج لكل عقد (n لكل عقد).
 *
 * كل الجداول Syncable (uuid/branch_id/synced_at/deleted_at). التعاقدات تُدار مركزياً
 * على السيرفر وتُوزَّع على الفروع؛ الأكواد تُولَّد per-owner (فريدة داخل المالك).
 */
return new class extends Migration
{
    /** أعمدة المزامنة الموحّدة على كل جدول جديد. */
    private function syncColumns(Blueprint $table): void
    {
        $table->ulid('uuid')->nullable()->unique()->after('id');
        $table->string('branch_id', 40)->nullable()->index();
        $table->timestamp('synced_at')->nullable();
        $table->softDeletes();
    }

    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // المالك (tenant)
            $table->string('code');                    // كود العقد CONT-0001 (فريد داخل المالك)
            $table->string('type');                    // insurance|government|company|syndicate|hospital|university
            $table->string('name');
            $table->string('tax_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active|suspended|expired
            $table->string('contract_pdf')->nullable();  // مسار ملف العقد
            $table->text('notes')->nullable();
            $table->timestamps();
            $this->syncColumns($table);

            $table->unique(['user_id', 'code']); // الكود فريد داخل المالك (لا عالمياً — multi-tenant)
        });

        Schema::create('insurance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->decimal('coverage_percent', 5, 2)->default(0);        // نسبة تغطية التأمين %
            $table->decimal('patient_contribution_percent', 5, 2)->default(0); // نسبة تحمّل المريض %
            $table->decimal('max_per_prescription', 12, 2)->nullable();   // حد أقصى للروشتة
            $table->boolean('approval_required')->default(false);
            $table->decimal('approval_amount_limit', 12, 2)->nullable();  // فوقه يتطلب موافقة
            $table->timestamps();
            $this->syncColumns($table);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('product_category');   // medicines|cosmetics|medical_devices|baby_care
            $table->string('discount_type');      // percentage|fixed
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->timestamps();
            $this->syncColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('insurance_rules');
        Schema::dropIfExists('contracts');
    }
};
