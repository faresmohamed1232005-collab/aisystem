<?php

namespace Tests\Feature;

use App\Support\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3A — RBAC: Gates تُفحص عبر Actor (المالك أو الموظف) لا Auth::user() مباشرة.
 * تتأكد إن الموظف مقيّد حسب دوره والمالك يملك كل شيء، وإن الراوتس الإدارية محميّة.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'مالك', 'email' => 'owner@t.com', 'password' => bcrypt('secret123'), 'is_approved' => 1,
        ]);
    }

    /** طلب كموظف (sub_user) بدور معيّن: نفس المالك في الجلسة + بيانات الموظف. */
    private function asRole(User $owner, string $role)
    {
        return $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 99, 'name' => 'موظف', 'email' => 's@t.com', 'role' => $role,
        ]]);
    }

    public function test_role_matrix_permissions(): void
    {
        $this->assertTrue(Roles::roleCan(Roles::OWNER, 'manage_sub_users'));
        $this->assertTrue(Roles::roleCan(Roles::OWNER, 'anything_at_all'));
        $this->assertFalse(Roles::roleCan(Roles::PHARMACIST, 'manage_contracts'));
        $this->assertTrue(Roles::roleCan(Roles::PHARMACIST, 'do_sales'));
        $this->assertTrue(Roles::roleCan(Roles::ACCOUNTANT, 'view_reports'));
        $this->assertFalse(Roles::roleCan(Roles::ACCOUNTANT, 'create_transfers'));
        $this->assertTrue(Roles::roleCan(Roles::BRANCH_MANAGER, 'create_transfers'));
        $this->assertFalse(Roles::roleCan(Roles::BRANCH_MANAGER, 'manage_sub_users'));
    }

    public function test_owner_can_reach_management_pages(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('contracts.index'))->assertOk();
        $this->actingAs($owner)->get(route('branches.index'))->assertOk();
        $this->actingAs($owner)->get(route('employees.index'))->assertOk();
        $this->actingAs($owner)->get(route('sub-users.index'))->assertOk();
        $this->actingAs($owner)->get(route('sales.report'))->assertOk();
        $this->actingAs($owner)->get(route('stock-transfers.create'))->assertOk();
    }

    public function test_pharmacist_is_blocked_from_management(): void
    {
        $owner = $this->owner();

        $this->asRole($owner, Roles::PHARMACIST)->get(route('contracts.index'))->assertForbidden();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('branches.index'))->assertForbidden();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('employees.index'))->assertForbidden();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('sub-users.index'))->assertForbidden();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('sales.report'))->assertForbidden();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('stock-transfers.create'))->assertForbidden();

        // لكن يمكنه الوصول للشاشات التشغيلية غير المقيّدة.
        $this->asRole($owner, Roles::PHARMACIST)->get(route('stock-transfers.index'))->assertOk();
        $this->asRole($owner, Roles::PHARMACIST)->get(route('products.index'))->assertOk();
    }

    public function test_accountant_sees_reports_not_transfers(): void
    {
        $owner = $this->owner();
        $this->asRole($owner, Roles::ACCOUNTANT)->get(route('sales.report'))->assertOk();
        $this->asRole($owner, Roles::ACCOUNTANT)->get(route('contracts.index'))->assertOk();
        $this->asRole($owner, Roles::ACCOUNTANT)->get(route('stock-transfers.create'))->assertForbidden();
    }

    public function test_sub_user_role_validation_uses_new_roles(): void
    {
        $owner = $this->owner();
        // دور قديم لم يعد صالحاً.
        $this->actingAs($owner)->post(route('sub-users.store'), [
            'name' => 'x', 'email' => 'x@t.com', 'password' => 'secret123', 'role' => 'cashier',
        ])->assertSessionHasErrors('role');

        // دور جديد صالح.
        $this->actingAs($owner)->post(route('sub-users.store'), [
            'name' => 'y', 'email' => 'y@t.com', 'password' => 'secret123', 'role' => Roles::BRANCH_MANAGER,
        ])->assertSessionDoesntHaveErrors('role');

        $this->assertDatabaseHas('sub_users', ['email' => 'y@t.com', 'role' => Roles::BRANCH_MANAGER]);
    }
}
