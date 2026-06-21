<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncPushService;
use Illuminate\Console\Command;

/**
 * أمر دفع التغييرات المحلية إلى السيرفر المركزي.
 * يُشغَّل يدوياً (php artisan sync:push) أو دورياً من الجدولة / خدمة الخلفية على الفرع.
 */
class SyncPush extends Command
{
    protected $signature = 'sync:push';
    protected $description = 'دفع التغييرات المحلية غير المتزامنة إلى السيرفر المركزي';

    public function handle(SyncPushService $service): int
    {
        $this->info('بدء رفع التغييرات إلى السيرفر...');

        $result = $service->run();

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);
            return self::SUCCESS;
        }

        $this->error('✗ ' . $result['message']);
        return self::FAILURE;
    }
}
