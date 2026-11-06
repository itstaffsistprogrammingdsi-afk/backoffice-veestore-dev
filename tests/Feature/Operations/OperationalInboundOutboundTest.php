<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function test_inbound_summary_reconciles_good_bad_and_pending_qc_items(): void
    {
        $productId = DB::table('products')->value('id');

        if (! $productId) {
            $this->markTestSkipped('Data produk belum tersedia.');
        }

        $goodConditionId = DB::table('item_conditions')->where('status', 'good')->value('id');
        $badConditionId = DB::table('item_conditions')->where('status', 'bad')->value('id');
        $repairConditionId = DB::table('item_conditions')->where('status', 'good_after_repair')->value('id');

        if (! $goodConditionId || ! $badConditionId || ! $repairConditionId) {
            $this->markTestSkipped('Data kondisi item belum lengkap.');
        }

        $warehouseId = (string) Str::uuid();
        $inboundId = (string) Str::uuid();
        $detailId = (string) Str::uuid();

        DB::table('warehouses')->insert([
            'id' => $warehouseId,
            'name' => 'Test Warehouse Inbound',
            'code' => 'TST-'.Str::upper(Str::random(8)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouse_inbounds')->insert([
            'id' => $inboundId,
            'warehouse_id' => $warehouseId,
            'inbound_type' => 'supplier',
            'quantity_item' => 5,
            'grand_total' => 999999,
            'received_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouse_inbound_details')->insert([
            'id' => $detailId,
            'warehouse_inbound_id' => $inboundId,
            'product_id' => $productId,
            'quantity' => 5,
            'price_idr' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemIds = [];
        $itemConditions = [
            1 => $goodConditionId,
            2 => $goodConditionId,
            3 => $badConditionId,
            4 => $repairConditionId,
            5 => null,
        ];

        foreach ($itemConditions as $index => $conditionId) {
            $itemId = (string) Str::uuid();
            $itemIds[$index] = $itemId;

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $productId,
                'current_condition_id' => $conditionId,
                'status' => $conditionId ? 'warehouse_stock' : 'warehouse_processing',
                'warehouse_inbound_detail_id' => $detailId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([1 => $goodConditionId, 2 => $goodConditionId, 3 => $badConditionId, 4 => $repairConditionId] as $index => $conditionId) {
            DB::table('warehouse_qc')->insert([
                'id' => (string) Str::uuid(),
                'item_id' => $itemIds[$index],
                'qc_type' => 'inbound',
                'item_condition_id' => $conditionId,
                'warehouse_id' => $warehouseId,
                'status' => $conditionId === $badConditionId ? 'rejected' : 'accepted',
                'performed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('operations.inbound.index', ['warehouse_id' => $warehouseId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Inbound')
                ->where('summary.total_qty', 5)
                ->where('summary.total_items', 5)
                ->where('summary.total_good', 2)
                ->where('summary.total_good_after_repair', 1)
                ->where('summary.total_bad', 1)
                ->where('summary.total_pending_qc', 1)
                ->where('summary.total_other', 0)
                ->where('summary.total_value', 5000)
                ->where('summary.total_header_value', 999999)
                ->where('rows.data', fn ($rows) => collect($rows)->contains(fn ($row) => $row['id'] === $inboundId
                    && $row['item_count'] === 5
                    && $row['qty_good'] === 2
                    && $row['qty_good_after_repair'] === 1
                    && $row['qty_bad'] === 1
                    && $row['qty_pending_qc'] === 1
                    && $row['qty_other'] === 0
                    && $row['detail_value'] === 5000
                    && $row['grand_total'] === 999999)));
    }

    public function test_inbound_summary_counts_distinct_sku_across_documents(): void
    {
        $productId = DB::table('products')->value('id');

        if (! $productId) {
            $this->markTestSkipped('Data produk belum tersedia.');
        }

        $warehouseId = (string) Str::uuid();

        DB::table('warehouses')->insert([
            'id' => $warehouseId,
            'name' => 'Test Warehouse Distinct SKU',
            'code' => 'TST-'.Str::upper(Str::random(8)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([1, 2] as $number) {
            $inboundId = (string) Str::uuid();

            DB::table('warehouse_inbounds')->insert([
                'id' => $inboundId,
                'warehouse_id' => $warehouseId,
                'inbound_type' => 'supplier',
                'invoice_number' => 'DISTINCT-SKU-'.$number,
                'quantity_item' => 1,
                'grand_total' => 0,
                'received_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouse_inbound_details')->insert([
                'id' => (string) Str::uuid(),
                'warehouse_inbound_id' => $inboundId,
                'product_id' => $productId,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('operations.inbound.index', ['warehouse_id' => $warehouseId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/Inbound')
                ->where('summary.total_inbounds', 2)
                ->where('summary.total_qty', 2)
                ->where('summary.total_sku', 1));
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
