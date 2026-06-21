<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

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

    public function handle(): int
    {
        $connection = config('database.default');
        if ($connection !== 'sqlite') {
            $this->warn("القاعدة الحالية ({$connection}) ليست SQLite — تخطّي النسخ الاحتياطي المحلي.");
            return self::SUCCESS;
        }

        $dbPath = config('database.connections.sqlite.database');
        if (!$dbPath || !File::exists($dbPath)) {
            $this->error("ملف قاعدة البيانات غير موجود: {$dbPath}");
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $stamp  = Carbon::now()->format('Ymd_His');
        $target = $backupDir . DIRECTORY_SEPARATOR . "branch_db_{$stamp}.sqlite";

        if (!File::copy($dbPath, $target)) {
            $this->error('فشل إنشاء النسخة الاحتياطية.');
            return self::FAILURE;
        }
        $this->info("✓ نسخة احتياطية: {$target}");

        // الاحتفاظ بآخر N نسخ فقط.
        $keep = max(1, (int) $this->option('keep'));
        $backups = collect(File::glob($backupDir . DIRECTORY_SEPARATOR . 'branch_db_*.sqlite'))
            ->sortDesc()
            ->values();

        $backups->slice($keep)->each(function ($old) {
            File::delete($old);
        });

        $this->line("  المحتفظ به: " . min($backups->count(), $keep) . " نسخة.");

        return self::SUCCESS;
    }
}
