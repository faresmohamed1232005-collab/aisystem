<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3C — 2FA (TOTP): تحقّق الكود، تفعيل بتأكيد، وتحدّي الدخول للحساب المُفعّل.
 */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $o = []): User
    {
        return User::create(array_merge([
            'name' => 'مالك', 'email' => 'owner@t.com', 'password' => bcrypt('secret123'), 'is_approved' => 1,
        ], $o));
    }

    public function test_totp_verifies_current_code_and_rejects_wrong(): void
    {
        $secret = Totp::generateSecret();
        $this->assertTrue(Totp::verify($secret, Totp::current($secret)));
        $this->assertFalse(Totp::verify($secret, '000000'));
        $this->assertFalse(Totp::verify($secret, 'abc'));
    }

    public function test_owner_enables_2fa_with_confirmation(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('security.2fa.enable'))->assertRedirect();
        $owner->refresh();
        $this->assertNotEmpty($owner->two_factor_secret);
        $this->assertFalse((bool) $owner->two_factor_enabled);

        // كود خاطئ لا يُفعّل.
        $this->actingAs($owner)->post(route('security.2fa.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse((bool) $owner->fresh()->two_factor_enabled);

        // كود صحيح يُفعّل.
        $this->actingAs($owner)->post(route('security.2fa.confirm'), ['code' => Totp::current($owner->two_factor_secret)])->assertRedirect();
        $this->assertTrue((bool) $owner->fresh()->two_factor_enabled);
    }

    public function test_login_challenges_when_2fa_enabled(): void
    {
        $secret = Totp::generateSecret();
        $owner  = $this->owner(['two_factor_secret' => $secret, 'two_factor_enabled' => true]);

        // كلمة المرور صحيحة → تحدّي 2FA لا دخول كامل.
        $this->post(route('login.post'), ['login' => 'owner@t.com', 'password' => 'secret123'])
            ->assertRedirect(route('login.2fa'));
        $this->assertGuest();

        // كود خاطئ → يبقى في التحدّي.
        $this->post(route('login.2fa.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // كود صحيح → دخول كامل.
        $this->post(route('login.2fa.verify'), ['code' => Totp::current($secret)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_login_without_2fa_is_direct(): void
    {
        $this->owner(['email' => 'plain@t.com']);
        $this->post(route('login.post'), ['login' => 'plain@t.com', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_security_and_challenge_pages_render(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('security.index'))->assertOk()->assertSee('المصادقة الثنائية');

        // شاشة التحدّي تُعرض فقط عند وجود جلسة معلّقة.
        $this->get(route('login.2fa'))->assertRedirect(route('login'));
        $this->withSession(['2fa:pending' => $owner->id])->get(route('login.2fa'))->assertOk()->assertSee('التحقق بخطوتين');
    }

    public function test_sub_user_cannot_manage_2fa(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 5, 'name' => 'موظف', 'email' => 's@t.com', 'role' => 'accountant',
        ]])->post(route('security.2fa.enable'))->assertForbidden();
    }
}
