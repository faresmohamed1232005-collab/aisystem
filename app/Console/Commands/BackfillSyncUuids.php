<?php

namespace App\Console\Commands;

use App\Services\Sync\UuidBackfiller;
use Illuminate\Console\Command;

/**
 * أمر يدوي لإصلاح صفوف المزامنة الفارغة الـ uuid فوراً (بدل انتظار نشر كامل).
 * يُشغّل على السيرفر المركزي بعد إعادة seed للكتالوج: php artisan sync:backfill-uuids
 */
class BackfillSyncUuids extends Command
{
    protected $signature = 'sync:backfill-uuids';

    protected $description = 'يملأ uuid وطوابع الوقت الناقصة لكل صف قابل للمزامنة (idempotent)';

    public function handle(): int
    {
        $this->info('⏳ جارٍ ملء الـ uuid الناقص للجداول القابلة للمزامنة...');

        $result = UuidBackfiller::run(function (string $table, int $filled) {
            if ($filled > 0) {
                $this->line("  • {$table}: مُلئ {$filled} صف");
            }
        });

        $total = array_sum($result);
        $this->info("✅ اكتمل. إجمالي الصفوف التي مُلئت: {$total}");

        return self::SUCCESS;
    }
}
