<?php

namespace Tests\Feature\Auth;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use InteractsWithBackofficeAdmin;

    public function test_auth_pages_use_inertia(): void
    {
        $this->get(route('auth-login-basic'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->has('urls'));

        $this->get(route('auth-register-basic'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->has('urls'));

        $this->get(route('auth-forgot-password-basic'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ForgotPassword')
                ->has('urls'));
    }

    public function test_invalid_login_returns_validation_error(): void
    {
        $this->post(route('login.attempt'), [
            'login' => 'tidak-ada-user-'.uniqid(),
            'password' => 'password-salah',
        ])->assertSessionHasErrors('login');
    }

    public function test_backoffice_uses_a_dedicated_session_cookie_name(): void
    {
        $this->assertSame('veestore_backoffice_session', config('session.cookie'));
    }

    public function test_backoffice_uses_a_dedicated_xsrf_cookie_name(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertCookie('VEE_XSRF-TOKEN');
    }

    public function test_home_redirects_authenticated_user_to_business_overview(): void
    {
        $this->actingAs($this->admin())
            ->get('/home')
            ->assertRedirect(route('reports.business-overview.index'));
    }

    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/home')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_visiting_login_is_sent_to_business_overview(): void
    {
        $this->actingAs($this->admin())
            ->get(route('login'))
            ->assertRedirect(route('reports.business-overview.index'));
    }
}
