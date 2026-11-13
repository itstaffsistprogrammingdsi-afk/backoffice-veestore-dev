<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StockReportsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_stock_per_branch_page_uses_inertia_and_summary(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-percabang'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('title', 'Stok per Cabang')
                ->has('data.summary.total_qty_real')
                ->has('data.summary.total_qty_available')
                ->has('data.summary.total_bad')
                ->has('data.lastUpdate')
                ->has('urls.export'));
    }

    public function test_stock_wms_page_uses_inertia_and_summary(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-wms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('title', 'Stok Warehouse')
                ->has('data.summary.total_warehouse_stock')
                ->has('data.summary.total_qty_available')
                ->has('data.summary.total_bad')
                ->has('data.lastUpdate')
                ->has('urls.export'));
    }

    public function test_stock_filters_reject_invalid_uuid(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-percabang', ['store_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors('store_id');

        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-wms', ['warehouse_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors('warehouse_id');
    }

    public function test_stock_exports_return_excel_file(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-percabang.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-wms.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
