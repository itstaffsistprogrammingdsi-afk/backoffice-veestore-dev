<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_default_locale_is_indonesian(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'id'));
    }

    public function test_language_can_be_switched_to_english(): void
    {
        $this->actingAs($this->admin())
            ->from(route('operations.index'))
            ->post(route('language.update', ['locale' => 'en']))
            ->assertRedirect(route('operations.index'))
            ->assertSessionHas('locale', 'en');

        $this->actingAs($this->admin())
            ->withSession(['locale' => 'en'])
            ->get(route('operations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_guest_can_switch_language_from_login_page(): void
    {
        $this->from(route('login'))
            ->post(route('language.update', ['locale' => 'en']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('language.update', ['locale' => 'fr']))
            ->assertNotFound();
    }
}
