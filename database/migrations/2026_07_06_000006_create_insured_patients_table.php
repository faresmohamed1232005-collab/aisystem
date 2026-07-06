<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * insured_patients — المرضى المؤمّن عليهم المرتبطون بعقد تأمين.
 *
 * بيانات ماستر على مستوى المالك تُسحب server→branch. تُستخدم أثناء البيع لتحديد
 * الشركة والتغطية للمريض المختار.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insured_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('card_number')->nullable();
            $table->string('membership_number')->nullable();
            $table->date('coverage_end_date')->nullable();
            $table->timestamps();

            // أعمدة المزامنة
            $table->ulid('uuid')->nullable()->unique()->after('id');
            $table->string('branch_id', 40)->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insured_patients');
    }
};
