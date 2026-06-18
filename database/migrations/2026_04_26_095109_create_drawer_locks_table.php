<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawer_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // الأدمن صاحب الحساب
            $table->string('locked_by_name');           // اسم من قفّل (auto-fill من السب-يوزر)
            $table->string('locked_by_email');          // إيميل من قفّل
            $table->string('seller_name');              // اسم البائع
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawer_locks');
    }
};