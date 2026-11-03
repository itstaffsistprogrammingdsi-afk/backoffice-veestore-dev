<?php

namespace Tests\Feature\Ecommerce;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class EcommercePaymentsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_ecommerce_payments_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ecommerce.payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ecommerce/Payments')
                ->has('rows')
                ->has('summary')
                ->has('paymentMethods')
                ->has('paymentStatuses')
                ->has('filters')
                ->has('urls'));
    }
}
