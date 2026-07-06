<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2أ — D2: جداول التحويلات بين الفروع + لقطات مخزون الفروع.
 *
 * stock_transfers          : تحويل مخزون من فرع لفرع (bidirectional — الفرع master
 *                            لما ينشئ/يعدّل، والسيرفر يخزّن ويوزّع للطرف الآخر).
 * stock_transfer_items     : بنود التحويل (دواء + صلاحية + كمية + تكلفة).
 * branch_inventory_snapshots: صورة مجمّعة لمخزون كل فرع يبنيها السيرفر (read-only على
 *                            الفرع)، تُستخدم لاقتراح فرع بديل ورؤية مخزون الفروع الأخرى.
 *
 * قرار مفتاح: from/to_branch_id نص branch_id الثابت عالمياً (مش FK int) → لا يحتاج
 * ترجمة uuid، ويبسّط relations. أعمدة المزامنة على كل جدول (uuid/branch_id/synced_at/
 * deleted_at) — branch_id هنا = وسم الفرع المُنشئ (origin) للصف.
 */
return new class extends Migration {
    private function syncColumns(Blueprint $table): void
    {
        $table->ulid('uuid')->nullable()->unique();
        $table->string('branch_id', 40)->nullable()->index(); // الفرع المُنشئ للصف (origin)
        $table->timestamp('synced_at')->nullable();
        $table->softDeletes();
    }

    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // المالك (tenant)
            $table->string('from_branch_id', 40);   // branch_id الثابت للمصدر
            $table->string('to_branch_id', 40);      // branch_id الثابت للوجهة
            $table->string('transfer_number');       // TR-A-0001 (بادئة فرع المصدر)
            $table->string('status')->default('draft'); // draft|sent|received|rejected
            $table->text('notes')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $this->syncColumns($table);

            $table->index(['from_branch_id', 'to_branch_id']);
            $table->index('status');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 4)->default(0);          // الكمية المرسلة (بوحدة العلبة)
            $table->decimal('cost_price', 10, 2)->nullable();        // تكلفة الباتش (سلامة FIFO عند الاستلام)
            $table->decimal('received_quantity', 12, 4)->nullable(); // الكمية المستلمة فعلاً
            $table->decimal('damaged_quantity', 12, 4)->nullable();  // كمية تالفة عند الاستلام
            $table->timestamps();
            $this->syncColumns($table);
        });

        Schema::create('branch_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('snapshot_branch_id', 40); // الفرع الذي تصفه اللقطة
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(0); // إجمالي كمية الدواء بهذا الفرع
            $table->timestamps();
            $this->syncColumns($table);

            $table->unique(['user_id', 'snapshot_branch_id', 'drug_id'], 'branch_snap_owner_branch_drug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_inventory_snapshots');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
