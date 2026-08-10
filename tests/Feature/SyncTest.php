<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\Sync\SyncPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * تغطية تدفّقات المزامنة (السيرفر master) بعد مراجعة 2026-07-06 المعمارية.
 *
 * يشتغل على SQLite :memory: (نفس نوع قاعدة الفرع الحقيقي) — معزول تماماً عن dev DB.
 * يغطّي: register (ج2)، تجاهل pull-only في push (ج1د)، accepted_uuids (ج1أ)،
 * السحب المخصّص للمالك + preserve_id + ترجمة FK (ج4)، keyset pagination (ج1ج).
 */
class SyncTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        // middleware sync.auth + الخدمات تقرأ config('sync.token'). نضبطه هنا بعد الإقلاع.
        config([
            'sync.token'      => $this->token,
            'sync.server_url' => 'https://server.test',
        ]);
    }

    private function headers(): array
    {
        return ['X-Sync-Token' => $this->token, 'Accept' => 'application/json'];
    }

    /** يُنشئ مستخدم صيدلية (owner/tenant) ويعيد id. */
    private function makeOwner(array $overrides = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name'        => 'صيدلية المعادي',
            'email'       => 'owner@test.com',
            'password'    => Hash::make('secret123'),
            'is_approved' => 1,
            'uuid'        => (string) \Illuminate\Support\Str::ulid(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides));
    }

    // ─────────────────────────────── register (الجزء 2) ───────────────────────────────

    public function test_register_creates_branch_and_links_owner(): void
    {
        $ownerId = $this->makeOwner();

        $res = $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code'           => 'A',
            'name'           => 'فرع المعادي',
            'owner_login'    => 'owner@test.com',
            'owner_password' => 'secret123',
        ]);

        $res->assertOk()->assertJson(['success' => true, 'branch_code' => 'A']);
        $branchId = $res->json('branch_id');
        $this->assertStringStartsWith('br_', $branchId);

        $this->assertDatabaseHas('branches', [
            'code'    => 'A',
            'user_id' => $ownerId,
        ]);
    }

    public function test_register_is_idempotent_by_code(): void
    {
        $this->makeOwner();

        $first = $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->json('branch_id');

        $second = $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->json('branch_id');

        $this->assertSame($first, $second, 'إعادة التسجيل بنفس الكود يجب أن تُعيد نفس branch_id');
        $this->assertSame(1, DB::table('branches')->where('code', 'A')->count());
    }

    public function test_register_rejects_wrong_password(): void
    {
        $this->makeOwner();

        $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'owner@test.com', 'owner_password' => 'WRONG',
        ])->assertStatus(401);
    }

    public function test_register_blocks_code_hijack_by_other_owner(): void
    {
        $owner1 = $this->makeOwner();
        // فرع A مسجّل بالفعل لـ owner1
        $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->assertOk();

        // صيدلية أخرى تحاول أخذ نفس الكود
        $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) \Illuminate\Support\Str::ulid()]);
        $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'other@test.com', 'owner_password' => 'secret123',
        ])->assertStatus(409);
    }

    public function test_sync_routes_require_token(): void
    {
        $this->makeOwner();
        // بلا ترويسة X-Sync-Token
        $this->postJson('/api/sync/register', [
            'code' => 'A', 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->assertStatus(401);
    }

    // ──────────────────── push: اتجاه + accepted_uuids (الجزء 1د + 1أ) ────────────────────

    public function test_push_applies_operational_and_ignores_pull_only_catalog(): void
    {
        $ownerId = $this->makeOwner();

        // كتالوج مركزي موجود على السيرفر — يجب ألا يكتب عليه الفرع.
        $drugUuid = (string) \Illuminate\Support\Str::ulid();
        DB::table('drugs')->insert([
            'uuid' => $drugUuid, 'name_ar' => 'أصلي', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $customerUuid = (string) \Illuminate\Support\Str::ulid();
        $saleUuid     = (string) \Illuminate\Support\Str::ulid();

        $payload = [
            'branch_id' => 'br_TEST',
            'tables'    => [
                'customers' => [[
                    'uuid' => $customerUuid, 'user_id' => $ownerId, 'code' => 'C-1',
                    'name' => 'عميل نقدي', 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                ]],
                'sales' => [[
                    'uuid' => $saleUuid, 'user_id' => $ownerId, 'invoice_number' => 'A-INV-1',
                    'total' => 50, 'paid' => 50, 'customer_id_uuid' => $customerUuid,
                    'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                ]],
                // محاولة الكتابة فوق الكتالوج pull-only — يجب أن تُتجاهل بصمت.
                'drugs' => [[
                    'uuid' => $drugUuid, 'name_ar' => 'مُخترَق', 'updated_at' => now()->toDateTimeString(),
                ]],
            ],
        ];

        $res = $this->withHeaders($this->headers())->postJson('/api/sync/push', $payload);

        $res->assertOk()->assertJson(['success' => true]);

        // العميل والفاتورة طُبّقا، مع ترجمة customer_id من uuid.
        $customerId = DB::table('customers')->where('uuid', $customerUuid)->value('id');
        $this->assertNotNull($customerId);
        $this->assertDatabaseHas('sales', [
            'uuid'        => $saleUuid,
            'customer_id' => $customerId,
            'user_id'     => $ownerId,
        ]);

        // accepted_uuids يُرجَع للفرع (إصلاح ج1أ).
        $this->assertContains($customerUuid, $res->json('detail.customers.accepted_uuids'));

        // الكتالوج pull-only لم يُمَس — الاسم الأصلي باقٍ (إصلاح ج1د).
        $this->assertSame('أصلي', DB::table('drugs')->where('uuid', $drugUuid)->value('name_ar'));
        $this->assertArrayNotHasKey('drugs', $res->json('detail'));
    }

    // ──────────── الفرع يعلّم المقبول فقط (SyncPushService) — إصلاح ج1أ ────────────

    public function test_push_service_marks_only_accepted_rows_synced(): void
    {
        $ownerId = $this->makeOwner();

        $acceptedUuid = (string) \Illuminate\Support\Str::ulid();
        $rejectedUuid = (string) \Illuminate\Support\Str::ulid();

        // صفّان معلّقان للرفع (synced_at = null).
        DB::table('customers')->insert([
            ['uuid' => $acceptedUuid, 'user_id' => $ownerId, 'code' => 'C-1', 'name' => 'مقبول', 'created_at' => now(), 'updated_at' => now(), 'synced_at' => null],
            ['uuid' => $rejectedUuid, 'user_id' => $ownerId, 'code' => 'C-2', 'name' => 'مرفوض', 'created_at' => now(), 'updated_at' => now(), 'synced_at' => null],
        ]);

        // السيرفر يقبل واحداً فقط ويرفض الآخر.
        Http::fake([
            '*/api/sync/push' => Http::response([
                'success'  => true,
                'accepted' => 1,
                'rejected' => 1,
                'detail'   => [
                    'customers' => ['accepted' => 1, 'rejected' => 1, 'accepted_uuids' => [$acceptedUuid]],
                ],
            ], 200),
        ]);

        $result = app(SyncPushService::class)->run();

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame(1, $result['pushed']);
        // المقبول اتعلّم synced، المرفوض لسه معلّق (هيُعاد رفعه) — لا ضياع صامت.
        $this->assertNotNull(DB::table('customers')->where('uuid', $acceptedUuid)->value('synced_at'));
        $this->assertNull(DB::table('customers')->where('uuid', $rejectedUuid)->value('synced_at'));
    }

    // ──────────────── pull: مخصّص للمالك + preserve_id + FK→uuid (الجزء 4) ────────────────

    public function test_pull_is_scoped_to_owner_and_preserves_user_id(): void
    {
        $ownerId = $this->makeOwner(); // الصيدلية المالكة للفرع
        $ownerUuid = DB::table('users')->where('id', $ownerId)->value('uuid');
        $otherId = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) \Illuminate\Support\Str::ulid()]);

        // كتالوج + إعلانات (يُسحبان للجميع)
        DB::table('drugs')->insert([
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'name_ar' => 'دواء1', 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'name_ar' => 'دواء2', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('ads')->insert([
            'uuid' => (string) \Illuminate\Support\Str::ulid(), 'image_path' => 'ads/1.jpg', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // موظفون: واحد للمالك، وواحد لصيدلية أخرى (يجب ألا يُسحب للفرع).
        DB::table('sub_users')->insert([
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'owner_id' => $ownerId, 'name' => 'كاشير المالك', 'email' => 'c1@test.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'owner_id' => $otherId, 'name' => 'كاشير غريب', 'email' => 'c2@test.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $branchId = 'br_OWNER';
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => 'A', 'user_id' => $ownerId,
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchId,
            'cursors'   => [],
        ])->assertOk();

        // users: فقط المالك، ومع الحفاظ على id (preserve_id) وبدون synced_at.
        $users = $res->json('tables.users.rows');
        $this->assertCount(1, $users);
        $this->assertSame($ownerId, $users[0]['id']);
        $this->assertTrue(Hash::check('secret123', $users[0]['password']));
        $this->assertArrayNotHasKey('synced_at', $users[0]);

        // sub_users: فقط موظف المالك، وowner_id متحوّل إلى owner_id_uuid (بدون owner_id خام).
        $subs = $res->json('tables.sub_users.rows');
        $this->assertCount(1, $subs);
        $this->assertSame('كاشير المالك', $subs[0]['name']);
        $this->assertSame($ownerUuid, $subs[0]['owner_id_uuid']);
        $this->assertArrayNotHasKey('owner_id', $subs[0]);

        // الكتالوج يُسحب كاملاً وبدون id (الربط عبر uuid).
        $this->assertCount(2, $res->json('tables.drugs.rows'));
        $this->assertArrayNotHasKey('id', $res->json('tables.drugs.rows.0'));
        $this->assertCount(1, $res->json('tables.ads.rows'));
    }

    // ──────────────── pull: keyset pagination بلا تخطّي (الجزء 1ج) ────────────────

    public function test_manifest_scope_matches_pull_and_reports_empty_tables(): void
    {
        $ownerId = $this->makeOwner();
        $otherId = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) \Illuminate\Support\Str::ulid()]);
        $branchId = 'br_OWNER';
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => 'A', 'user_id' => $ownerId,
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('sub_users')->insert([
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'owner_id' => $ownerId, 'name' => 'Mine', 'email' => 'mine@test.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) \Illuminate\Support\Str::ulid(), 'owner_id' => $otherId, 'name' => 'Other', 'email' => 'other-sub@test.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $manifest = $this->withHeaders($this->headers())->postJson('/api/sync/manifest', [
            'branch_id' => $branchId,
        ])->assertOk()->assertJson(['success' => true, 'version' => 1]);
        $pull = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchId,
            'cursors' => [],
        ])->assertOk();

        $this->assertSame(count($pull->json('tables.users.rows')), $manifest->json('tables.users.total'));
        $this->assertSame(count($pull->json('tables.sub_users.rows')), $manifest->json('tables.sub_users.total'));
        $this->assertSame(0, $manifest->json('tables.drugs.total'));
        $this->assertNull($manifest->json('tables.drugs.until'));
    }

    public function test_pull_respects_inclusive_until_cursor(): void
    {
        config(['sync.batch_size' => 10]);
        foreach ([
            ['01AAAAAAAAAAAAAAAAAAAAAA01', '2026-07-06 10:00:00'],
            ['01AAAAAAAAAAAAAAAAAAAAAA02', '2026-07-06 10:00:00'],
            ['01AAAAAAAAAAAAAAAAAAAAAA03', '2026-07-06 11:00:00'],
        ] as [$uuid, $ts]) {
            DB::table('drugs')->insert(['uuid' => $uuid, 'name_ar' => $uuid, 'created_at' => $ts, 'updated_at' => $ts]);
        }

        $response = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'cursors' => ['drugs' => null],
            'until' => ['drugs' => ['ts' => '2026-07-06 10:00:00', 'uuid' => '01AAAAAAAAAAAAAAAAAAAAAA02']],
        ])->assertOk();

        $this->assertSame(
            ['01AAAAAAAAAAAAAAAAAAAAAA01', '01AAAAAAAAAAAAAAAAAAAAAA02'],
            array_column($response->json('tables.drugs.rows'), 'uuid')
        );
        $this->assertFalse($response->json('tables.drugs.more'));
    }

    public function test_pull_keyset_pagination_does_not_skip_rows_sharing_timestamp(): void
    {
        config(['sync.batch_size' => 2]); // نُجبر التقسيم على دفعات

        $ts = '2026-07-06 10:00:00';
        // ثلاثة أدوية بنفس updated_at بالضبط، وuuid مرتّبة تصاعدياً.
        $u1 = '01AAAAAAAAAAAAAAAAAAAAAA01';
        $u2 = '01AAAAAAAAAAAAAAAAAAAAAA02';
        $u3 = '01AAAAAAAAAAAAAAAAAAAAAA03';
        foreach ([$u1, $u2, $u3] as $i => $u) {
            DB::table('drugs')->insert([
                'uuid' => $u, 'name_ar' => 'دواء' . ($i + 1), 'created_at' => $ts, 'updated_at' => $ts,
            ]);
        }

        // الدفعة الأولى (cursor فارغ): أول صفّين + more=true.
        $first = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'cursors' => ['drugs' => null],
        ])->assertOk();

        $firstRows = $first->json('tables.drugs.rows');
        $this->assertCount(2, $firstRows);
        $this->assertTrue($first->json('tables.drugs.more'));
        $cursor = $first->json('tables.drugs.cursor');
        $this->assertSame($u2, $cursor['uuid']);

        // الدفعة الثانية بالـ cursor: يجب أن تُرجع u3 (نفس الـ ts لكن uuid > u2) — لا تخطّي.
        $second = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'cursors' => ['drugs' => $cursor],
        ])->assertOk();

        $secondRows = $second->json('tables.drugs.rows');
        $this->assertCount(1, $secondRows);
        $this->assertSame($u3, $secondRows[0]['uuid']);
        $this->assertFalse($second->json('tables.drugs.more'));

        // تأكيد: الثلاثة كلهم رجعوا عبر الدفعتين (لا صفّ ضائع).
        $allUuids = array_merge(
            array_column($firstRows, 'uuid'),
            array_column($secondRows, 'uuid')
        );
        sort($allUuids);
        $this->assertSame([$u1, $u2, $u3], $allUuids);
    }
}
