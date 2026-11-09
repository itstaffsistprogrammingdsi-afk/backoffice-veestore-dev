<?php

namespace Tests\Feature\Monitoring;

use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ShiftPurchaseReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_shift_purchase_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.shift-purchases.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/ShiftPurchases')
                ->has('rows')
                ->has('summary')
                ->has('productRows')
                ->has('stores')
                ->has('shifts')
                ->has('cashiers')
                ->has('filters')
                ->has('urls'));
    }

    public function test_shift_purchase_page_aggregates_items_per_shift(): void
    {
        $fixture = $this->fixture();
        $today = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('monitoring.shift-purchases.index', [
                'from' => $today,
                'to' => $today,
                'store_id' => $fixture['store']->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/ShiftPurchases')
                ->where('summary.total_transactions', 1)
                ->where('summary.total_items', 1)
                ->where('summary.gross_amount', number_format((float) $fixture['product']->price, 2, '.', ''))
                ->where('summary.net_amount', number_format((float) $fixture['product']->price, 2, '.', ''))
                ->where('rows.data.0.store_name', $fixture['store']->name)
                ->where('rows.data.0.shift_name', 'Tanpa Shift')
                ->where('productRows.0.product_id', $fixture['product']->id)
                ->where('productRows.0.qty', 1));
    }

    public function test_shift_purchase_detail_returns_products_and_transactions(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($this->admin())
            ->getJson(route('monitoring.shift-purchases.detail', [
                'date' => now()->toDateString(),
                'store_id' => $fixture['store']->id,
            ]))
            ->assertOk()
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('summary.total_items', 1)
            ->assertJsonPath('products.0.product_id', $fixture['product']->id)
            ->assertJsonPath('transactions.0.transaction_number', $fixture['transaction_number']);

        $this->actingAs($this->admin())
            ->get(route('monitoring.shift-purchases.export', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'store_id' => $fixture['store']->id,
            ]))
            ->assertOk();
    }

    private function fixture(): array
    {
        $product = DB::table('products as p')
            ->join('product_prices as pp', 'pp.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->select(['p.id', 'p.name', 'p.code', 'pp.id as product_price_id', 'pp.price'])
            ->first();

        $this->assertNotNull($product);

        $suffix = Str::lower(Str::random(6));

        $store = Store::query()->create([
            'name' => 'Shift Purchase Store '.$suffix,
            'address' => 'Jl. Shift '.$suffix,
        ]);

        $admin = $this->admin();
        $customerId = (string) Str::uuid();
        $visitId = (string) Str::uuid();
        $cartId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();
        $sessionId = (string) Str::uuid();
        $itemId = (string) Str::uuid();
        $transactionNumber = 'SHIFT-TRX-'.Str::upper($suffix);

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

        DB::table('items')->insert([
            'id' => $itemId,
            'product_id' => $product->id,
            'status' => 'sold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_cart_details')->insert([
            'id' => (string) Str::uuid(),
            'cart_id' => $cartId,
            'item_id' => $itemId,
            'product_price_id' => $product->product_price_id,
            'product_price_amount' => $product->price,
            'discount_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_sessions')->insert([
            'id' => $sessionId,
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'session_number' => 'SESSION-'.$suffix,
            'status' => 'closed',
            'opened_at' => now(),
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'id' => $paymentId,
            'total_amount' => $product->price,
            'change_amount' => 0,
            'paid_amount' => $product->price,
            'status' => 'paid',
            'transaction_number' => $transactionNumber,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_visit_payments')->insert([
            'id' => (string) Str::uuid(),
            'visit_id' => $visitId,
            'payment_id' => $paymentId,
            'cashier_user_id' => $admin->id,
            'cashier_session_id' => $sessionId,
        ]);

        return [
            'product' => $product,
            'store' => $store,
            'transaction_number' => $transactionNumber,
        ];
    }
}
