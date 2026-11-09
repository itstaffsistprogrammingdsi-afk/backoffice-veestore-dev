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

    private function shippedItemIds(string $outboundId): array
    {
        return DB::table('store_outbound_details')
            ->where('store_outbound_id', $outboundId)
            ->pluck('item_id')
            ->all();
    }

    private function fixture(int $stockQty): array
    {
        $product = DB::table('products as p')
            ->join('product_prices as pp', 'pp.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->select(['p.id', 'p.name', 'p.code', 'pp.id as product_price_id', 'pp.price'])
            ->first();

        $conditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'good'")
            ->value('id') ?? DB::table('item_conditions')->value('id');

        $this->assertNotNull($product);
        $this->assertNotNull($conditionId);

        $suffix = Str::lower(Str::random(6));

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
                'product_id' => $product->id,
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
        }

        return [
            'product' => $product,
            'source' => $source,
            'destination' => $destination,
            'location_id' => $locationId,
        ];
    }
}
