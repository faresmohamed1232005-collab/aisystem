<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // database/migrations/xxxx_add_pharmacy_fields_to_users_table.php
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('pharmacy_name')->nullable();
            $table->text('address')->nullable();
            $table->string('syndicate_card')->nullable(); // صورة الكارنيه
            $table->string('governorate')->nullable();    // المحافظة
            $table->string('city')->nullable();           // المركز
            $table->boolean('is_approved')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'phone', 'pharmacy_name', 'address', 'syndicate_card', 'governorate', 'city', 'is_approved']);
        });
    }
};
