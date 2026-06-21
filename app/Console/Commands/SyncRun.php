<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncRunner;
use Illuminate\Console\Command;

/**
 * دورة مزامنة كاملة: Push (رفع تغييرات الفرع) ثم Pull (سحب تحديثات الكتالوج).
 * تُجدوَل دورياً على الفرع عند توفّر النت. تحترم الـ backoff (تشغيل تلقائي).
 *
 * --manual يتجاهل الـ backoff (للتشغيل اليدوي من سطر الأوامر).
 */
class SyncRun extends Command
{
    protected $signature = 'sync:run {--manual : تجاهل الـ backoff وأجبر المحاولة}';
    protected $description = 'دورة مزامنة كاملة مع السيرفر (رفع ثم سحب) مع تسجيل وbackoff';

    public function handle(SyncRunner $runner): int
    {
        if (!config('sync.enabled')) {
            $this->warn('المزامنة غير مُفعّلة (SYNC_ENABLED=false).');
            return self::SUCCESS;
        }

        $result = $runner->run(manual: (bool) $this->option('manual'));

        if (!empty($result['skipped'])) {
            $this->line('  ⏸ ' . $result['message']);
            return self::SUCCESS;
        }

        $this->line(($result['success'] ? '  ✓ ' : '  ✗ ') . $result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
