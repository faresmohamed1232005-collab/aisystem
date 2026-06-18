<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // النوع: bonus=بونص | deduction=خصم | advance=سلفة | absence=غياب | salary=راتب
            $table->enum('type', ['bonus', 'deduction', 'advance', 'absence', 'salary']);

            $table->decimal('amount', 12, 2)->default(0);   // المبلغ
            $table->integer('absence_days')->default(0);     // أيام الغياب (لو النوع absence)
            $table->integer('month');                        // الشهر 1-12
            $table->integer('year');                         // السنة
            $table->text('notes')->nullable();               // ملاحظات

            // لما يتصرف الراتب → نحتفظ بـ id المصروف المرتبط
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_transactions');
    }
};