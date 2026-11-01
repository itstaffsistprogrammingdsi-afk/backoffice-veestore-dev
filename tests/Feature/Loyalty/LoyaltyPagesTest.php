<?php

namespace Tests\Feature\Loyalty;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class LoyaltyPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_loyalty_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('loyalty.customer-loyalty-points.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Points')
                ->has('rows')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('loyalty.customer-loyalty-point-mutations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Mutations')
                ->has('rows')
                ->has('clps')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('loyalty.customer-visits.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Visits')
                ->has('visits')
                ->has('stores')
                ->has('filters')
                ->has('urls'));
    }
}
