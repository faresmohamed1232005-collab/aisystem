<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المطالبات (Insurance Claims) — بيانات تشغيلية تُنشأ من فواتير التأمين.
 *
 * insurance_claims      : مطالبة لشركة تأمين تجمع فواتير غير مرسلة. تُدفع branch→server.
 * insurance_claim_items : ربط المطالبة بفواتير البيع (sales).
 *
 * اتجاه المزامنة: push (الفرع master لما ينشئ المطالبة من فواتيره).
 */
return new class extends Migration
{
    private function syncColumns(Blueprint $table): void
    {
        $table->ulid('uuid')->nullable()->unique();
        $table->string('branch_id', 40)->nullable()->index();
        $table->timestamp('synced_at')->nullable();
        $table->softDeletes();
    }

    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('claim_number');            // CLM-0001 (فريد داخل المالك)
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('draft'); // draft|submitted|paid|rejected
            $table->text('rejection_reason')->nullable();
            $table->date('claim_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $this->syncColumns($table);

            $table->unique(['user_id', 'claim_number']);
        });

        Schema::create('insurance_claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('covered_amount', 14, 2)->default(0); // ما تطالب به الشركة عن الفاتورة
            $table->timestamps();
            $this->syncColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_items');
        Schema::dropIfExists('insurance_claims');
    }
};
