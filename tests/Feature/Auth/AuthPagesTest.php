<?php

namespace Tests\Feature\Auth;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
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
}
