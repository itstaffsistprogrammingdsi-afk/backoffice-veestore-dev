<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BusinessOverviewTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_business_overview_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.business-overview.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/BusinessOverview')
                ->has('filters')
                ->has('sales.summary')
                ->has('sales.channels')
                ->has('sales.trend')
                ->has('sales.top_stores')
                ->has('sales.top_products')
                ->has('orders.summary')
                ->has('orders.ecommerce_statuses')
                ->has('orders.attention')
                ->has('inventory.summary')
                ->has('inventory.low_stock')
                ->has('inventory.logistics')
                ->has('inventory.pending_queues')
                ->has('urls')
                ->where('urls.index', route('reports.business-overview.index')));
    }

    public function test_business_overview_accepts_period_filter(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.business-overview.index', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/BusinessOverview')
                ->where('filters.date_from', '2026-10-01')
                ->where('filters.date_to', '2026-10-31'));
    }
}
