<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreOperationsPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_stock_operations_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreStock')
                ->has('stores')
                ->has('products')
                ->has('importNeeds')
                ->has('missingStock')
                ->has('storeThresholds')
                ->has('summary')
                ->has('urls'));
    }

    public function test_store_inbound_operations_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-inbound.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreInbound')
                ->has('rows')
                ->has('summary')
                ->has('skuRows')
                ->has('stores')
                ->has('warehouses')
                ->has('statusOptions')
                ->has('urls'));
    }

    public function test_store_outbound_operations_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-outbound.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreOutbound')
                ->has('rows')
                ->has('summary')
                ->has('movementSummary')
                ->has('skuRows')
                ->has('stores')
                ->has('statusOptions')
                ->has('urls'));
    }

    public function test_customers_and_visits_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/CustomersVisits')
                ->has('rows')
                ->has('summary')
                ->has('stores')
                ->has('urls'));
    }
}
