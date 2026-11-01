<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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

    public function test_voucher_report_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.voucher.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Voucher')
                ->has('vouchers')
                ->has('voucherList')
                ->has('filters')
                ->has('urls'));
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
                ->has('totalCustomers')
                ->has('genderRows')
                ->has('ageRows')
                ->has('urls'));
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
                ->has('warehouses')
                ->has('statuses')
                ->has('filters')
                ->has('urls'));
    }

    public function test_retur_cabang_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.retur-cabang.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Returns')
                ->has('returns')
                ->has('stores')
                ->has('warehouses')
                ->has('statuses')
                ->has('filters')
                ->has('urls'));
    }
}
