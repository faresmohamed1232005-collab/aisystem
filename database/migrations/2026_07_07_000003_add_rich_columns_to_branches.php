<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2أ — D4: كيان الفرع الغني.
 *
 * أعمدة nullable إضافية تصف الفرع (نوعه/موقعه/مديره/حالته) وأعلام صلاحياته
 * (السماح بالتحويل الصادر/الوارد، التسعير، الجرد). تتدفّق للفرع عبر pull الـ owner-scoped.
 * register() يبقى شغّالاً (الأعمدة الجديدة كلها لها قيم افتراضية).
 *
 * manager_type/manager_id: إشارة polymorphic خفيفة (users أو sub_users) بدون FK صلب.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('type')->default('pharmacy')->after('name');       // pharmacy|warehouse|office
            $table->string('governorate')->nullable()->after('type');
            $table->string('address')->nullable()->after('governorate');
            $table->string('phone', 30)->nullable()->after('address');

            $table->string('manager_type')->nullable()->after('phone');       // App\Models\User | App\Models\SubUser
            $table->unsignedBigInteger('manager_id')->nullable()->after('manager_type');

            $table->string('status')->default('active')->after('manager_id'); // active|stopped

            $table->boolean('allow_transfer_out')->default(true)->after('status');
            $table->boolean('allow_transfer_in')->default(true)->after('allow_transfer_out');
            $table->boolean('allow_pricing')->default(true)->after('allow_transfer_in');
            $table->boolean('allow_stocktake')->default(true)->after('allow_pricing');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'governorate', 'address', 'phone',
                'manager_type', 'manager_id', 'status',
                'allow_transfer_out', 'allow_transfer_in', 'allow_pricing', 'allow_stocktake',
            ]);
        });
    }
};
