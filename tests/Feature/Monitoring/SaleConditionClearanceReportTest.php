<?php

namespace Tests\Feature\Monitoring;

use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SaleConditionClearanceReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_omset_report_shows_sale_condition_and_clearance_percentage(): void
    {
        $fixture = $this->saleFixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.index', [
                'channel' => 'store',
                'q' => $fixture['sold_product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.rows.data.0.condition_names', 'Good After Repair')
                ->where('data.rows.data.0.qty_good_after_repair', 1)
                ->where('data.rows.data.0.qty_clearance', 1)
                ->where('data.summary.total_qty_good_after_repair', 1)
                ->where('data.summary.total_qty_clearance', 1));

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.detail', [
                'product' => $fixture['sold_product_id'],
                'channel' => 'store',
                'q' => $fixture['sold_product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.rows.data.0.condition_status', 'good_after_repair')
                ->where('data.rows.data.0.condition_name', 'Good After Repair')
                ->where('data.rows.data.0.clearance_percentage', fn ($value) => (float) $value === 30.0));

        foreach (['daily', 'monthly'] as $groupBy) {
            $this->actingAs($this->admin())
                ->get(route('monitoring.omset-kotor.index', [
                    'channel' => 'store',
                    'q' => $fixture['sold_product_code'],
                    'date_from' => $today,
                    'date_to' => $today,
                    'group_by' => $groupBy,
                ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('data.rows.data.0.qty_good_after_repair', 1)
                    ->where('data.rows.data.0.qty_clearance', 1));
        }
    }

    public function test_omset_cabang_shows_condition_and_clearance_aggregates(): void
    {
        $fixture = $this->saleFixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-cabang.index', [
                'store_id' => $fixture['store_id'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.rows.data.0.total_qty_good_after_repair', 1)
                ->where('data.rows.data.0.total_qty_clearance', 1)
                ->where('data.rows.data.0.total_clearance_discount', fn ($value) => (float) $value === 30000.0)
                ->where('data.rows.data.0.clearance_percentage', fn ($value) => (float) $value === 30.0));
    }

    public function test_sample_gimmick_report_separates_gimmick_and_sample_from_omset(): void
    {
        $fixture = $this->saleFixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.index', [
                'channel' => 'store',
                'q' => $fixture['gimmick_product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.rows.data', fn ($rows) => count($rows) === 0));

        $this->actingAs($this->admin())
            ->get(route('monitoring.sample-gimmick-report.index', [
                'q' => $fixture['gimmick_product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.gimmickReceiptRows')
                ->has('data.sampleRows')
                ->has('data.gimmickStockRows')
                ->has('data.requests')
                ->where('data.gimmickReceiptRows.data.0.product_code', $fixture['gimmick_product_code'])
                ->where('data.summary.total_gimmick_receipts', 1)
                ->where('data.summary.total_gimmick_value', fn ($value) => (float) $value === 50000.0));

        $this->actingAs($this->admin())
            ->get(route('monitoring.sample-gimmick-report.index', [
                'q' => $fixture['sample_product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.sampleRows.data.0.product_code', $fixture['sample_product_code'])
                ->where('data.summary.total_sample_items', 1));

        $this->actingAs($this->admin())
            ->get(route('monitoring.sample-gimmick-report.export', [
                'date_from' => $today,
                'date_to' => $today,
            ]))
            ->assertOk();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-cabang.export', [
                'store_id' => $fixture['store_id'],
                'date_from' => $today,
                'date_to' => $today,
            ]))
            ->assertOk();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.export', [
                'channel' => 'store',
                'q' => $fixture['sold_product_code'],
                'date_from' => $today,
                'date_to' => $today,
            ]))
            ->assertOk();
    }

    private function saleFixture(): array
    {
        $suffix = Str::lower(Str::random(6));
        $admin = $this->admin();
        $unitId = DB::table('units')->value('id');
        $categoryId = DB::table('product_categories')->value('id');
        $garConditionId = DB::table('item_conditions')->where('status', 'good_after_repair')->value('id');

        $this->assertNotNull($unitId);
        $this->assertNotNull($categoryId);
        $this->assertNotNull($garConditionId);

        $store = Store::query()->create([
            'name' => 'Kondisi Store '.$suffix,
            'address' => 'Jl. Kondisi '.$suffix,
        ]);

        $customerId = (string) Str::uuid();
        $visitId = (string) Str::uuid();
        $cartId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();
        $soldProductCode = 'GAR-'.Str::upper($suffix);
        $gimmickProductCode = 'GIFT-'.Str::upper($suffix);
        $sampleProductCode = 'SAMPLE-'.Str::upper($suffix);

        DB::table('customers')->insert([
            'id' => $customerId,
            'name' => 'Customer '.$suffix,
            'wa_number' => '0812'.random_int(100000, 999999),
            'is_guest' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_customer_visits')->insert([
            'id' => $visitId,
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'visit_code' => 'VISIT-'.$suffix,
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

        $soldProductId = $this->createProduct($soldProductCode, $unitId, $categoryId);
        $gimmickProductId = $this->createProduct($gimmickProductCode, $unitId, $categoryId);
        $sampleProductId = $this->createProduct($sampleProductCode, $unitId, $categoryId);

        $soldPriceId = $this->createPrice($soldProductId, 100000);
        $gimmickPriceId = $this->createPrice($gimmickProductId, 50000);

        $soldItemId = (string) Str::uuid();

        DB::table('items')->insert([
            'id' => $soldItemId,
            'product_id' => $soldProductId,
            'current_condition_id' => $garConditionId,
            'status' => 'sold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_cart_details')->insert([
            'id' => (string) Str::uuid(),
            'cart_id' => $cartId,
            'item_id' => $soldItemId,
            'product_price_id' => $soldPriceId,
            'product_price_amount' => 100000,
            'discount_amount' => 30000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gimmickItemId = (string) Str::uuid();

        DB::table('items')->insert([
            'id' => $gimmickItemId,
            'product_id' => $gimmickProductId,
            'status' => 'sold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_cart_details')->insert([
            'id' => (string) Str::uuid(),
            'cart_id' => $cartId,
            'item_id' => $gimmickItemId,
            'product_price_id' => $gimmickPriceId,
            'product_price_amount' => 50000,
            'discount_amount' => 50000,
            'void_status' => 'gimmick',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'id' => $paymentId,
            'total_amount' => 70000,
            'change_amount' => 0,
            'paid_amount' => 70000,
            'status' => 'paid',
            'transaction_number' => 'TRX-COND-'.Str::upper($suffix),
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

        $storeItemId = $this->createSampleStoreItem($store->id, $sampleProductId, $unitId, $categoryId);
        $markerKey = 'store_sample_gimmick:'.$storeItemId;

        DB::table('app_settings')->updateOrInsert(
            ['key' => $markerKey],
            [
                'value' => json_encode([
                    'type' => 'sample',
                    'marked_at' => now()->toIso8601String(),
                    'marked_by' => 'Tester',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return [
            'store_id' => $store->id,
            'sold_product_id' => $soldProductId,
            'sold_product_code' => $soldProductCode,
            'gimmick_product_code' => $gimmickProductCode,
            'sample_product_code' => $sampleProductCode,
        ];
    }

    private function createProduct(string $code, string $unitId, string $categoryId): string
    {
        $productId = (string) Str::uuid();

        DB::table('products')->insert([
            'id' => $productId,
            'name' => 'Produk '.$code,
            'code' => $code,
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $productId;
    }

    private function createPrice(string $productId, float $price): string
    {
        $priceId = (string) Str::uuid();

        DB::table('product_prices')->insert([
            'id' => $priceId,
            'product_id' => $productId,
            'price_type' => 'store',
            'price' => $price,
            'valid_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $priceId;
    }

    private function createSampleStoreItem(string $storeId, string $productId, string $unitId, string $categoryId): string
    {
        $itemId = (string) Str::uuid();
        $storeItemId = (string) Str::uuid();

        DB::table('items')->insert([
            'id' => $itemId,
            'product_id' => $productId,
            'status' => 'store_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_items')->insert([
            'id' => $storeItemId,
            'store_id' => $storeId,
            'item_id' => $itemId,
            'category' => 'inventory',
            'created_at' => now(),
        ]);

        $this->createPrice($productId, 25000);

        return $storeItemId;
    }
}
