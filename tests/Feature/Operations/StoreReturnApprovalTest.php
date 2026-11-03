<?php

namespace Tests\Feature\Operations;

use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreReturnApprovalTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_return_approval_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-returns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreReturnApproval')
                ->has('rows')
                ->has('stats')
                ->has('stores')
                ->has('statusOptions')
                ->has('filters')
                ->has('urls'));
    }

    public function test_return_can_be_approved_fully_with_destination_warehouse(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        $returnId = $this->makeReturn($admin, $fixture['store'], $fixture['product'], 3);

        $this->actingAs($admin)
            ->patch(route('operations.store-returns.approve', ['return' => $returnId]), [
                'warehouse_id' => $fixture['warehouse']->id,
                'note' => 'Barang sesuai untuk diproses gudang.',
                'items' => [
                    ['product_id' => $fixture['product']->id, 'qty' => 3],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $return = DB::table('store_returns')->where('id', $returnId)->first();

        $this->assertSame('approved', $return->status);
        $this->assertSame($fixture['warehouse']->id, $return->warehouse_id);
        $this->assertSame($admin->id, $return->approved_by);
        $this->assertSame('Barang sesuai untuk diproses gudang.', $return->note_warehouse);
        $this->assertSame(3, DB::table('store_return_details')->where('store_return_id', $returnId)->where('status', 'approved')->count());
    }

    public function test_return_can_be_approved_partially_then_completed(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        $returnId = $this->makeReturn($admin, $fixture['store'], $fixture['product'], 3);

        $this->actingAs($admin)
            ->patch(route('operations.store-returns.approve', ['return' => $returnId]), [
                'warehouse_id' => $fixture['warehouse']->id,
                'items' => [
                    ['product_id' => $fixture['product']->id, 'qty' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('partial_approved', DB::table('store_returns')->where('id', $returnId)->value('status'));
        $this->assertSame(1, DB::table('store_return_details')->where('store_return_id', $returnId)->where('status', 'approved')->count());

        $this->actingAs($admin)
            ->patch(route('operations.store-returns.approve', ['return' => $returnId]), [
                'warehouse_id' => $fixture['warehouse']->id,
                'items' => [
                    ['product_id' => $fixture['product']->id, 'qty' => 2],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', DB::table('store_returns')->where('id', $returnId)->value('status'));
        $this->assertSame(3, DB::table('store_return_details')->where('store_return_id', $returnId)->where('status', 'approved')->count());
    }

    public function test_return_approval_cannot_exceed_remaining_quantity(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        $returnId = $this->makeReturn($admin, $fixture['store'], $fixture['product'], 2);

        $this->actingAs($admin)
            ->patch(route('operations.store-returns.approve', ['return' => $returnId]), [
                'warehouse_id' => $fixture['warehouse']->id,
                'items' => [
                    ['product_id' => $fixture['product']->id, 'qty' => 3],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame('requested', DB::table('store_returns')->where('id', $returnId)->value('status'));
        $this->assertSame(0, DB::table('store_return_details')->where('store_return_id', $returnId)->where('status', 'approved')->count());
    }

    public function test_requested_return_can_be_rejected_with_note(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        $returnId = $this->makeReturn($admin, $fixture['store'], $fixture['product'], 2);

        $this->actingAs($admin)
            ->patch(route('operations.store-returns.reject', ['return' => $returnId]), [
                'note' => 'Barang retur tidak sesuai ketentuan.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $return = DB::table('store_returns')->where('id', $returnId)->first();

        $this->assertSame('rejected', $return->status);
        $this->assertSame('Barang retur tidak sesuai ketentuan.', $return->note_warehouse);
        $this->assertNotNull($return->rejected_at);
        $this->assertSame(2, DB::table('store_return_details')->where('store_return_id', $returnId)->where('status', 'rejected')->count());
    }

    public function test_return_detail_exposes_remaining_quantity_and_conditions(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture();
        $returnId = $this->makeReturn($admin, $fixture['store'], $fixture['product'], 2, $fixture['bad_condition_id']);

        $this->actingAs($admin)
            ->getJson(route('operations.store-returns.show', ['return' => $returnId]))
            ->assertOk()
            ->assertJsonPath('header.status', 'requested')
            ->assertJsonPath('header.total_requested', 2)
            ->assertJsonPath('details.0.qty_remaining', 2)
            ->assertJsonPath('is_editable', true)
            ->assertJsonPath('can_reject', true);
    }

    private function fixture(): array
    {
        $product = DB::table('products as p')
            ->whereNull('p.deleted_at')
            ->select(['p.id', 'p.name', 'p.code'])
            ->first();

        $goodConditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'good'")
            ->value('id');

        $badConditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'bad'")
            ->value('id') ?? $goodConditionId;

        $this->assertNotNull($product);
        $this->assertNotNull($goodConditionId);

        $store = Store::query()->create([
            'name' => 'Test Return Store '.Str::random(6),
        ]);

        $warehouse = Warehouse::query()->create([
            'name' => 'Test Return Warehouse '.Str::random(6),
        ]);

        return [
            'product' => $product,
            'good_condition_id' => $goodConditionId,
            'bad_condition_id' => $badConditionId,
            'store' => $store,
            'warehouse' => $warehouse,
        ];
    }

    private function makeReturn(
        User $admin,
        Store $store,
        object $product,
        int $qty,
        ?string $conditionId = null
    ): string {
        $returnId = (string) Str::uuid();

        DB::table('store_returns')->insert([
            'id' => $returnId,
            'store_id' => $store->id,
            'status' => 'requested',
            'return_number' => 'TEST-RTR-'.Str::uuid(),
            'created_by' => $admin->id,
            'note_store' => 'Barang yang dikirim tidak sesuai dengan pesanan.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goodConditionId = DB::table('item_conditions')
            ->whereRaw("LOWER(COALESCE(status::text, name)) = 'good'")
            ->value('id');

        for ($index = 0; $index < $qty; $index++) {
            $itemId = (string) Str::uuid();

            DB::table('items')->insert([
                'id' => $itemId,
                'product_id' => $product->id,
                'current_condition_id' => $conditionId ?? $goodConditionId,
                'status' => 'store_stock',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('store_return_details')->insert([
                'id' => (string) Str::uuid(),
                'store_return_id' => $returnId,
                'item_id' => $itemId,
                'store_id' => $store->id,
                'status' => 'in_review',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $returnId;
    }
}
