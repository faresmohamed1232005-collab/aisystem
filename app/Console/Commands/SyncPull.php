<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncPullService;
use Illuminate\Console\Command;

/**
 * أمر سحب تحديثات الكتالوج/المراجع من السيرفر المركزي إلى الفرع.
 * يُشغَّل يدوياً (php artisan sync:pull) أو دورياً على الفرع.
 */
class SyncPull extends Command
{
    protected $signature = 'sync:pull';
    protected $description = 'سحب تحديثات الكتالوج/المراجع من السيرفر المركزي';

    public function handle(SyncPullService $service): int
    {
        $this->info('بدء سحب التحديثات من السيرفر...');

        $result = $service->run();

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);
            return self::SUCCESS;
        }

        $this->error('✗ ' . $result['message']);
        return self::FAILURE;
    }
}
