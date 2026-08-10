<?php

namespace App\Console\Commands;

use App\Services\SqliteBackupService;
use Illuminate\Console\Command;

/**
 * نسخ احتياطي محلي دوري لقاعدة بيانات الفرع (SQLite).
 *
 * ينسخ ملف قاعدة SQLite إلى مجلد backups بطابع زمني، ويحتفظ بآخر N نسخ فقط.
 * يعمل فقط عندما تكون القاعدة sqlite (بيئة الفرع) — على السيرفر (MySQL) يتخطّى.
 *
 * يُجدوَل (مثلاً يومياً) على الفرع لحماية بيانات الصيدلية من التلف/الفقد.
 */
class BackupLocalDb extends Command
{
    protected $signature = 'sync:backup {--keep=14 : عدد النسخ المحتفظ بها}';
    protected $description = 'نسخ احتياطي لقاعدة بيانات الفرع المحلية (SQLite)';

    public function handle(SqliteBackupService $backups): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->warn('القاعدة الحالية ليست SQLite — تخطّي النسخ الاحتياطي المحلي.');

            return self::SUCCESS;
        }

        try {
            $backup = $backups->create(max(1, (int) $this->option('keep')));
            $this->info('نسخة احتياطية موثوقة: '.$backup['path']);
            $this->line('SHA256: '.$backup['sha256']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
