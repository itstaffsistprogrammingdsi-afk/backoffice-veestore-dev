<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ProductAgingReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_product_aging_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.product-aging.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('title', 'Product Aging')
                ->has('data.rows')
                ->has('data.summary')
                ->has('urls.export'));
    }

    public function test_product_aging_export_returns_excel_file(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.product-aging.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload();
    }
}
