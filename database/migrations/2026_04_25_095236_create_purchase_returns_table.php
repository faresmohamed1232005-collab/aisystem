<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════
        // مرتجعات الشراء
        // ══════════════════════════════════════════
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('return_number')->unique();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('refund_method', ['cash', 'balance', 'none'])->default('balance');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ══════════════════════════════════════════
        // أصناف مرتجعات الشراء
        // ══════════════════════════════════════════
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_item_id')->constrained('purchase_invoice_items')->cascadeOnDelete();

            // ✅ drug_id بدل product_id
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->foreign('drug_id')->references('id')->on('drugs')->nullOnDelete();

            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};