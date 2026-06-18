<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_order_items', function (Blueprint $table) {
            // استبدال product_id بـ drug_id
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->foreignId('drug_id')->after('pending_order_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 10, 2)->default(0)->after('price');
            $table->string('unit_key')->default('pack')->after('unit_price');
            $table->string('unit_name')->default('علبة')->after('unit_key');
            $table->decimal('qty_factor', 10, 6)->default(1)->after('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('pending_order_items', function (Blueprint $table) {
            $table->dropForeign(['drug_id']);
            $table->dropColumn(['drug_id', 'unit_price', 'unit_key', 'unit_name', 'qty_factor']);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        });
    }
};