<?php

namespace Tests\Feature\Operations;

use App\Models\Store;
use App\Models\StoreOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreTransferTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_transfer_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-transfers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreTransfers')
                ->has('rows')
                ->has('summary')
                ->has('stores')
                ->has('statusOptions')
                ->has('filters')
                ->has('urls'));
    }

    public function test_store_transfer_can_be_created_approved_shipped_and_received(): void
    {
        $fixture = $this->fixture(3);

        $this->actingAs($this->admin())
            ->getJson(route('operations.store-transfers.stock', ['source_store_id' => $fixture['source']->id]))
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $fixture['product']->id,
                'available_qty' => 3,
            ]);

        $this->actingAs($this->admin())
            ->post(route('operations.store-transfers.store'), [
                'source_store_id' => $fixture['source']->id,
                'destination_store_id' => $fixture['destination']->id,
                'note' => 'Transfer test',
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'qty' => 2,
                        'note' => 'Prioritas',
                    ],
                ],
            ])
            ->assertRedirect(route('operations.store-transfers.index'))
            ->assertSessionHasNoErrors();

        $order = StoreOrder::query()
            ->where('store_id', $fixture['destination']->id)
            ->where('order_number', 'like', 'TRF-%')
            ->with('details')
            ->firstOrFail();

        $this->assertSame('requested', $order->status);
        $this->assertNull($order->warehouse_id);
        $this->assertSame($order->order_number, $order->shipping_number);
        $this->assertSame(2, (int) $order->details->first()->requested_qty);

        $this->actingAs($this->admin())
            ->getJson(route('operations.store-orders.show', $order))
            ->assertNotFound();

        $outbound = DB::table('store_outbounds')
            ->where('shipping_number', $order->order_number)
            ->where('outbound_type', 'transfer')
            ->first();

        $this->assertNotNull($outbound);
        $this->assertSame($fixture['source']->id, $outbound->store_id);
        $this->assertNull($outbound->shipment_date);

        $this->actingAs($this->admin())
            ->patch(route('operations.store-transfers.approve', $order))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $order->refresh()->status);
        $this->assertSame(2, (int) $order->details()->first()->approved_qty);

        $this->scanItems((string) $outbound->id, $fixture['items'], (string) $this->admin()->id, 2);

        $this->actingAs($this->admin())
            ->patch(route('operations.store-transfers.ship', $order))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertNotNull(DB::table('store_outbounds')->where('id', $outbound->id)->value('shipment_date'));
        $this->assertSame(2, DB::table('store_outbound_details')->where('store_outbound_id', $outbound->id)->count());
        $this->assertSame(2, DB::table('items')->whereIn('id', $this->shippedItemIds($outbound->id))->where('status', 'in_delivery')->count());

        $this->actingAs($this->admin())
            ->getJson(route('operations.store-transfers.show', $order))
            ->assertOk()
            ->assertJsonPath('order.status', 'shipped')
            ->assertJsonCount(2, 'items');

        $this->actingAs($this->admin())
            ->patch(route('operations.store-transfers.receive', $order), [
                'received_qty' => [
                    (string) $order->details()->first()->id => 2,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame('received', $order->status);
        $this->assertSame(2, (int) $order->details()->first()->received_qty);

        $movedItems = DB::table('store_items')
            ->where('store_id', $fixture['destination']->id)
            ->whereIn('item_id', $this->shippedItemIds($outbound->id))
            ->count();

        $this->assertSame(2, $movedItems);
        $this->assertSame(2, DB::table('item_location_histories')->where('location_id', $fixture['location_id'])->count());
        $this->assertSame(2, DB::table('items')->whereIn('id', $this->shippedItemIds($outbound->id))->where('current_location_id', $fixture['location_id'])->count());
    }

    public function test_store_transfer_requires_scanned_items_before_shipping(): void
    {
        $fixture = $this->fixture(1);

        $this->actingAs($this->admin())
            ->post(route('operations.store-transfers.store'), [
                'source_store_id' => $fixture['source']->id,
                'destination_store_id' => $fixture['destination']->id,
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'qty' => 1,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $order = StoreOrder::query()
            ->where('store_id', $fixture['destination']->id)
            ->where('order_number', 'like', 'TRF-%')
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->patch(route('operations.store-transfers.approve', $order))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->patch(route('operations.store-transfers.ship', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame('approved', $order->refresh()->status);
    }

    public function test_store_transfer_is_blocked_when_warehouse_stock_is_available(): void
    {
        $fixture = $this->fixture(1);
        $this->addWarehouseStock((string) $fixture['product']->id, (string) $fixture['condition_id']);

        $this->actingAs($this->admin())
            ->getJson(route('operations.store-transfers.stock', ['source_store_id' => $fixture['source']->id]))
            ->assertOk()
            ->assertJsonFragment([
                'product_id' => $fixture['product']->id,
                'warehouse_available_qty' => 1,
                'can_transfer' => false,
            ]);

        $this->actingAs($this->admin())
            ->post(route('operations.store-transfers.store'), [
                'source_store_id' => $fixture['source']->id,
                'destination_store_id' => $fixture['destination']->id,
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'qty' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, StoreOrder::query()
            ->where('store_id', $fixture['destination']->id)
            ->where('order_number', 'like', 'TRF-%')
            ->count());
    }

    public function test_display_stock_is_not_available_for_inter_store_transfer(): void
    {
        $fixture = $this->fixture(1);

        DB::table('items')
            ->where('id', $fixture['items'][0]['item_id'])
            ->update(['status' => 'store_display']);

        DB::table('store_items')
            ->where('item_id', $fixture['items'][0]['item_id'])
            ->update(['category' => 'display']);

        $this->actingAs($this->admin())
            ->getJson(route('operations.store-transfers.stock', ['source_store_id' => $fixture['source']->id]))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    public function test_store_transfer_rejects_qty_above_source_stock(): void
    {
        $fixture = $this->fixture(1);

        $this->actingAs($this->admin())
            ->post(route('operations.store-transfers.store'), [
                'source_store_id' => $fixture['source']->id,
                'destination_store_id' => $fixture['destination']->id,
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'qty' => 5,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, StoreOrder::query()->where('store_id', $fixture['destination']->id)->where('order_number', 'like', 'TRF-%')->count());
    }

    private function scanItems(string $outboundId, array $items, string $userId, int $qty): void
    {
        foreach (array_slice($items, 0, $qty) as $item) {
            DB::table('store_outbound_details')->insert([
                'id' => (string) Str::uuid(),
                'store_outbound_id' => $outboundId,
                'item_id' => $item['item_id'],
                'rfid_tag_id' => $item['rfid_tag_id'],
                'rfid_releaser_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function shippedItemIds(string $outboundId): array
    {
        return DB::table('store_outbound_details')
            ->where('store_outbound_id', $outboundId)
            ->pluck('item_id')
            ->all();
    }

    private function fixture(int $stockQty): array
    {
        $conditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'good'")
            ->value('id') ?? DB::table('item_conditions')->value('id');

        $this->assertNotNull($conditionId);

        $suffix = Str::lower(Str::random(6));
        $unitId = DB::table('units')->whereNull('deleted_at')->value('id');
        $categoryId = DB::table('product_categories')->whereNull('deleted_at')->value('id');

        if (! $unitId) {
            $unitId = (string) Str::uuid();
            DB::table('units')->insert([
                'id' => $unitId,
                'name' => 'Pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $categoryId) {
            $categoryId = (string) Str::uuid();
            DB::table('product_categories')->insert([
                'id' => $categoryId,
                'name' => 'Transfer Test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $productId = (string) Str::uuid();
        $productPriceId = (string) Str::uuid();

        DB::table('products')->insert([
            'id' => $productId,
            'name' => 'Transfer Product '.$suffix,
            'code' => 'TRF-PROD-'.$suffix,
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_prices')->insert([
            'id' => $productPriceId,
            'product_id' => $productId,
            'price_type' => 'store',
            'price' => 10000,
            'valid_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = (object) [
            'id' => $productId,
            'name' => 'Transfer Product '.$suffix,
            'code' => 'TRF-PROD-'.$suffix,
            'product_price_id' => $productPriceId,
            'price' => 10000,
        ];

        $source = Store::query()->create([
            'name' => 'Transfer Source '.$suffix,
            'address' => 'Jl. Source '.$suffix,
        ]);

        $destination = Store::query()->create([
            'name' => 'Transfer Destination '.$suffix,
            'address' => 'Jl. Destination '.$suffix,
        ]);

        $locationId = (string) Str::uuid();

        DB::table('locations')->insert([
            'id' => $locationId,
            'location_parent_id' => null,
            'name' => 'Transfer Destination '.$suffix,
            'type' => 'store',
            'code' => 'TRF-'.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_locations')->insert([
            'store_id' => $destination->id,
            'location_id' => $locationId,
        ]);

        $items = [];

        for ($index = 0; $index < $stockQty; $index++) {
            $itemId = (string) Str::uuid();
            $rfidTagId = (string) Str::uuid();

            DB::table('rfid_tags')->insert([
                'id' => $rfidTagId,
                'value' => 'TRF-'.$suffix.'-'.$index,
                'created_at' => now(),
            ]);

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $productId,
                'current_condition_id' => $conditionId,
                'rfid_tag_id' => $rfidTagId,
                'status' => 'store_stock',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('store_items')->insert([
                'store_id' => $source->id,
                'item_id' => $itemId,
                'category' => 'inventory',
                'created_at' => now(),
            ]);

            $items[] = [
                'item_id' => $itemId,
                'rfid_tag_id' => $rfidTagId,
            ];
        }

        return [
            'product' => $product,
            'source' => $source,
            'destination' => $destination,
            'location_id' => $locationId,
            'items' => $items,
            'condition_id' => $conditionId,
        ];
    }

    private function addWarehouseStock(string $productId, string $conditionId, int $qty = 1): void
    {
        $warehouseId = DB::table('warehouses')->whereNull('deleted_at')->value('id');

        if (! $warehouseId) {
            $warehouseId = (string) Str::uuid();
            DB::table('warehouses')->insert([
                'id' => $warehouseId,
                'name' => 'Transfer Test Warehouse',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($index = 0; $index < $qty; $index++) {
            $itemId = (string) Str::uuid();
            $rfidTagId = (string) Str::uuid();

            DB::table('rfid_tags')->insert([
                'id' => $rfidTagId,
                'value' => 'WH-'.Str::lower(Str::random(10)),
                'created_at' => now(),
            ]);

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $productId,
                'current_condition_id' => $conditionId,
                'rfid_tag_id' => $rfidTagId,
                'status' => 'warehouse_stock',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouse_items')->insert([
                'id' => (string) Str::uuid(),
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
