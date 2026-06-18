<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->nullOnDelete();
            $table->string('return_number')->unique();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('refund_method', ['cash', 'balance', 'none'])->default('cash');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')
                  ->constrained('sale_returns')
                  ->cascadeOnDelete();
            $table->foreignId('sale_item_id')
                  ->constrained('sale_items')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->foreign('drug_id')
                  ->references('id')
                  ->on('drugs')
                  ->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};