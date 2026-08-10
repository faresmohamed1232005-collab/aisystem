<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

class SqliteBackupService
{
    /**
     * @return array{path:string,sha256:string,metadata_path:string}
     */
    public function create(?int $keep = null, array $metadata = []): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('النسخ الاحتياطي المحلي متاح لقواعد SQLite فقط.');
        }

        $source = (string) config('database.connections.sqlite.database');
        if ($source === '' || $source === ':memory:' || ! File::isFile($source)) {
            throw new RuntimeException('ملف قاعدة بيانات SQLite المحلية غير متاح.');
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $suffix = bin2hex(random_bytes(4));
        $target = $directory.DIRECTORY_SEPARATOR.'branch_db_'.now()->format('Ymd_His_u')."_{$suffix}.sqlite";
        $metadataPath = $target.'.json';

        try {
            // VACUUM INTO is a consistent SQLite snapshot and safely includes committed WAL data.
            $quotedTarget = DB::connection()->getPdo()->quote($target);
            DB::connection()->statement("VACUUM INTO {$quotedTarget}");
            $this->verify($target);

            $hash = hash_file('sha256', $target);
            if (! is_string($hash) || $hash === '') {
                throw new RuntimeException('تعذّر حساب بصمة النسخة الاحتياطية.');
            }

            $document = array_merge($metadata, [
                'database' => basename($target),
                'created_at' => now()->toIso8601String(),
                'sha256' => $hash,
            ]);
            File::put($metadataPath, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if ($keep !== null) {
                $this->retainLatest(max(1, $keep), $directory);
            }

            return ['path' => $target, 'sha256' => $hash, 'metadata_path' => $metadataPath];
        } catch (\Throwable $e) {
            File::delete([$target, $metadataPath]);
            throw new RuntimeException('فشل إنشاء نسخة SQLite احتياطية موثوقة: '.$e->getMessage(), 0, $e);
        }
    }

    private function verify(string $path): void
    {
        if (! File::isFile($path) || File::size($path) < 1) {
            throw new RuntimeException('ملف النسخة الاحتياطية فارغ أو مفقود.');
        }

        $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $result = $pdo->query('PRAGMA quick_check')->fetchColumn();
        $pdo = null;

        if ($result !== 'ok') {
            throw new RuntimeException('فشل فحص PRAGMA quick_check للنسخة الاحتياطية.');
        }
    }

    private function retainLatest(int $keep, string $directory): void
    {
        $backups = collect(File::glob($directory.DIRECTORY_SEPARATOR.'branch_db_*.sqlite'))
            ->sortByDesc(fn (string $path) => File::lastModified($path))
            ->values();

        foreach ($backups->slice($keep) as $old) {
            File::delete([$old, $old.'.json']);
        }
    }
}
