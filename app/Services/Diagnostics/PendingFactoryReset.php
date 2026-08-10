<?php

namespace App\Services\Diagnostics;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PendingFactoryReset
{
    public function schedule(string $database, string $backupPath): string
    {
        $this->assertDatabasePath($database);
        $marker = $this->markerPath($database);
        $nonce = bin2hex(random_bytes(16));
        $recovery = dirname($database).DIRECTORY_SEPARATOR.'recovery';
        $payload = [
            'version' => 1,
            'database' => $database,
            'backup' => $backupPath,
            'archive' => $recovery.DIRECTORY_SEPARATOR.'factory_reset_'.now()->format('Ymd_His').'_'.substr($nonce, 0, 8).'.sqlite',
            'requested_at' => now()->toIso8601String(),
            'nonce' => $nonce,
        ];
        $payload['signature'] = $this->signature($payload);
        File::put($marker.'.tmp', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        File::move($marker.'.tmp', $marker);

        return $marker;
    }

    /** تُستدعى قبل فتح Laravel اتصال SQLite عند الإقلاع التالي. */
    public function process(?string $database): ?array
    {
        if (! is_string($database) || $database === '') {
            return null;
        }
        $marker = $this->markerPath($database);
        if (! File::isFile($marker)) {
            return null;
        }

        $payload = json_decode(File::get($marker), true, flags: JSON_THROW_ON_ERROR);
        $signature = (string) ($payload['signature'] ?? '');
        unset($payload['signature']);
        if ($signature === '' || ! hash_equals($this->signature($payload), $signature)) {
            throw new RuntimeException('توقيع طلب إعادة ضبط المصنع غير صالح.');
        }
        if (! hash_equals($database, (string) ($payload['database'] ?? ''))) {
            throw new RuntimeException('مسار قاعدة طلب إعادة الضبط غير مطابق.');
        }
        $this->assertDatabasePath($database);
        if (! File::isFile((string) ($payload['backup'] ?? ''))) {
            throw new RuntimeException('النسخة الاحتياطية المطلوبة لإعادة الضبط غير موجودة.');
        }

        $recovery = dirname($database).DIRECTORY_SEPARATOR.'recovery';
        File::ensureDirectoryExists($recovery);
        $archive = (string) ($payload['archive'] ?? '');
        if (dirname($archive) !== $recovery || strtolower(pathinfo($archive, PATHINFO_EXTENSION)) !== 'sqlite') {
            throw new RuntimeException('مسار أرشيف إعادة الضبط غير صالح.');
        }

        if (File::isFile($database)) {
            if (File::isFile($archive)) {
                throw new RuntimeException('قاعدة البيانات والأرشيف موجودان معًا؛ أوقفت إعادة الضبط لحماية البيانات.');
            }
            if (! File::move($database, $archive)) {
                throw new RuntimeException('تعذّرت أرشفة قاعدة البيانات القديمة؛ لم تُنفذ إعادة الضبط.');
            }
        } elseif (! File::isFile($archive)) {
            throw new RuntimeException('لم تُوجد قاعدة البيانات أو أرشيفها؛ أوقفت إعادة الضبط.');
        }
        foreach (['-wal', '-shm'] as $suffix) {
            if (File::isFile($database.$suffix) && ! File::move($database.$suffix, $archive.$suffix)) {
                throw new RuntimeException('تعذّرت أرشفة ملفات SQLite المساندة؛ أوقفت إعادة الضبط.');
            }
        }
        if (! File::isFile($database)) {
            File::put($database, '');
        }
        File::delete($marker);
        File::put($marker.'.completed.json', json_encode([
            'archive' => $archive,
            'backup' => $payload['backup'],
            'completed_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return ['archive' => $archive, 'backup' => $payload['backup']];
    }

    private function markerPath(string $database): string
    {
        return dirname($database).DIRECTORY_SEPARATOR.'.factory-reset-pending.json';
    }

    private function signature(array $payload): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        if ($key === '') {
            throw new RuntimeException('مفتاح التطبيق غير متاح لتوقيع طلب إعادة الضبط.');
        }

        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $key);
    }

    private function assertDatabasePath(string $database): void
    {
        if ($database === ':memory:' || strtolower(pathinfo($database, PATHINFO_EXTENSION)) !== 'sqlite') {
            throw new RuntimeException('مسار قاعدة SQLite غير صالح لإعادة الضبط.');
        }
        $parent = realpath(dirname($database));
        if ($parent === false || ! File::isDirectory($parent)) {
            throw new RuntimeException('مجلد قاعدة SQLite غير متاح.');
        }
    }
}
