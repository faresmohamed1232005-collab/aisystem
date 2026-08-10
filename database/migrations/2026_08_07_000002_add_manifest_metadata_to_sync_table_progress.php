<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sync_table_progress', function (Blueprint $table) {
            $table->string('manifest_id')->nullable()->after('sync_mode');
            $table->unsignedInteger('manifest_version')->nullable()->after('manifest_id');
            $table->timestamp('manifest_generated_at')->nullable()->after('manifest_version');
            $table->text('until_cursor')->nullable()->after('cursor');
            $table->timestamp('last_attempt_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_table_progress', function (Blueprint $table) {
            $table->dropColumn([
                'manifest_id',
                'manifest_version',
                'manifest_generated_at',
                'until_cursor',
                'last_attempt_at',
            ]);
        });
    }
};
