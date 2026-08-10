<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Diagnostics\OwnerVerificationService;
use App\Services\Diagnostics\RecoveryService;
use App\Services\SqliteBackupService;
use App\Services\Sync\SyncPullService;
use App\Services\Sync\SyncRunner;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RecoveryActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_employee_cannot_invoke_recovery_actions(): void
    {
        $owner = User::factory()->create();

        $this->post(route('diagnostics.actions.backup'))->assertRedirect(route('login'));
        $this->actingAs($owner)->withSession(['sub_user' => ['id' => 9, 'role' => 'manager']])
            ->post(route('diagnostics.actions.backup'))->assertForbidden();
    }

    public function test_bad_owner_password_rejects_retry_before_backup(): void
    {
        $owner = User::factory()->create(['password' => Hash::make('correct-password')]);
        $backup = Mockery::mock(SqliteBackupService::class);
        $backup->shouldNotReceive('create');
        $this->app->instance(SqliteBackupService::class, $backup);

        $this->actingAs($owner)->post(route('diagnostics.actions.retry'), [
            'table' => 'users',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_backup_failure_aborts_retry_without_clearing_state(): void
    {
        $owner = User::factory()->create(['password' => Hash::make('password')]);
        DB::table('sync_state')->insert(['table_name' => 'users', 'last_pulled_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $backup = Mockery::mock(SqliteBackupService::class);
        $backup->shouldReceive('create')->once()->andThrow(new RuntimeException('backup failed'));
        $this->app->instance(SqliteBackupService::class, $backup);

        $this->actingAs($owner)->from(route('diagnostics.page'))->post(route('diagnostics.actions.retry'), [
            'table' => 'users', 'password' => 'password',
        ])->assertRedirect(route('diagnostics.page'))->assertSessionHas('error');

        $this->assertDatabaseHas('sync_state', ['table_name' => 'users']);
    }

    public function test_retry_clears_only_requested_cursor_and_progress_without_touching_synced_rows(): void
    {
        $owner = User::factory()->create(['password' => Hash::make('password')]);
        DB::table('sync_state')->insert([
            ['table_name' => 'users', 'last_pulled_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['table_name' => 'drugs', 'last_pulled_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('sync_table_progress')->insert([
            'table_name' => 'users', 'direction' => 'pull', 'sync_mode' => 'initial', 'status' => 'failed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $syncedAt = now()->subMinute()->toDateTimeString();
        DB::table('users')->where('id', $owner->id)->update(['synced_at' => $syncedAt]);

        $backup = Mockery::mock(SqliteBackupService::class);
        $backup->shouldReceive('create')->once()->andReturn(['path' => 'test.sqlite', 'sha256' => 'abc', 'metadata_path' => 'test.json']);
        $this->app->instance(SqliteBackupService::class, $backup);
        $pull = Mockery::mock(SyncPullService::class);
        $pull->shouldReceive('pullableTables')->andReturn(['users', 'drugs']);
        $pull->shouldReceive('pullStep')->once()->with('users')->andReturn(['table' => 'users', 'pulled' => 0, 'total' => 0, 'more' => false]);
        $this->app->instance(SyncPullService::class, $pull);

        $this->actingAs($owner)->post(route('diagnostics.actions.retry'), ['table' => 'users', 'password' => 'password'])
            ->assertRedirect();

        $this->assertDatabaseMissing('sync_state', ['table_name' => 'users']);
        $this->assertDatabaseHas('sync_state', ['table_name' => 'drugs']);
        $this->assertSame($syncedAt, DB::table('users')->where('id', $owner->id)->value('synced_at'));
    }

    public function test_repair_keeps_verified_owner_when_later_full_sync_fails(): void
    {
        config(['nativephp-internal.running' => true, 'database.default' => 'sqlite']);
        $legacy = User::factory()->create([
            'id' => 1,
            'uuid' => '01LEGACYOWNER00000000000000',
            'email' => 'legacy@example.com',
            'password' => Hash::make('legacy-password'),
        ]);
        DB::table('employees')->insert([
            'uuid' => '01EMPLOYEE0000000000000000',
            'user_id' => $legacy->id,
            'name' => 'Preserved Employee',
            'base_salary' => 1000,
            'hired_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Settings::set('branch.id', 'br_same');
        Settings::set('branch.code', 'A');
        Settings::set('sync.token', 'secret');
        Settings::set('sync.server_url', 'https://server.test');

        $backup = Mockery::mock(SqliteBackupService::class);
        $backup->shouldReceive('create')->once()->andReturn(['path' => 'test.sqlite', 'sha256' => 'abc', 'metadata_path' => 'test.json']);
        $owners = Mockery::mock(OwnerVerificationService::class);
        $owners->shouldReceive('verify')->once()->andReturn(['branch_id' => 'br_same', 'owner_uuid' => '01WEBOWNER000000000000000']);
        $pull = Mockery::mock(SyncPullService::class);
        $pull->shouldReceive('pullStep')->once()->with('users')->andReturnUsing(function (): array {
            DB::table('users')->where('id', 1)->update([
                'uuid' => '01WEBOWNER000000000000000',
                'email' => 'owner@example.com',
                'password' => Hash::make('web-password'),
                'updated_at' => now(),
            ]);

            return ['table' => 'users', 'pulled' => 1, 'total' => 1, 'more' => false];
        });
        $runner = Mockery::mock(SyncRunner::class);
        $runner->shouldReceive('run')->once()->withArgs(fn ($manual) => $manual === true)->andReturn([
            'success' => false, 'pushed' => 0, 'pulled' => 1, 'message' => 'فشل جدول لاحق',
        ]);

        $result = (new RecoveryService($backup, $owners))->repairSync('owner@example.com', 'web-password', $pull, $runner);

        $this->assertFalse($result['sync']['success']);
        $this->assertSame('01WEBOWNER000000000000000', Settings::get('branch.owner_uuid'));
        $this->assertTrue(Hash::check('web-password', DB::table('users')->where('id', 1)->value('password')));
        $this->assertDatabaseHas('employees', ['user_id' => 1, 'name' => 'Preserved Employee']);
    }

    public function test_disconnect_preserves_business_and_pending_rows_then_returns_setup(): void
    {
        config(['nativephp-internal.running' => true, 'database.default' => 'sqlite']);
        $owner = User::factory()->create(['password' => Hash::make('password')]);
        Settings::set('branch.id', 'br_same'); Settings::set('branch.code', 'A');
        Settings::set('branch.owner_uuid', $owner->uuid); Settings::set('sync.token', 'secret');
        Settings::set('sync.server_url', 'https://server.test'); Settings::set('sync.enabled', '1');
        DB::table('products')->insert(['user_id' => $owner->id, 'name' => 'Local product', 'price' => 10, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $productId = DB::table('products')->where('name', 'Local product')->value('id');

        $backup = Mockery::mock(SqliteBackupService::class);
        $backup->shouldReceive('create')->once()->andReturn(['path' => 'test.sqlite', 'sha256' => 'abc', 'metadata_path' => 'test.json']);
        $this->app->instance(SqliteBackupService::class, $backup);

        $this->actingAs($owner)->post(route('diagnostics.actions.disconnect'), [
            'password' => 'password', 'confirmation' => RecoveryService::DISCONNECT_PHRASE,
        ])->assertRedirect(route('setup.show'));

        $this->assertDatabaseHas('products', ['id' => $productId, 'name' => 'Local product', 'synced_at' => null]);
        $this->assertNull(Settings::get('branch.id'));
        $this->assertSame('br_same', Settings::get('reconfigure.prior_branch_id'));
        $this->assertSame('1', Settings::get('reconfigure.guard'));
    }

    public function test_different_identity_registration_is_rejected_when_operational_rows_exist(): void
    {
        config(['nativephp-internal.running' => true]);
        $owner = User::factory()->create();
        DB::table('products')->insert(['user_id' => $owner->id, 'name' => 'Preserved', 'price' => 10, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Settings::set('reconfigure.guard', '1');
        Settings::set('reconfigure.prior_branch_id', 'br_old');
        Settings::set('reconfigure.prior_owner_uuid', 'owner-old');
        Http::fake(['*/api/sync/register' => Http::response([
            'success' => true, 'branch_id' => 'br_other', 'branch_code' => 'B', 'owner_uuid' => 'owner-other',
        ])]);

        $this->post(route('setup.store'), [
            'code' => 'B', 'server_url' => 'https://server.test', 'token' => 'token',
            'owner_login' => 'owner', 'owner_password' => 'password',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertNull(Settings::get('branch.id'));
        $this->assertSame('1', Settings::get('reconfigure.guard'));
        $this->assertDatabaseHas('products', ['name' => 'Preserved']);
    }
}
