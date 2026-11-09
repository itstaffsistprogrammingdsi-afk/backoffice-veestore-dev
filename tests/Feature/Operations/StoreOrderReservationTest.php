<?php

namespace Tests\Feature\Operations;

use App\Models\Store;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StoreOrderReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreOrderReservationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_requested_order_reserves_stock_once_and_blocks_other_orders(): void
    {
        $fixture = $this->fixture(10);
        [$firstOrder] = $this->makeOrder($this->admin(), $fixture['store'], $fixture['product'], 5, $fixture['warehouse']);

        $service = app(StoreOrderReservationService::class);
        $reserved = $service->reserve($firstOrder->fresh(['details']));

        $this->assertSame(5, $reserved);
        $this->assertSame(5, $this->activeReservedQty($firstOrder->id));

        $this->assertSame(0, $service->reserve($firstOrder->fresh(['details'])));
        $this->assertSame(5, $this->activeReservedQty($firstOrder->id));

        [$secondOrder] = $this->makeOrder($this->admin(), $fixture['store'], $fixture['product'], 5, $fixture['warehouse']);

        $this->assertSame(5, $service->reserve($secondOrder->fresh(['details'])));
        $this->assertSame(5, $this->activeReservedQty($secondOrder->id));

        [$thirdOrder] = $this->makeOrder($this->admin(), $fixture['store'], $fixture['product'], 1, $fixture['warehouse']);

        $this->assertSame(0, $service->reserve($thirdOrder->fresh(['details'])));
    }

    public function test_approval_replaces_request_reservation_without_double_counting(): void
    {
        $fixture = $this->fixture(10);
        [$order, $detail] = $this->makeOrder($this->admin(), $fixture['store'], $fixture['product'], 5, $fixture['warehouse']);

        app(StoreOrderReservationService::class)->reserve($order->fresh(['details']));

        $this->assertSame(5, $this->activeReservedQty($order->id));

        $this->actingAs($this->admin())
            ->patch(route('operations.store-orders.approve', $order), [
                'allocations' => [
                    [
                        'detail_id' => $detail->id,
                        'warehouse_id' => $fixture['warehouse']->id,
                        'qty' => 5,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame('approved', $order->status);
        $this->assertSame(5, $this->activeReservedQty($order->id));
    }

    private function activeReservedQty(string $orderId): int
    {
        return (int) DB::table('stock_reservations')
            ->where('reference_type', 'store_order')
            ->where('reference_id', $orderId)
            ->where('status', 'active')
            ->sum('quantity');
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

        $store = Store::query()->create([
            'name' => 'Store Reserve '.$suffix,
            'address' => 'Jl. Reserve '.$suffix,
        ]);

        $warehouse = Warehouse::query()->create([
            'name' => 'Warehouse Reserve '.$suffix,
        ]);

        for ($index = 0; $index < $stockQty; $index++) {
            $itemId = (string) Str::uuid();

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $product->id,
                'current_condition_id' => $conditionId,
                'status' => 'warehouse_stock',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouse_items')->insert([
                'id' => (string) Str::uuid(),
                'warehouse_id' => $warehouse->id,
                'item_id' => $itemId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return compact('product', 'store', 'warehouse');
    }

    private function makeOrder(
        User $admin,
        Store $store,
        object $product,
        int $qty,
        Warehouse $warehouse
    ): array {
        $order = StoreOrder::query()->create([
            'order_number' => 'TEST-RESERVE-'.Str::uuid(),
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'requested',
            'requested_at' => now(),
            'created_by' => $admin->id,
            'date' => now()->toDateString(),
        ]);

        $detail = $order->details()->create([
            'product_id' => $product->id,
            'product_price_id' => $product->product_price_id,
            'product_price' => $product->price,
            'requested_qty' => $qty,
            'approved_qty' => 0,
            'received_qty' => 0,
        ]);

        return [$order, $detail];
    }
}
