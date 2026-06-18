<?php
// database/migrations/xxxx_add_unit_fields_to_sale_items_and_products.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'unit_key')) {
                $table->string('unit_key')->default('pack')->after('quantity');
            }
            if (!Schema::hasColumn('sale_items', 'unit_name')) {
                $table->string('unit_name')->default('علبة')->after('unit_key');
            }
            if (!Schema::hasColumn('sale_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('unit_name');
            }
            if (!Schema::hasColumn('sale_items', 'qty_factor')) {
                $table->decimal('qty_factor', 10, 4)->default(1)->after('unit_price');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_key')) {
                $table->string('unit_key')->default('pack')->after('quantity');
            }
            if (!Schema::hasColumn('products', 'units_json')) {
                $table->json('units_json')->nullable()->after('unit_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['unit_key', 'unit_name', 'unit_price', 'qty_factor']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });
    }
};