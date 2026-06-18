<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('new_price', 10, 2)->nullable();
            $table->text('active_ingredient')->nullable();
            $table->text('company')->nullable();
            $table->text('category')->nullable();
            $table->unsignedInteger('major_units')->default(1);
            $table->unsignedInteger('minor_units')->default(1);
            $table->text('size_weight')->nullable();
            $table->text('concentration')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->string('origin')->nullable();
            $table->string('price_updated_at')->nullable();
            $table->unsignedInteger('popularity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
