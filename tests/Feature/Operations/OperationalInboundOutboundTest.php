<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class OperationalInboundOutboundTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_operational_inbound_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.inbound.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Inbound')
                ->has('rows')
                ->has('summary')
                ->has('warehouses')
                ->has('inboundTypes')
                ->has('summary.total_good')
                ->has('summary.total_bad')
                ->has('summary.total_value')
                ->where('filters.inbound_type', null)
                ->where('filters.source_warehouse_id', null)
                ->has('statusOptions')
                ->has('urls'));
    }

    public function test_operational_outbound_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.outbound.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Outbound')
                ->has('rows')
                ->has('summary')
                ->has('movementSummary')
                ->has('skuRows')
                ->has('warehouses')
                ->has('statusOptions')
                ->where('filters.channel', null)
                ->has('urls'));
    }

    public function test_inbound_and_outbound_search_can_match_sku(): void
    {
        $inboundSku = DB::table('warehouse_inbound_details as wid')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->value('p.code');

        $outboundSku = DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->value('p.code');

        if (! $inboundSku || ! $outboundSku) {
            $this->markTestSkipped('Data SKU inbound/outbound belum tersedia.');
        }

        $this->actingAs($this->admin())
            ->get(route('operations.inbound.index', ['q' => $inboundSku]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Inbound')
                ->where('summary.total_sku', fn ($value) => (int) $value >= 1)
                ->where('skuRows', fn ($value) => count($value) >= 1)
                ->where('skuRows', fn ($value) => collect($value)->contains(fn ($row) => count($row['documents'] ?? []) >= 1))
                ->where('rows.total', fn ($value) => (int) $value >= 1));

        $this->actingAs($this->admin())
            ->get(route('operations.outbound.index', ['q' => $outboundSku]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Outbound')
                ->where('movementSummary.total_qty', fn ($value) => (int) $value >= 1)
                ->where('movementSummary.total_sku', fn ($value) => (int) $value >= 1)
                ->where('skuRows', fn ($value) => count($value) >= 1)
                ->where('skuRows', fn ($value) => collect($value)->contains(fn ($row) => count($row['documents'] ?? []) >= 1))
                ->where('rows.total', fn ($value) => (int) $value >= 1));
    }
}
