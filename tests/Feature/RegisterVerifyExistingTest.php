<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب (اتجاه B) — Stage 4: التسجيل المركزي «verify-exists».
 *
 * عند تفعيل sync.register_requires_existing_branch، لا يُنشئ الجهاز فرعاً — الفرع يُعرَّف
 * على السيرفر أولاً. جهاز بكود غير موجود يُرفض؛ أجهزة متعددة بنفس الكود تشترك في نفس
 * branch_id (بيانات الفرع مشتركة بينها).
 */
class RegisterVerifyExistingTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sync.token' => $this->token,
            'sync.server_url' => 'https://server.test',
            'sync.register_requires_existing_branch' => true, // النموذج المركزي
        ]);
    }

    private function headers(): array
    {
        return ['X-Sync-Token' => $this->token, 'Accept' => 'application/json'];
    }

    private function makeOwner(array $o = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'صيدلية', 'email' => 'owner@test.com', 'password' => Hash::make('secret123'),
            'is_approved' => 1, 'uuid' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now(),
        ], $o));
    }

    private function seedBranch(string $code, int $ownerId): string
    {
        $branchId = 'br_' . Str::ulid();
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => $code, 'user_id' => $ownerId, 'uuid' => (string) Str::ulid(),
            'status' => 'active', 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $branchId;
    }

    private function register(array $body)
    {
        return $this->withHeaders($this->headers())->postJson('/api/sync/register', array_merge([
            'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ], $body));
    }

    public function test_unknown_code_is_rejected_when_verify_required(): void
    {
        $this->makeOwner();

        $this->register(['code' => 'A'])->assertStatus(404);
        $this->assertSame(0, DB::table('branches')->where('code', 'A')->count()); // لم يُنشأ فرع
    }

    public function test_existing_branch_links_and_shares_id_across_devices(): void
    {
        $owner = $this->makeOwner();
        $branchId = $this->seedBranch('A', $owner);

        $d1 = $this->register(['code' => 'A', 'device_no' => 1])->assertOk()->json('branch_id');
        $d2 = $this->register(['code' => 'A', 'device_no' => 2])->assertOk()->json('branch_id');

        $this->assertSame($branchId, $d1);
        $this->assertSame($branchId, $d2); // نفس الفرع لكل الأجهزة
        $this->assertSame(1, DB::table('branches')->where('code', 'A')->count());
    }

    public function test_existing_branch_of_another_owner_is_rejected(): void
    {
        $this->makeOwner();
        $other = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) Str::ulid()]);
        $this->seedBranch('A', $other); // فرع A لمالك آخر

        $this->register(['code' => 'A'])->assertStatus(409); // المالك الحالي لا يملكه
    }
}
