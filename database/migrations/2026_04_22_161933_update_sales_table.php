<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // ربط بالعميل (اختياري)
            $table->foreignId('customer_id')->nullable()->after('user_id')
                  ->constrained()->nullOnDelete();

            // تفاصيل الدفع الإضافية
            $table->string('payment_method')->default('cash')->change();
            // طريقة الدفع: cash | card | insurance | deferred
            // تفاصيل البطاقة
            $table->string('card_type')->nullable()->after('payment_method');
            // visa | instapay | wallet

            // حالة الدفع للآجل
            $table->string('payment_status')->default('paid')->after('card_type');
            // paid | deferred | partial

            // المبلغ المتبقي للآجل
            $table->decimal('remaining', 10, 2)->default(0)->after('paid');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id','card_type','payment_status','remaining']);
        });
    }
};