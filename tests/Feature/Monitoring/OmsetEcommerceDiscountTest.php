<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class OmsetEcommerceDiscountTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_ecommerce_line_discount_is_not_subtracted_twice(): void
    {
        $fixture = $this->fixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.omset-kotor.index', [
                'channel' => 'ecommerce',
                'q' => $fixture['product_code'],
                'date_from' => $today,
                'date_to' => $today,
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.finance.gross_sales', 100000)
                ->where('data.finance.product_discount', 10000)
                ->where('data.finance.voucher_discount', 0)
                ->where('data.finance.net_sales', 90000)
                ->where('data.finance.gross_profit', -10000));
    }

    private function fixture(): array
    {
        $unitId = DB::table('units')->value('id');
        $categoryId = DB::table('product_categories')->value('id');
        $this->assertNotNull($unitId);
        $this->assertNotNull($categoryId);

        $suffix = Str::lower(Str::random(8));
        $productCode = 'ECOM-DISC-'.Str::upper($suffix);
        $productId = (string) Str::uuid();
        $productPriceId = (string) Str::uuid();
        $customerId = (string) Str::uuid();
        $addressId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();
        $orderId = (string) Str::uuid();

        DB::table('products')->insert([
            'id' => $productId,
            'name' => 'Produk Diskon '.$suffix,
            'code' => $productCode,
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_prices')->insert([
            'id' => $productPriceId,
            'product_id' => $productId,
            'price_type' => 'ecommerce',
            'price' => 100000,
            'valid_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => $customerId,
            'name' => 'Pelanggan Diskon '.$suffix,
            'wa_number' => '0812'.random_int(100000, 999999),
            'is_guest' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ecommerce_order_shipping_addresses')->insert([
            'id' => $addressId,
            'customer_id' => $customerId,
            'name' => 'Alamat Diskon '.$suffix,
            'created_at' => now(),
        ]);

        DB::table('payments')->insert([
            'id' => $paymentId,
            'total_amount' => 90000,
            'change_amount' => 0,
            'paid_amount' => 0,
            'status' => 'paid',
            'transaction_number' => 'ECOM-DISC-TRX-'.Str::upper($suffix),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ecommerce_orders')->insert([
            'id' => $orderId,
            'order_number' => 'ECOM-DISC-'.Str::upper($suffix),
            'customer_id' => $customerId,
            'status' => 'paid',
            'created_at' => now(),
            'shipping_address_snapshot_id' => $addressId,
            'payment_id' => $paymentId,
            'sub_total' => 90000,
            'discount_total' => 10000,
            'grand_total' => 90000,
            'payment_method_code' => 'test',
            'payment_method_name' => 'Test',
            'payment_fee' => 0,
        ]);

        DB::table('ecommerce_order_details')->insert([
            'id' => (string) Str::uuid(),
            'ecommerce_order_id' => $orderId,
            'product_id' => $productId,
            'product_price_id' => $productPriceId,
            'product_price' => 100000,
            'quantity' => 1,
            'total_discount' => 10000,
            'product_discount' => 10000,
            'total_price' => 90000,
            'product_base_price_amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['product_code' => $productCode];
    }
}
