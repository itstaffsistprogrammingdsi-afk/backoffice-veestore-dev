<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class IntegrationReadinessTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_integration_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.integrations.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Integrations')
                ->has('providers.doku')
                ->has('providers.biteship')
                ->has('summary')
                ->has('urls'));
    }

    public function test_integration_verify_returns_provider_statuses(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('settings.integrations.verify'))
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'checked_at',
                'providers' => [
                    'doku' => ['status', 'checks', 'activity'],
                    'biteship' => ['status', 'checks', 'activity'],
                ],
            ]);
    }
}
