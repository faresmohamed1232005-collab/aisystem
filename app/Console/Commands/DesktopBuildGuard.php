<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DesktopBuildGuard extends Command
{
    protected $signature = 'desktop:build-guard';
    protected $description = 'يرفض بناء Desktop إذا كانت العملية متصلة بقاعدة MySQL أو تحمل إعدادات فرع محلية';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            throw new RuntimeException("تم إيقاف البناء: اتصال قاعدة البيانات الحالي [{$driver}] وليس SQLite معزولة.");
        }

        $database = (string) DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && $database !== '' && ! str_ends_with(strtolower($database), '.sqlite')) {
            throw new RuntimeException('تم إيقاف البناء: مسار قاعدة SQLite غير متوقع.');
        }

        foreach (['SYNC_TOKEN', 'SYNC_SERVER_URL', 'BRANCH_ID', 'BRANCH_CODE'] as $key) {
            if (filled(env($key))) {
                throw new RuntimeException("تم إيقاف البناء: المتغير {$key} يجب ألا يكون موجوداً في بيئة إصدار Desktop العامة.");
            }
        }

        $this->info('Desktop build guard passed: isolated SQLite configuration and no branch credentials.');

        return self::SUCCESS;
    }
}
