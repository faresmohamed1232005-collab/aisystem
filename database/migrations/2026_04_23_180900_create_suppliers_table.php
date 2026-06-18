<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════
        // جدول الموردين
        // ══════════════════════════════════════════
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('company')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ══════════════════════════════════════════
        // جدول فواتير الشراء
        // ══════════════════════════════════════════
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('extra', 12, 2)->default(0);      // ✅ مصاريف إضافية
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('remaining', 12, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid');
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'deferred'])->default('cash');
            $table->date('invoice_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ══════════════════════════════════════════
        // جدول أصناف فاتورة الشراء
        // ══════════════════════════════════════════
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();

            // product_id nullable — FK قديم على products
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();

            // ✅ drug_id — FK على drugs (الصح)
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->foreign('drug_id')->references('id')->on('drugs')->nullOnDelete();

            $table->string('product_name');
            $table->string('category')->nullable();
            $table->string('barcode')->nullable();             // ✅ باركود
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });

        // ══════════════════════════════════════════
        // جدول سداد فواتير الشراء
        // ══════════════════════════════════════════
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('suppliers');
    }
};