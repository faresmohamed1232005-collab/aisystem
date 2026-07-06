<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3B — Audit Log: تسجيل create/update/delete مع الفاعل والقيم القديمة/الجديدة،
 * غير قابل للتعديل، وعارض محمي بـ view_audit.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'مالك', 'email' => 'owner@t.com', 'password' => bcrypt('secret123'), 'is_approved' => 1,
        ]);
    }

    public function test_create_and_update_are_audited_with_actor_and_diff(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);

        $contract = Contract::create([
            'user_id' => $owner->id, 'code' => Contract::generateCode($owner->id),
            'type' => 'government', 'name' => 'جهة', 'status' => 'active',
        ]);

        $created = AuditLog::where('auditable_type', Contract::class)
            ->where('auditable_id', $contract->id)->where('event', 'created')->first();
        $this->assertNotNull($created);
        $this->assertSame('owner', $created->actor_type);
        $this->assertSame($owner->id, $created->user_id);
        $this->assertNull($created->actor_id);

        // تعديل → يسجّل الفرق (قديم/جديد) للاسم فقط.
        $contract->update(['name' => 'جهة جديدة']);
        $updated = AuditLog::where('auditable_id', $contract->id)->where('event', 'updated')->first();
        $this->assertNotNull($updated);
        $this->assertSame('جهة', $updated->old_values['name']);
        $this->assertSame('جهة جديدة', $updated->new_values['name']);
        // القيم الضوضائية (updated_at) لا تُسجَّل.
        $this->assertArrayNotHasKey('updated_at', $updated->new_values);
    }

    public function test_sub_user_actor_is_recorded(): void
    {
        $owner = $this->owner();

        // محاسب (لديه manage_contracts) ينشئ عقداً عبر الشاشة.
        $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 77, 'name' => 'محاسب', 'email' => 'a@t.com', 'role' => Roles::ACCOUNTANT,
        ]])->post(route('contracts.store'), [
            'type' => 'company', 'name' => 'شركة', 'status' => 'active',
        ])->assertRedirect();

        $log = AuditLog::where('auditable_type', Contract::class)->where('event', 'created')->first();
        $this->assertNotNull($log);
        $this->assertSame('sub_user', $log->actor_type);
        $this->assertSame(77, $log->actor_id);
        $this->assertSame('محاسب', $log->actor_name);
    }

    public function test_audit_viewer_is_gated(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('audit-logs.index'))->assertOk();

        // الصيدلي لا يملك view_audit.
        $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 1, 'name' => 'صيدلي', 'email' => 'p@t.com', 'role' => Roles::PHARMACIST,
        ]])->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_audit_log_has_no_update_timestamp(): void
    {
        // غير قابل للتعديل: لا عمود/سلوك updated_at.
        $this->assertFalse((new AuditLog)->usesTimestamps());
    }
}
