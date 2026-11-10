<?php

namespace Tests\Feature\Monitoring;

use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SampleGimmickOmsetExclusionTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_approved_gimmick_item_is_removed_from_omset_penjualan(): void
    {
        $fixture = $this->fixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.index', [
                'channel' => 'store',
                'q' => $fixture['product']->code,
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.summary.total_rows', 1)
                ->where('data.rows.data.0.product_code', $fixture['product']->code));

        $requestId = $this->createPendingGimmickRequest($fixture);

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.requests.approve', $requestId))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'gimmick',
            DB::table('store_cart_details')->where('id', $fixture['cart_detail_id'])->value('void_status')
        );

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.index', [
                'channel' => 'store',
                'q' => $fixture['product']->code,
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.rows.data', fn ($rows) => count($rows) === 0));
    }

    private function fixture(): array
    {
        $unitId = DB::table('units')->value('id');
        $categoryId = DB::table('product_categories')->value('id');
        $this->assertNotNull($unitId);
        $this->assertNotNull($categoryId);

        $suffix = Str::lower(Str::random(6));
        $admin = $this->admin();
        $productId = (string) Str::uuid();
        $productPriceId = (string) Str::uuid();
        $store = Store::query()->create([
            'name' => 'Omset Gimmick Store '.$suffix,
            'address' => 'Jl. Omset '.$suffix,
        ]);
        $customerId = (string) Str::uuid();
        $visitId = (string) Str::uuid();
        $cartId = (string) Str::uuid();
        $cartDetailId = (string) Str::uuid();
        $itemId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();

        DB::table('products')->insert([
            'id' => $productId,
            'name' => 'Produk Gimmick '.$suffix,
            'code' => 'GIMMICK-'.Str::upper($suffix),
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_prices')->insert([
            'id' => $productPriceId,
            'product_id' => $productId,
            'price_type' => 'store',
            'price' => 100000,
            'valid_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => $customerId,
            'name' => 'Pelanggan '.$suffix,
            'wa_number' => '0812'.random_int(100000, 999999),
            'is_guest' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_customer_visits')->insert([
            'id' => $visitId,
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'visit_code' => 'VISIT-GIMMICK-'.$suffix,
            'status' => 'completed',
            'payment_status' => 'paid',
            'visit_type' => 'cashier',
            'check_in_at' => now(),
            'created_at' => now(),
        ]);

        DB::table('store_carts')->insert([
            'id' => $cartId,
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'visit_id' => $visitId,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('items')->insert([
            'id' => $itemId,
            'product_id' => $productId,
            'status' => 'sold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_cart_details')->insert([
            'id' => $cartDetailId,
            'cart_id' => $cartId,
            'item_id' => $itemId,
            'product_price_id' => $productPriceId,
            'product_price_amount' => 100000,
            'discount_amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'id' => $paymentId,
            'total_amount' => 0,
            'change_amount' => 0,
            'paid_amount' => 0,
            'status' => 'paid',
            'transaction_number' => 'GIMMICK-TRX-'.Str::upper($suffix),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_visit_payments')->insert([
            'id' => (string) Str::uuid(),
            'visit_id' => $visitId,
            'payment_id' => $paymentId,
            'cashier_user_id' => $admin->id,
        ]);

        return [
            'product' => (object) [
                'id' => $productId,
                'code' => 'GIMMICK-'.Str::upper($suffix),
            ],
            'store' => $store,
            'cart_detail_id' => $cartDetailId,
            'item_id' => $itemId,
            'product_id' => $productId,
        ];
    }

    private function createPendingGimmickRequest(array $fixture): string
    {
        $requestId = (string) Str::uuid();

        DB::table('app_settings')->insert([
            'key' => 'store_sample_gimmick_request:'.$requestId,
            'value' => json_encode([
                'id' => $requestId,
                'request_number' => 'SG-OMSET-'.Str::upper(Str::random(5)),
                'store_id' => $fixture['store']->id,
                'store_name' => $fixture['store']->name,
                'type' => 'gimmick',
                'status' => 'pending',
                'submitted_at' => now()->toIso8601String(),
                'created_by_name' => 'Store Tester',
                'items' => [[
                    'cart_detail_id' => $fixture['cart_detail_id'],
                    'item_id' => $fixture['item_id'],
                    'product_id' => $fixture['product_id'],
                    'product_name' => 'Produk Gimmick',
                    'product_code' => $fixture['product']->code,
                    'price' => 100000,
                    'discount' => 100000,
                    'net_price' => 0,
                ]],
                'item_count' => 1,
                'history' => [],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $requestId;
    }
}
