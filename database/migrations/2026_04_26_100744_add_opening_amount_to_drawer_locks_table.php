<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ════════════════════════════════════════════════════════════
//  لو الجدول موجود بالفعل → اعمل migration جديد بالاسم ده
//  php artisan make:migration add_opening_amount_to_drawer_locks
// ════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drawer_locks', function (Blueprint $table) {
            // المبلغ المتروك في الدرج للوردية القادمة
            $table->decimal('opening_amount', 12, 2)->default(0)->after('expected_amount');
        });
    }

    public function down(): void
    {
        Schema::table('drawer_locks', function (Blueprint $table) {
            $table->dropColumn('opening_amount');
        });
    }
};