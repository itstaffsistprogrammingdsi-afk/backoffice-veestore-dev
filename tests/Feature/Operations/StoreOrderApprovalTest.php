<?php

namespace Tests\Feature\Operations;

use App\Models\Store;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreOrderApprovalTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_order_approval_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreOrderApproval')
                ->has('orders')
                ->has('stats')
                ->has('stores')
                ->has('statusOptions')
                ->has('filters')
                ->has('urls'));
    }

    public function test_all_status_filter_removes_the_default_requested_filter(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-orders.index', ['status' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreOrderApproval')
                ->where('filters.status', ''));
    }

    public function test_order_can_be_approved_and_split_across_warehouses(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        [$order, $detail] = $this->makeOrder($admin, $fixture['store'], $fixture['product'], 20);

        $this->stockWarehouse($fixture['warehouse_a']->id, $fixture['product']->id, $fixture['condition_id'], 10);
        $this->stockWarehouse($fixture['warehouse_b']->id, $fixture['product']->id, $fixture['condition_id'], 10);

        $this->actingAs($admin)
            ->patch(route('operations.store-orders.approve', $order), [
                'allocations' => [
                    ['detail_id' => $detail->id, 'warehouse_id' => $fixture['warehouse_a']->id, 'qty' => 10],
                    ['detail_id' => $detail->id, 'warehouse_id' => $fixture['warehouse_b']->id, 'qty' => 10],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame('approved', $order->status);
        $this->assertSame($fixture['warehouse_a']->id, $order->warehouse_id);
        $this->assertSame(10, (int) $order->details()->first()->approved_qty);

        $orders = StoreOrder::query()
            ->where('store_id', $fixture['store']->id)
            ->with('details')
            ->get();

        $this->assertCount(2, $orders);
        $this->assertSame(20, (int) $orders->flatMap(fn (StoreOrder $storeOrder) => $storeOrder->details)->sum('approved_qty'));

        $reservations = DB::table('stock_reservations')
            ->where('reference_type', 'store_order')
            ->whereIn('reference_id', $orders->pluck('id')->all())
            ->get();

        $this->assertCount(2, $reservations);
        $this->assertSame(20, (int) $reservations->sum('quantity'));
        $this->assertEqualsCanonicalizing(
            [$fixture['warehouse_a']->id, $fixture['warehouse_b']->id],
            $reservations->pluck('handler_id')->all()
        );
    }

    public function test_approval_is_rejected_when_warehouse_stock_is_not_enough(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        [$order, $detail] = $this->makeOrder($admin, $fixture['store'], $fixture['product'], 10);

        $this->stockWarehouse($fixture['warehouse_a']->id, $fixture['product']->id, $fixture['condition_id'], 5);

        $this->actingAs($admin)
            ->patch(route('operations.store-orders.approve', $order), [
                'allocations' => [
                    ['detail_id' => $detail->id, 'warehouse_id' => $fixture['warehouse_a']->id, 'qty' => 10],
                ],
            ])
            ->assertSessionHasErrors('allocations');

        $order->refresh();

        $this->assertSame('requested', $order->status);
        $this->assertNull($order->warehouse_id);
        $this->assertSame(0, DB::table('stock_reservations')->where('reference_id', $order->id)->count());
        $this->assertSame(1, StoreOrder::query()->where('store_id', $fixture['store']->id)->count());
    }

    public function test_requested_order_can_be_rejected(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        [$order] = $this->makeOrder($admin, $fixture['store'], $fixture['product'], 8);

        $this->actingAs($admin)
            ->patch(route('operations.store-orders.reject', $order))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $order->refresh()->status);
    }

    public function test_order_detail_exposes_available_stock_per_warehouse(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        [$order] = $this->makeOrder($admin, $fixture['store'], $fixture['product'], 20);

        $this->stockWarehouse($fixture['warehouse_a']->id, $fixture['product']->id, $fixture['condition_id'], 10);
        $this->stockWarehouse($fixture['warehouse_b']->id, $fixture['product']->id, $fixture['condition_id'], 10);

        $this->actingAs($admin)
            ->getJson(route('operations.store-orders.show', $order))
            ->assertOk()
            ->assertJsonPath(
                "stock.{$fixture['warehouse_a']->id}.{$fixture['product']->id}.qty_available",
                10
            )
            ->assertJsonPath(
                "stock.{$fixture['warehouse_b']->id}.{$fixture['product']->id}.qty_available",
                10
            )
            ->assertJsonPath('is_editable', true);
    }

    private function fixture(): array
    {
        $product = DB::table('products as p')
            ->join('product_prices as pp', 'pp.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'p.name',
                'p.code',
                'pp.id as product_price_id',
                'pp.price',
            ])
            ->first();

        $conditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'good'")
            ->value('id') ?? DB::table('item_conditions')->value('id');

        $this->assertNotNull($product);
        $this->assertNotNull($conditionId);

        $store = Store::query()->create([
            'name' => 'Test Store Approval '.Str::random(6),
        ]);

        $warehouseA = Warehouse::query()->create([
            'name' => 'Test Warehouse A '.Str::random(6),
        ]);

        $warehouseB = Warehouse::query()->create([
            'name' => 'Test Warehouse B '.Str::random(6),
        ]);

        return [
            'product' => $product,
            'condition_id' => $conditionId,
            'store' => $store,
            'warehouse_a' => $warehouseA,
            'warehouse_b' => $warehouseB,
        ];
    }

    private function makeOrder(User $admin, Store $store, object $product, int $qty): array
    {
        $order = StoreOrder::query()->create([
            'order_number' => 'TEST-'.Str::uuid(),
            'store_id' => $store->id,
            'warehouse_id' => null,
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

    private function stockWarehouse(string $warehouseId, string $productId, string $conditionId, int $qty): void
    {
        for ($index = 0; $index < $qty; $index++) {
            $itemId = (string) Str::uuid();

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $productId,
                'current_condition_id' => $conditionId,
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
