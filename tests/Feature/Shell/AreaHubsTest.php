<?php

namespace Tests\Feature\Shell;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AreaHubsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_operations_area_hub_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Index')
                ->has('stats')
                ->has('urls')
                ->where('branding.name', 'VeeStore')
                ->where('branding.tagline', 'Master Data & Report')
                ->where('branding.logo_url', asset('assets/img/logo.png'))
                ->where('urls.stock', route('operations.stock.index'))
                ->where('urls.inbound', route('operations.inbound.index'))
                ->where('urls.outbound', route('operations.outbound.index')));
    }

    public function test_reports_area_hub_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('summary')
                ->has('urls')
                ->where('urls.overview', route('reports.business-overview.index'))
                ->where('urls.preference', route('reports.crm.customer-preference.index')));
    }
}
