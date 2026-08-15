<?php

use App\Services\Sync\UuidBackfiller;
use Illuminate\Database\Migrations\Migration;

/**
 * إصلاح البيانات: يملأ uuid (وطوابع الوقت الناقصة) لكل صف قابل للمزامنة بـ uuid فارغ.
 *
 * السبب: DrugsSeeder (وأي إدراج خام) كان يتخطّى هوك Syncable الذي يولّد الـ uuid، فصار
 * كتالوج drugs على الماستر بـ uuid=NULL، فتوقّف سحب الفرع عند drugs («صف بدون uuid»).
 * تشتغل تلقائياً على السيرفر عند migrate وعلى الفرع عند أول إقلاع. idempotent وآمنة لإعادة
 * التشغيل (تلمس فقط الصفوف الفارغة). النسخة الأصلية من الأعمدة أُضيفت في 2026_06_21_000001.
 */
return new class extends Migration {
    public function up(): void
    {
        UuidBackfiller::run();
    }

    public function down(): void
    {
        // لا تراجع: إزالة الـ uuid المولّد ستكسر المزامنة. الأعمدة نفسها تُدار في 2026_06_21_000001.
    }
};
