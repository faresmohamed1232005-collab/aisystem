<?php

namespace Tests\Unit;

use App\Services\Sync\SyncPullService;
use App\Services\Sync\SyncPushService;
use App\Services\Sync\SyncRunner;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SyncRunnerTest extends TestCase
{
    public function test_it_skips_when_another_sync_cycle_holds_the_lock(): void
    {
        config(['sync.enabled' => true]);
        $lock = Cache::lock('sync.runner', 300);
        $this->assertTrue($lock->get());

        try {
            $push = Mockery::mock(SyncPushService::class);
            $push->shouldNotReceive('run');
            $pull = Mockery::mock(SyncPullService::class);
            $pull->shouldNotReceive('run');

            $result = (new SyncRunner($push, $pull))->run(manual: true);

            $this->assertTrue($result['success']);
            $this->assertTrue($result['skipped']);
            $this->assertSame('توجد دورة مزامنة أخرى قيد التشغيل.', $result['message']);
        } finally {
            $lock->release();
        }
    }
}
