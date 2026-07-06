<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * branches — سجل الفروع المسجّلة على السيرفر المركزي (master).
 *
 * كل فرع يسجّل نفسه مرة واحدة عبر POST /api/sync/register (من «شاشة إعداد أول تشغيل»
 * على الجهاز). السيرفر يولّد branch_id ثابت ويعيده للفرع ليخزّنه دائماً. هذا يجعل
 * نسخة .exe واحدة تكفي لكل الفروع (الهوية تُخصَّص وقت التشغيل لا وقت البناء).
 *
 * code فريد = بادئة الفواتير (A, B, ...) وأيضاً مُعرّف الفرع البشري (جهاز واحد/فرع).
 * token: توكن خاص بالفرع (تحسين أمني اختياري — لكل فرع توكنه بدل توكن عام واحد).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_id', 32)->unique();   // br_<ULID> يولّده السيرفر
            $table->string('code', 16)->unique();          // بادئة الفواتير (فريدة)
            $table->string('name')->nullable();
            $table->string('token', 80)->nullable();       // توكن خاص بالفرع (اختياري)
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
