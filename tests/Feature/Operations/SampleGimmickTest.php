<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SampleGimmickTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_sample_gimmick_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.sample-gimmick.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/SampleGimmick')
                ->has('rows')
                ->has('summary')
                ->has('stores')
                ->has('categories')
                ->has('filters')
                ->has('urls'));
    }

    public function test_store_item_can_be_marked_as_sample_and_restored(): void
    {
        $storeItemId = DB::table('store_items')->value('id');

        $this->assertNotNull($storeItemId);

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'sample',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $marker = json_decode((string) DB::table('app_settings')->where('key', 'store_sample_gimmick:'.$storeItemId)->value('value'), true);

        $this->assertSame('sample', $marker['type'] ?? null);
        $this->assertSame('inventory', DB::table('store_items')->where('id', $storeItemId)->value('category'));

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'inventory',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('inventory', DB::table('store_items')->where('id', $storeItemId)->value('category'));
        $this->assertNull(DB::table('app_settings')->where('key', 'store_sample_gimmick:'.$storeItemId)->value('value'));
    }

    public function test_invalid_category_is_rejected(): void
    {
        $storeItemId = DB::table('store_items')->value('id');

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'invalid',
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_pending_request_is_visible_in_backoffice_index(): void
    {
        $fixture = $this->pendingSampleRequest();

        $this->actingAs($this->admin())
            ->get(route('operations.sample-gimmick.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/SampleGimmick')
                ->has('requests')
                ->has('requestSummary')
                ->has('requestStatusOptions')
                ->has('requestTypeOptions')
                ->where('requests.data', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['id'] === $fixture['id']
                )));
    }

    public function test_sample_request_can_be_approved_and_marks_store_item(): void
    {
        $fixture = $this->pendingSampleRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']), [
                'note' => 'Sesuai kebutuhan tester.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = $this->requestPayload($fixture['id']);
        $this->assertSame('approved', $request['status'] ?? null);
        $this->assertSame($this->admin()->name, $request['reviewed_by_name'] ?? null);

        $marker = DB::table('app_settings')
            ->where('key', 'store_sample_gimmick:'.$fixture['store_item_id'])
            ->first();

        $this->assertNotNull($marker);
        $markerPayload = json_decode((string) $marker->value, true);
        $this->assertSame('sample', $markerPayload['type'] ?? null);
        $this->assertSame($fixture['id'], $markerPayload['request_id'] ?? null);
    }

    public function test_gimmick_request_approval_excludes_cart_detail_from_omset(): void
    {
        $fixture = $this->pendingGimmickRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'gimmick',
            DB::table('store_cart_details')->where('id', $fixture['cart_detail_id'])->value('void_status')
        );

        $request = $this->requestPayload($fixture['id']);
        $this->assertSame('approved', $request['status'] ?? null);
    }

    public function test_gimmick_scan_request_marks_store_item_as_gimmick(): void
    {
        $fixture = $this->pendingGimmickScanRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $marker = DB::table('app_settings')
            ->where('key', 'store_sample_gimmick:'.$fixture['store_item_id'])
            ->first();

        $this->assertNotNull($marker);
        $markerPayload = json_decode((string) $marker->value, true);
        $this->assertSame('gimmick', $markerPayload['type'] ?? null);
        $this->assertSame($fixture['id'], $markerPayload['request_id'] ?? null);

        $request = $this->requestPayload($fixture['id']);
        $this->assertSame('approved', $request['status'] ?? null);
    }

    public function test_gimmick_request_can_combine_receipt_and_scan_items(): void
    {
        $fixture = $this->pendingMixedGimmickRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'gimmick',
            DB::table('store_cart_details')->where('id', $fixture['cart_detail_id'])->value('void_status')
        );

        $marker = DB::table('app_settings')
            ->where('key', 'store_sample_gimmick:'.$fixture['store_item_id'])
            ->first();

        $this->assertNotNull($marker);
        $markerPayload = json_decode((string) $marker->value, true);
        $this->assertSame('gimmick', $markerPayload['type'] ?? null);
    }

    public function test_request_can_be_rejected_with_reason(): void
    {
        $fixture = $this->pendingSampleRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.reject', $fixture['id']), [
                'reason' => 'Item masih dipakai untuk display.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = $this->requestPayload($fixture['id']);
        $this->assertSame('rejected', $request['status'] ?? null);
        $this->assertSame('Item masih dipakai untuk display.', $request['rejection_reason'] ?? null);
    }

    public function test_rejected_request_requires_reason(): void
    {
        $fixture = $this->pendingSampleRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.reject', $fixture['id']), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_approved_request_cannot_be_approved_again(): void
    {
        $fixture = $this->pendingSampleRequest();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $fixture['id']))
            ->assertSessionHasErrors('request');
    }

    private function pendingSampleRequest(): array
    {
        $storeItem = DB::table('store_items as store_item')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->select([
                'store_item.id',
                'store_item.store_id',
                'item.id as item_id',
                'product.id as product_id',
                'product.name as product_name',
                'product.code as product_code',
            ])
            ->first();

        $this->assertNotNull($storeItem);

        return $this->storeRequest('sample', (string) $storeItem->store_id, [[
            'store_item_id' => (string) $storeItem->id,
            'item_id' => (string) $storeItem->item_id,
            'product_id' => (string) $storeItem->product_id,
            'product_name' => (string) $storeItem->product_name,
            'product_code' => (string) $storeItem->product_code,
        ]]);
    }

    private function pendingMixedGimmickRequest(): array
    {
        $storeId = DB::table('store_cart_details as cart_detail')
            ->join('store_carts as cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->join('store_items as store_item', 'store_item.store_id', '=', 'cart.store_id')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->where(function ($query): void {
                $query->whereNull('cart_detail.void_status')
                    ->orWhereNotIn('cart_detail.void_status', ['voided', 'gimmick']);
            })
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->value('cart.store_id');

        $this->assertNotNull($storeId, 'Fixture store dengan item stok dan transaksi tidak tersedia.');

        $cartDetail = DB::table('store_cart_details as cart_detail')
            ->join('store_carts as cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->join('items as item', 'item.id', '=', 'cart_detail.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->where('cart.store_id', $storeId)
            ->where(function ($query): void {
                $query->whereNull('cart_detail.void_status')
                    ->orWhereNotIn('cart_detail.void_status', ['voided', 'gimmick']);
            })
            ->select([
                'cart_detail.id',
                'cart.store_id',
                'cart_detail.item_id',
                'product.id as product_id',
                'product.name as product_name',
                'product.code as product_code',
            ])
            ->first();

        $storeItem = DB::table('store_items as store_item')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->where('store_item.store_id', $storeId)
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->select([
                'store_item.id',
                'item.id as item_id',
                'product.id as product_id',
                'product.name as product_name',
                'product.code as product_code',
            ])
            ->first();

        $this->assertNotNull($cartDetail);
        $this->assertNotNull($storeItem);

        $fixture = $this->storeRequest('gimmick', (string) $storeId, [
            [
                'source' => 'receipt',
                'cart_detail_id' => (string) $cartDetail->id,
                'item_id' => (string) $cartDetail->item_id,
                'product_id' => (string) $cartDetail->product_id,
                'product_name' => (string) $cartDetail->product_name,
                'product_code' => (string) $cartDetail->product_code,
                'price' => 10000,
                'discount' => 10000,
                'net_price' => 0,
            ],
            [
                'source' => 'scan',
                'store_item_id' => (string) $storeItem->id,
                'item_id' => (string) $storeItem->item_id,
                'product_id' => (string) $storeItem->product_id,
                'product_name' => (string) $storeItem->product_name,
                'product_code' => (string) $storeItem->product_code,
            ],
        ]);

        $fixture['cart_detail_id'] = (string) $cartDetail->id;
        $fixture['store_item_id'] = (string) $storeItem->id;

        return $fixture;
    }

    private function pendingGimmickScanRequest(): array
    {
        $storeItem = DB::table('store_items as store_item')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->select([
                'store_item.id',
                'store_item.store_id',
                'item.id as item_id',
                'product.id as product_id',
                'product.name as product_name',
                'product.code as product_code',
            ])
            ->first();

        $this->assertNotNull($storeItem);

        return $this->storeRequest('gimmick', (string) $storeItem->store_id, [[
            'source' => 'scan',
            'store_item_id' => (string) $storeItem->id,
            'item_id' => (string) $storeItem->item_id,
            'product_id' => (string) $storeItem->product_id,
            'product_name' => (string) $storeItem->product_name,
            'product_code' => (string) $storeItem->product_code,
        ]]);
    }

    private function pendingGimmickRequest(): array
    {
        $cartDetail = DB::table('store_cart_details as cart_detail')
            ->join('store_carts as cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->join('items as item', 'item.id', '=', 'cart_detail.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->where(function ($query): void {
                $query->whereNull('cart_detail.void_status')
                    ->orWhereNotIn('cart_detail.void_status', ['voided', 'gimmick']);
            })
            ->select([
                'cart_detail.id',
                'cart.store_id',
                'cart_detail.item_id',
                'product.id as product_id',
                'product.name as product_name',
                'product.code as product_code',
            ])
            ->first();

        $this->assertNotNull($cartDetail);

        $fixture = $this->storeRequest('gimmick', (string) $cartDetail->store_id, [[
            'cart_detail_id' => (string) $cartDetail->id,
            'item_id' => (string) $cartDetail->item_id,
            'product_id' => (string) $cartDetail->product_id,
            'product_name' => (string) $cartDetail->product_name,
            'product_code' => (string) $cartDetail->product_code,
            'price' => 10000,
            'discount' => 10000,
            'net_price' => 0,
        ]]);

        $fixture['cart_detail_id'] = (string) $cartDetail->id;

        return $fixture;
    }

    private function storeRequest(string $type, string $storeId, array $items): array
    {
        $id = (string) Str::uuid();

        DB::table('app_settings')->insert([
            'key' => 'store_sample_gimmick_request:'.$id,
            'value' => json_encode([
                'id' => $id,
                'request_number' => 'SG-TEST-'.Str::upper(Str::random(5)),
                'store_id' => $storeId,
                'store_name' => 'Test Store',
                'type' => $type,
                'status' => 'pending',
                'submitted_at' => now()->toIso8601String(),
                'created_by_name' => 'Store Tester',
                'items' => $items,
                'item_count' => count($items),
                'history' => [[
                    'action' => 'submitted',
                    'at' => now()->toIso8601String(),
                    'by' => null,
                    'by_name' => 'Store Tester',
                    'note' => null,
                ]],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'store_item_id' => $items[0]['store_item_id'] ?? null];
    }

    private function requestPayload(string $requestId): array
    {
        $row = DB::table('app_settings')
            ->where('key', 'store_sample_gimmick_request:'.$requestId)
            ->first();

        return $row ? json_decode((string) $row->value, true) : [];
    }
}
