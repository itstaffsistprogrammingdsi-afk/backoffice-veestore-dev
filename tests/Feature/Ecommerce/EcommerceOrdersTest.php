<?php

namespace Tests\Feature\Ecommerce;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class EcommerceOrdersTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_ecommerce_orders_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ecommerce.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ecommerce/Orders')
                ->has('orders')
                ->has('stores')
                ->has('warehouses')
                ->has('statuses')
                ->has('paymentMethods')
                ->has('stockIssueOrderIds')
                ->has('filters')
                ->has('urls'));
    }
}
