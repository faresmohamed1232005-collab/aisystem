<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // صاحب الصيدلية
            $table->string('name');                        // اسم الموظف
            $table->string('job_title')->nullable();       // المسمى الوظيفي
            $table->string('phone')->nullable();           // رقم الموبايل
            $table->decimal('base_salary', 12, 2);        // الراتب الأساسي
            $table->date('hired_at');                      // تاريخ التعيين
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};