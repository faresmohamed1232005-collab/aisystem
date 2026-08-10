<?php

namespace Tests\Unit;

use App\Services\Diagnostics\PendingFactoryReset;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class PendingFactoryResetTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $this->directory = storage_path('framework/testing/factory-reset-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_signed_reset_archives_database_and_is_idempotent(): void
    {
        $database = $this->directory.'/database.sqlite';
        $backup = $this->directory.'/backup.sqlite';
        File::put($database, 'old database');
        File::put($database.'-wal', 'wal');
        File::put($backup, 'verified backup');

        $pending = app(PendingFactoryReset::class);
        $marker = $pending->schedule($database, $backup);
        $result = $pending->process($database);

        $this->assertSame('', File::get($database));
        $this->assertSame('old database', File::get($result['archive']));
        $this->assertSame('wal', File::get($result['archive'].'-wal'));
        $this->assertFileDoesNotExist($marker);
        $this->assertFileExists($marker.'.completed.json');
        $this->assertNull($pending->process($database));
    }

    public function test_tampered_marker_is_rejected_without_touching_database(): void
    {
        $database = $this->directory.'/database.sqlite';
        $backup = $this->directory.'/backup.sqlite';
        File::put($database, 'old database');
        File::put($backup, 'verified backup');

        $pending = app(PendingFactoryReset::class);
        $marker = $pending->schedule($database, $backup);
        $payload = json_decode(File::get($marker), true, flags: JSON_THROW_ON_ERROR);
        $payload['backup'] = $this->directory.'/other.sqlite';
        File::put($marker, json_encode($payload, JSON_THROW_ON_ERROR));

        try {
            $pending->process($database);
            $this->fail('A tampered reset marker must be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('توقيع', $e->getMessage());
        }

        $this->assertSame('old database', File::get($database));
    }

    public function test_processing_resumes_after_database_was_already_archived(): void
    {
        $database = $this->directory.'/database.sqlite';
        $backup = $this->directory.'/backup.sqlite';
        File::put($database, 'old database');
        File::put($backup, 'verified backup');

        $pending = app(PendingFactoryReset::class);
        $marker = $pending->schedule($database, $backup);
        $payload = json_decode(File::get($marker), true, flags: JSON_THROW_ON_ERROR);
        File::ensureDirectoryExists(dirname($payload['archive']));
        File::move($database, $payload['archive']);

        $result = $pending->process($database);

        $this->assertSame($payload['archive'], $result['archive']);
        $this->assertSame('', File::get($database));
        $this->assertSame('old database', File::get($payload['archive']));
    }
}
