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
                ->has('filters')
                ->has('summary')
                ->has('summary.gross_revenue')
                ->has('summary.net_revenue')
                ->has('summary.revenue')
                ->has('urls')
                ->where('filters.period', 'month')
                ->where('filters.channel', 'all')
                ->where('periodOptions', fn ($options) => collect($options)->pluck('value')->all() === ['today', 'week', 'month', 'last_month', 'year', 'last_year'])
                ->where('urls.hub', route('reports.index'))
                ->where('urls.overview', route('reports.business-overview.index'))
                ->where('urls.preference', route('reports.crm.customer-preference.index')));
    }

    public function test_reports_area_hub_accepts_quick_period_filters(): void
    {
        $admin = $this->admin();

        foreach (['today', 'week', 'month', 'year'] as $period) {
            $this->actingAs($admin)
                ->get(route('reports.index', ['period' => $period]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Reports/Index')
                    ->where('filters.period', $period)
                    ->has('filters.date_from')
                    ->has('filters.date_to'));
        }
    }

    public function test_reports_area_hub_accepts_custom_range(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.index', [
                'period' => 'custom',
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('filters.period', 'custom')
                ->where('filters.date_from', '2026-10-01')
                ->where('filters.date_to', '2026-10-31'));
    }

    public function test_reports_area_hub_accepts_channel_filter(): void
    {
        $admin = $this->admin();

        foreach (['store', 'ecommerce'] as $channel) {
            $this->actingAs($admin)
                ->get(route('reports.index', ['period' => 'month', 'channel' => $channel]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Reports/Index')
                    ->where('filters.channel', $channel)
                    ->has('summary.gross_revenue')
                    ->has('summary.net_revenue')
                    ->has('summary.revenue'));
        }
    }
}
