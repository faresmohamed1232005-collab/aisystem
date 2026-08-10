<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_table_progress', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('direction', 10)->default('pull');
            $table->string('sync_mode', 10)->default('normal'); // initial | normal
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('succeeded_rows')->default(0);
            $table->unsignedBigInteger('failed_rows')->default(0);
            $table->text('last_error')->nullable();
            $table->text('cursor')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['table_name', 'direction']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('sync_runtime_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('status', 20)->default('idle');
            $table->text('message')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();
        });

        DB::table('sync_runtime_status')->insert([
            'id' => 1,
            'status' => 'idle',
            'consecutive_failures' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runtime_status');
        Schema::dropIfExists('sync_table_progress');
    }
};
