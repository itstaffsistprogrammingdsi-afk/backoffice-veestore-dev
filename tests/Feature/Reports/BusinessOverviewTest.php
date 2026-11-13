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
                ->has('periodOptions')
                ->where('filters.period', 'month')
                ->where('periodOptions', fn ($options) => collect($options)->pluck('value')->all() === ['today', 'week', 'month', 'last_month', 'year', 'last_year'])
                ->has('stores')
                ->has('warehouses')
                ->has('sales.summary')
                ->has('sales.summary.gross_revenue')
                ->has('sales.summary.net_revenue')
                ->has('sales.summary.average_net_transaction')
                ->has('sales.channels')
                ->has('sales.channels.0.gross_revenue')
                ->has('sales.channels.0.net_revenue')
                ->has('sales.channels.0.net_share')
                ->has('sales.trend')
                ->has('sales.top_stores')
                ->has('sales.top_products')
                ->has('customers.summary')
                ->has('orders.summary')
                ->has('orders.ecommerce_statuses')
                ->has('orders.attention')
                ->has('inventory.summary')
                ->has('inventory.low_stock')
                ->has('inventory.logistics')
                ->has('inventory.pending_queues.store_transfer_not_recorded')
                ->has('payments.applicable')
                ->has('payments.orders')
                ->has('payments.matched')
                ->has('payments.exceptions')
                ->has('payments.rows')
                ->has('urls')
                ->where('urls.index', route('reports.business-overview.index'))
                ->where('urls.payments', route('monitoring.payment-reconciliation.index')));
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
                ->where('filters.period', 'custom')
                ->where('filters.date_from', '2026-10-01')
                ->where('filters.date_to', '2026-10-31'));
    }

    public function test_business_overview_supports_quick_period_filters(): void
    {
        $admin = $this->admin();

        foreach (['today', 'week', 'month', 'last_month', 'year', 'last_year'] as $period) {
            $this->actingAs($admin)
                ->get(route('reports.business-overview.index', ['period' => $period]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.period', $period)
                    ->has('filters.date_from')
                    ->has('filters.date_to')
                    ->has('customers.summary.new_members'));
        }
    }

    public function test_removed_period_presets_fall_back_to_this_month(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.business-overview.index', ['period' => '30d']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'month'));
    }

    public function test_business_overview_accepts_channel_store_and_warehouse_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.business-overview.index', [
                'period' => 'month',
                'channel' => 'store',
                'store_id' => '00000000-0000-0000-0000-000000000000',
                'warehouse_id' => '00000000-0000-0000-0000-000000000000',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'month')
                ->where('filters.channel', 'store')
                ->where('filters.store_id', '00000000-0000-0000-0000-000000000000')
                ->where('filters.warehouse_id', '00000000-0000-0000-0000-000000000000'));
    }

    public function test_legacy_dashboard_redirects_to_business_overview(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard-analytics'))
            ->assertRedirect(route('reports.business-overview.index'));
    }
}
