<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();   // PND-0001
            $table->string('delivery_type')->default('store'); // store | delivery
            $table->text('delivery_address')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending | confirmed | cancelled
            $table->timestamps();
        });

        Schema::create('pending_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_order_items');
        Schema::dropIfExists('pending_orders');
    }
};