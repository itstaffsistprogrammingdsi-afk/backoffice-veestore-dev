<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PriceMarginControllerTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_price_margin_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.price-margin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/PriceMargin')
                ->has('rows')
                ->has('summary')
                ->has('categories')
                ->has('brands')
                ->has('statusOptions')
                ->has('filters')
                ->where('filters.min_margin', 20)
                ->has('urls'));
    }

    public function test_price_margin_page_accepts_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.price-margin.index', [
                'status' => 'below_min_margin',
                'q' => 'produk',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'below_min_margin')
                ->where('filters.q', 'produk'));
    }
}
