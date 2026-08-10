<?php

namespace Tests\Feature;

use App\Services\Sync\SyncPullService;
use App\Services\Sync\SyncRepository;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DesktopUserSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_replaces_unreferenced_legacy_user_with_same_id(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'uuid' => (string) Str::ulid(),
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('legacy-password'),
            'is_approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerUuid = (string) Str::ulid();
        $result = app(SyncRepository::class)->applyBatch('users', [[
            'id' => 1,
            'uuid' => $ownerUuid,
            'name' => 'Web Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'is_approved' => 1,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]], true);

        $this->assertSame(1, $result['accepted']);
        $this->assertDatabaseMissing('users', ['email' => 'legacy@example.com']);
        $this->assertDatabaseHas('users', ['id' => 1, 'uuid' => $ownerUuid, 'email' => 'owner@example.com']);
        $this->assertTrue(Hash::check('secret123', DB::table('users')->where('id', 1)->value('password')));
    }

    public function test_legacy_user_is_not_replaced_when_operational_data_exists(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'uuid' => (string) Str::ulid(),
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('legacy-password'),
            'is_approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'uuid' => (string) Str::ulid(),
            'user_id' => 1,
            'code' => 'C-1',
            'name' => 'Existing Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(SyncRepository::class)->applyBatch('users', [[
            'id' => 1,
            'uuid' => (string) Str::ulid(),
            'name' => 'Web Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'is_approved' => 1,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]], true);

        $this->assertSame(1, $result['rejected']);
        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'legacy@example.com']);
    }

    public function test_verified_owner_replaces_legacy_identity_without_breaking_operational_relations(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'uuid' => (string) Str::ulid(),
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('legacy-password'),
            'is_approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('employees')->insert([
            'uuid' => (string) Str::ulid(),
            'user_id' => 1,
            'name' => 'Preserved Employee',
            'base_salary' => 1000,
            'hired_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ownerUuid = (string) Str::ulid();
        Settings::set('branch.owner_uuid', $ownerUuid);

        $result = app(SyncRepository::class)->applyBatch('users', [[
            'id' => 1,
            'uuid' => $ownerUuid,
            'name' => 'Web Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'is_approved' => 1,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]], true);

        $this->assertSame(1, $result['accepted']);
        $this->assertDatabaseHas('users', ['id' => 1, 'uuid' => $ownerUuid, 'email' => 'owner@example.com']);
        $this->assertDatabaseHas('employees', ['user_id' => 1, 'name' => 'Preserved Employee']);
        $this->assertTrue(Hash::check('secret123', DB::table('users')->where('id', 1)->value('password')));
    }

    public function test_initial_progress_is_exact_and_resumes_after_failed_batch(): void
    {
        config([
            'sync.server_url' => 'https://server.test',
            'sync.token' => 'token',
            'sync.branch_id' => 'br_TEST',
            'sync.pull' => ['drugs'],
            'sync.pull_scoped' => [],
        ]);
        $until = ['ts' => '2026-08-01 10:00:00', 'uuid' => '01UNTIL000000000000000000'];
        DB::table('sync_table_progress')->insert([
            'table_name' => 'drugs', 'direction' => 'pull', 'sync_mode' => 'initial',
            'manifest_id' => 'manifest-1', 'manifest_version' => 1, 'status' => 'pending',
            'total_rows' => 1, 'until_cursor' => json_encode($until),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $calls = 0;
        Http::fake(function ($request) use (&$calls, $until) {
            $this->assertSame($until, $request['until']['drugs']);
            $calls++;
            if ($calls === 1) {
                return Http::response(['success' => false, 'message' => 'offline'], 500);
            }
            $rows = [[
                'uuid' => '01DRUG0000000000000000001', 'name_ar' => 'دواء',
                'created_at' => '2026-08-01 09:00:00', 'updated_at' => '2026-08-01 09:00:00',
            ]];
            return Http::response(['success' => true, 'tables' => [
                'drugs' => ['rows' => $rows, 'cursor' => ['ts' => '2026-08-01 09:00:00', 'uuid' => $rows[0]['uuid']], 'more' => false],
            ]]);
        });

        try {
            app(SyncPullService::class)->pullStep('drugs');
            $this->fail('The first batch should fail.');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('sync_table_progress', ['table_name' => 'drugs', 'status' => 'failed', 'failed_rows' => 1]);
        }

        $result = app(SyncPullService::class)->pullStep('drugs');
        $this->assertSame(1, $result['progress']['succeeded_rows']);
        $this->assertSame(1, $result['progress']['total_rows']);
        $this->assertSame('completed', $result['progress']['status']);
        $this->assertDatabaseHas('drugs', ['uuid' => '01DRUG0000000000000000001']);
    }

    public function test_new_manifest_resets_existing_pull_cursor_but_resume_does_not(): void
    {
        config(['sync.server_url' => 'https://server.test', 'sync.token' => 'token', 'sync.branch_id' => 'br_TEST']);
        DB::table('sync_state')->insert([
            'table_name' => 'drugs',
            'last_pulled_at' => '2026-08-01 09:00:00',
            'last_pulled_uuid' => '01OLDCURSOR000000000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Http::fake(['*/api/sync/manifest' => Http::response([
            'success' => true, 'version' => 1, 'manifest_id' => 'manifest-reset',
            'generated_at' => '2026-08-07T00:00:00Z',
            'tables' => ['drugs' => ['total' => 1, 'until' => ['ts' => '2026-08-07 00:00:00', 'uuid' => '01UNTIL000000000000000000']]],
        ])]);

        $service = app(\App\Services\Sync\SyncManifestService::class);
        $service->initialize(['drugs']);
        $this->assertDatabaseMissing('sync_state', ['table_name' => 'drugs']);

        DB::table('sync_state')->insert([
            'table_name' => 'drugs',
            'last_pulled_at' => '2026-08-01 10:00:00',
            'last_pulled_uuid' => '01RESUMECURSOR000000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service->initialize(['drugs']);
        $this->assertDatabaseHas('sync_state', [
            'table_name' => 'drugs',
            'last_pulled_uuid' => '01RESUMECURSOR000000000000',
        ]);
        Http::assertSentCount(1);
    }

    public function test_manifest_404_falls_back_to_indeterminate_progress(): void
    {
        config(['sync.server_url' => 'https://server.test', 'sync.token' => 'token', 'sync.branch_id' => 'br_TEST']);
        Http::fake(['*/api/sync/manifest' => Http::response([], 404)]);

        $rows = app(\App\Services\Sync\SyncManifestService::class)->initialize(['drugs']);

        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['manifest_id']);
        $this->assertSame(0, $rows[0]['total_rows']);
        $this->assertSame('pending', $rows[0]['status']);
    }

    public function test_zero_total_manifest_is_immediately_complete(): void
    {
        config(['sync.server_url' => 'https://server.test', 'sync.token' => 'token', 'sync.branch_id' => 'br_TEST']);
        Http::fake(['*/api/sync/manifest' => Http::response([
            'success' => true, 'version' => 1, 'manifest_id' => 'manifest-empty',
            'generated_at' => '2026-08-07T00:00:00Z',
            'tables' => ['drugs' => ['total' => 0, 'until' => null]],
        ])]);

        $rows = app(\App\Services\Sync\SyncManifestService::class)->initialize(['drugs']);

        $this->assertSame('completed', $rows[0]['status']);
        $this->assertNotNull($rows[0]['completed_at']);
    }

    public function test_missing_cursor_user_is_pulled_again_from_start(): void
    {
        config([
            'sync.server_url' => 'https://server.test',
            'sync.token' => 'token',
            'sync.branch_id' => 'br_TEST',
        ]);
        DB::table('sync_state')->insert([
            'table_name' => 'users',
            'last_pulled_at' => '2026-05-03 19:11:23',
            'last_pulled_uuid' => '01MISSINGUSER00000000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake(function ($request) {
            $this->assertNull($request['cursors']['users']);

            return Http::response([
                'success' => true,
                'tables' => [
                    'users' => ['rows' => [], 'cursor' => null, 'more' => false],
                ],
            ]);
        });

        app(SyncPullService::class)->pullStep('users');
        Http::assertSentCount(1);
    }
}
