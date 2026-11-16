<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ReportsExtraPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_supplier_products_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.supplier-products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/SupplierProducts')
                ->has('rows')
                ->has('summary')
                ->has('filters')
                ->has('urls'));
    }

    public function test_voucher_report_page_redirects_to_unified_promo_loyalty_report(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.voucher.index'))
            ->assertRedirect(route('monitoring.promo-loyalty.index', ['dataset' => 'voucherUsageRows']));
    }

    public function test_crm_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('reports.crm.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Crm')
                ->has('summary')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('reports.crm.customer-profiles'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmCustomers')
                ->has('customers')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('reports.crm.demographics'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmDemographics')
                ->has('filters')
                ->has('summary')
                ->has('genderRows')
                ->has('ageRows')
                ->has('tierRows')
                ->has('provinceRows')
                ->has('registrationTrend')
                ->has('birthdayCustomers')
                ->has('birthdayGreeting.template')
                ->has('rows')
                ->where('urls.index', route('reports.crm.demographics'))
                ->where('urls.export', route('reports.crm.demographics.export'))
                ->where('urls.birthdayGreeting', route('reports.crm.demographics.birthday-greeting')));
    }

    public function test_reports_crm_demographics_accepts_complete_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.crm.demographics', [
                'gender' => 'female',
                'age_group' => '25_34',
                'is_guest' => 'no',
                'sort' => 'spending',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmDemographics')
                ->where('filters.gender', 'female')
                ->where('filters.age_group', '25_34')
                ->where('filters.is_guest', 'no')
                ->where('filters.sort', 'spending'));
    }

    public function test_history_customer_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.history-customer.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/HistoryCustomer')
                ->has('rowsPos')
                ->has('rowsEcom')
                ->has('stores')
                ->has('urls'));
    }

    public function test_order_cabang_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.order-cabang.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/OrderCabang')
                ->has('orders')
                ->has('stores')
                ->has('statuses')
                ->has('filters')
                ->has('urls')
                ->where('filters.status', ''));
    }

    public function test_order_cabang_report_shows_all_statuses_by_default(): void
    {
        $store = DB::table('stores')->whereNull('deleted_at')->first();
        $warehouse = DB::table('warehouses')->whereNull('deleted_at')->first();
        $product = DB::table('products')->first();
        $price = $product
            ? DB::table('product_prices')->where('product_id', $product->id)->first()
            : null;

        if (! $store || ! $warehouse || ! $product || ! $price) {
            $this->markTestSkipped('Data master untuk order cabang tidak tersedia.');
        }

        $orderId = (string) Str::uuid();
        $orderNumber = 'POS-TEST-'.Str::upper(Str::random(6));

        DB::table('store_orders')->insert([
            'id' => $orderId,
            'order_number' => $orderNumber,
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'requested_at' => now(),
            'approved_at' => now(),
            'status' => 'received',
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_order_details')->insert([
            'id' => (string) Str::uuid(),
            'store_order_id' => $orderId,
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'product_price' => $price->price ?? 0,
            'requested_qty' => 3,
            'approved_qty' => 2,
            'received_qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('reports.order-cabang.index', ['q' => $orderNumber]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/OrderCabang')
                ->where('filters.status', '')
                ->where('orders.total', 1)
                ->where('orders.data.0.status', 'received')
                ->where('orders.data.0.status_label', 'Diterima')
                ->where('orders.data.0.requested_qty', 3)
                ->where('orders.data.0.approved_qty', 2)
                ->where('orders.data.0.received_qty', 2));
    }

    public function test_retur_cabang_redirects_to_operations_approval_queue(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.retur-cabang.index'))
            ->assertRedirect(route('operations.store-returns.index', ['status' => 'all']));
    }

    public function test_retur_cabang_does_not_register_duplicate_approval_routes(): void
    {
        $this->assertFalse(Route::has('reports.retur-cabang.approve'));
        $this->assertFalse(Route::has('reports.retur-cabang.reject'));
        $this->assertTrue(Route::has('operations.store-returns.approve'));
        $this->assertTrue(Route::has('operations.store-returns.reject'));
    }
}
