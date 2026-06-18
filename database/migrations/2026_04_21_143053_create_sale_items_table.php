<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('drug_id')->nullable();
            $table->foreign('drug_id')
                  ->references('id')
                  ->on('drugs')
                  ->nullOnDelete();
            $table->integer('quantity');
            $table->string('unit_key')->default('pack');
            $table->string('unit_name')->default('علبة');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('qty_factor', 8, 4)->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};