<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أعمدة التأمين على فاتورة البيع.
 *
 * عند البيع بالتأمين (payment_method='insurance') نخزّن العقد والمريض والمبلغين
 * المحسوبين تلقائياً (Contract Pricing Engine): ما يدفعه المريض وما تتحمّله الشركة.
 * كلها nullable → آمن للفواتير النقدية العادية وللمزامنة (migration إضافي).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable()->after('customer_id');
            $table->unsignedBigInteger('insured_patient_id')->nullable()->after('contract_id');
            $table->decimal('covered_amount', 14, 2)->default(0)->after('insured_patient_id'); // يتحمّلها التأمين
            $table->decimal('patient_amount', 14, 2)->default(0)->after('covered_amount');      // يدفعها المريض
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['contract_id', 'insured_patient_id', 'covered_amount', 'patient_amount']);
        });
    }
};
