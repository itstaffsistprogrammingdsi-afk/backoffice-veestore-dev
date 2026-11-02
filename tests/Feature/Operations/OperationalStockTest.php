<?php

namespace Tests\Feature\Operations;

use App\Models\WarehouseStockBarrier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class OperationalStockTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_operational_stock_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Stock')
                ->has('products')
                ->has('importNeeds')
                ->has('missingStock')
                ->has('summary')
                ->has('summary.reconciliation')
                ->has('summary.aging_buckets')
                ->has('warehouses')
                ->has('urls'));
    }

    public function test_items_below_warehouse_barrier_appear_in_import_needs(): void
    {
        $barrierCount = WarehouseStockBarrier::query()->count();

        if ($barrierCount === 0) {
            $this->markTestSkipped('Tidak ada warehouse stock barrier untuk diuji.');
        }

        WarehouseStockBarrier::query()->update([
            'stock_barrier' => 999999,
            'tolerance_percentage' => 0,
        ]);

        $this->actingAs($this->admin())
            ->get(route('operations.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Stock')
                ->where('summary.below_barrier_count', $barrierCount)
                ->has('importNeeds', $barrierCount));
    }
}
