<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class CustomerPreferenceTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_customer_preference_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.crm.customer-preference.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CustomerPreference')
                ->has('filters')
                ->has('dimensionOptions')
                ->has('metricOptions')
                ->has('summary')
                ->has('matrixSegments')
                ->has('matrixRows')
                ->has('breakdown')
                ->has('rows')
                ->has('urls')
                ->where('urls.index', route('reports.crm.customer-preference.index'))
                ->where('urls.export', route('reports.crm.customer-preference.export')));
    }

    public function test_customer_preference_export_can_be_downloaded(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.crm.customer-preference.export', [
                'dimension' => 'gender',
                'metric' => 'revenue',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_customer_preference_accepts_complete_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.crm.customer-preference.index', [
                'dimension' => 'gender',
                'metric' => 'revenue',
                'channel' => 'pos',
                'q' => 'headset',
                'store_id' => 'not-a-uuid',
                'product_id' => 'not-a-uuid',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CustomerPreference')
                ->where('filters.dimension', 'gender')
                ->where('filters.metric', 'revenue')
                ->where('filters.channel', 'pos')
                ->where('filters.q', 'headset')
                ->where('filters.store_id', '')
                ->where('filters.product_id', ''));
    }
}
