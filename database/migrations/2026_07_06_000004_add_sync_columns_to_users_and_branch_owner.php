<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * تزويد حسابات الفرع (offline login) — الخيار «أ»: السيرفر يبعت حساب الصيدلية المالكة
 * وموظفيها للفرع.
 *
 * - يضيف uuid + synced_at إلى users حتى يمكن سحبها للفرع (upsert بالـ uuid). users
 *   ليست Syncable كاملة (لا حذف ناعم/branch_id) — تُسحب فقط server→branch (server master).
 * - يضيف user_id إلى branches: يربط الفرع بالـ tenant (الصيدلية) الذي يتبعه، فيُرسل
 *   السيرفر للفرع ذلك المستخدم + sub_users الخاصة به فقط (لا حسابات tenants أخرى).
 *
 * migration إضافي غير كاسر؛ يملأ uuid للمستخدمين الحاليين على السيرفر.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->char('uuid', 26)->nullable()->after('id');
                $table->timestamp('synced_at')->nullable();
            });
        }

        // املأ uuid للمستخدمين الحاليين (جدول صغير — صف بصف آمن).
        DB::table('users')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update(['uuid' => (string) Str::ulid()]);
        });

        // فهرس فريد بعد الملء.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('uuid');
        });

        if (! Schema::hasColumn('branches', 'user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                // الـ tenant (users.id على السيرفر) الذي يتبعه هذا الفرع.
                $table->unsignedBigInteger('user_id')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn(['uuid', 'synced_at']);
            });
        }
        if (Schema::hasColumn('branches', 'user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
