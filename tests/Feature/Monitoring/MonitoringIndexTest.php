<?php

namespace Tests\Feature\Monitoring;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class MonitoringIndexTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_monitoring_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('monitoring.command-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Index')
                ->has('monitoringGroups')
                ->has('storeTransferReconciliation.summary')
                ->has('storeTransferReconciliation.rows')
                ->where('storeTransferReportUrl', route('monitoring.warehouse-outbound.index', ['reconciliation_status' => 'not_recorded']))
                ->where('overviewUrl', route('reports.business-overview.index'))
                ->where('monitoringGroups', fn ($groups) => collect($groups)->pluck('name')->contains('Pembayaran & Pemasukan')
                    && collect($groups)->pluck('name')->contains('Omzet & Kasir')));

        $routes = [
            'monitoring.stok-wms',
            'monitoring.omset-kotor.index',
            'monitoring.store-inbound.index',
            'monitoring.warehouse-outbound.index',
            'monitoring.supplier-insight.index',
            'monitoring.cashier-shift-report.index',
            'monitoring.tracking-barang.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Monitoring/Report'));
        }
    }

    public function test_stock_warehouse_page_exposes_reservation_detail_url(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.stok-wms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('urls.reservations', route('monitoring.stok-wms.reservations'))
                ->has('data.rows'));
    }

    public function test_stok_wms_reservation_details_explain_the_reserving_party(): void
    {
        $warehouse = DB::table('warehouses')->whereNull('deleted_at')->first();
        $product = DB::table('products')->first();

        $this->assertNotNull($warehouse);
        $this->assertNotNull($product);

        $reservationId = (string) Str::uuid();

        DB::table('stock_reservations')->insert([
            'id' => $reservationId,
            'product_id' => $product->id,
            'reference_type' => 'store_order',
            'reference_id' => (string) Str::uuid(),
            'handler_type' => 'warehouse',
            'handler_id' => $warehouse->id,
            'quantity' => 3,
            'status' => 'active',
            'reserved_at' => now(),
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->getJson(route('monitoring.stok-wms.reservations', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ]))->assertOk();

        $row = collect($response->json('rows'))->firstWhere('id', $reservationId);

        $this->assertNotNull($row);
        $this->assertSame(3, $row['remaining_quantity']);
        $this->assertSame('store_order', $row['reference_type']);
        $this->assertArrayHasKey('reserved_by_name', $row);
        $this->assertArrayHasKey('reserved_by_role', $row);
        $this->assertArrayHasKey('reserved_for_name', $row);
        $this->assertFalse($row['is_expired']);
    }

    public function test_inbound_outbound_monitoring_links_back_to_operations(): void
    {
        $admin = $this->admin();

        $routes = [
            'monitoring.warehouse-inbound' => 'operations.inbound.index',
            'monitoring.warehouse-outbound.index' => 'operations.outbound.index',
            'monitoring.store-inbound.index' => 'operations.store-inbound.index',
            'monitoring.store-outbound.index' => 'operations.store-outbound.index',
        ];

        foreach ($routes as $monitoringRoute => $operationsRoute) {
            $this->actingAs($admin)
                ->get(route($monitoringRoute))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Monitoring/Report')
                    ->where('urls.operations', route($operationsRoute))
                    ->has('data.rows'));
        }
    }

    public function test_outbound_reports_use_document_status_instead_of_generic_resi_status(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('monitoring.warehouse-outbound.index', ['document_status' => 'missing_delivery_order']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.filters.document_status', 'missing_delivery_order')
                ->has('data.documentStatuses'));

        $this->actingAs($admin)
            ->get(route('monitoring.store-outbound.index', ['document_status' => 'missing_awb']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.filters.document_status', 'missing_awb')
                ->has('data.documentStatuses'));
    }

    public function test_warehouse_outbound_report_exposes_destination_store(): void
    {
        if (! DB::table('warehouse_outbounds')->exists()) {
            $this->markTestSkipped('Belum ada data warehouse outbound.');
        }

        $response = $this->actingAs($this->admin())
            ->get(route('monitoring.warehouse-outbound.index', ['reconciliation_status' => 'not_recorded']));

        $response->assertOk();

        $rows = $response->viewData('page')['props']['data']['rows']['data'] ?? [];

        if ($rows === []) {
            $this->markTestSkipped('Belum ada outbound ke toko yang belum tercatat.');
        }

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Monitoring/Report')
            ->has('data.rows.data.0.destination_store_name')
            ->has('data.rows.data.0.destination_store_code'));
    }

    public function test_monitoring_sales_pages_load_and_expose_clear_metrics(): void
    {
        $admin = $this->admin();
        $query = [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-31',
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ];

        $routes = [
            'monitoring.payment-income-realtime.index',
            'monitoring.ecommerce-payment.index',
            'monitoring.omset-cabang.index',
            'monitoring.omset-kotor.index',
            'monitoring.cashier-shift-report.index',
            'monitoring.void-cancel-customer.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route, $query))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Monitoring/Report'));
        }

        $this->actingAs($admin)
            ->get(route('monitoring.ecommerce-payment.index', $query))
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.dashboardSummary.total_paid_amount')
                ->has('data.dashboardSummary.total_outstanding'));

        $this->actingAs($admin)
            ->get(route('monitoring.omset-cabang.index', $query))
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.omsetTab', 'cabang')
                ->has('data.omsetUrls.kotor')
                ->has('data.rows.data.0.store_name')
                ->missing('data.rows.data.0.product_name'));

        $this->actingAs($admin)
            ->get(route('monitoring.omset-kotor.index', $query))
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.omsetTab', 'kotor')
                ->has('data.omsetUrls.cabang')
                ->has('data.summary.total_gross_sales')
                ->has('data.summary.total_final_paid'));

        $this->actingAs($admin)
            ->get(route('monitoring.cashier-shift-report.index', $query))
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.summary.total_cash_amount')
                ->has('data.summary.total_non_cash_amount')
                ->has('data.summary.total_non_cash_transactions'));
    }

    public function test_command_center_reports_expose_audit_filters(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('monitoring.payment-reconciliation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.filters')
                ->has('data.orderStatuses')
                ->has('data.paymentStatuses')
                ->has('data.reconciliationStatuses'));

        $this->actingAs($admin)
            ->get(route('monitoring.fulfillment-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.filters')
                ->has('data.stores')
                ->has('data.warehouses')
                ->has('data.orderStatuses')
                ->has('data.handledTypes')
                ->has('data.ecommerce.data.0.sla_status'));

        $this->actingAs($admin)
            ->get(route('monitoring.quality-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.filters')
                ->has('data.warehouses')
                ->has('data.stores')
                ->has('data.conditions')
                ->has('data.statuses')
                ->has('data.sources'));

        $this->actingAs($admin)
            ->get(route('monitoring.audit-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.filters')
                ->has('data.actions'));
    }

    public function test_order_handled_combines_ecommerce_store_orders_and_store_returns(): void
    {
        $admin = $this->admin();

        $tabs = [
            'monitoring.order-handled.index' => 'ecommerceHandled',
            'monitoring.order-handled.store-orders' => 'storeOrders',
            'monitoring.order-handled.store-returns' => 'storeReturns',
        ];

        foreach ($tabs as $route => $datasetKey) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Monitoring/Report')
                    ->where('title', 'Order Handled')
                    ->has('data.orderTabs')
                    ->has("data.{$datasetKey}.data")
                    ->has('data.summary'));
        }
    }

    public function test_command_center_audit_filters_are_applied(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('monitoring.payment-reconciliation.index', [
                'reconciliation_status' => 'Tanpa pembayaran',
                'exception_only' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.reconciliation_status', 'Tanpa pembayaran')
                ->where('data.filters.exception_only', true));

        $this->actingAs($admin)
            ->get(route('monitoring.fulfillment-control.index', [
                'handled_type' => 'unhandled',
                'status' => 'pending',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.handled_type', 'unhandled')
                ->where('data.filters.status', 'pending'));

        $this->actingAs($admin)
            ->get(route('monitoring.quality-control.index', [
                'source' => 'store',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.source', 'store'));

        $this->actingAs($admin)
            ->get(route('monitoring.audit-center.index', [
                'action' => 'create',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.action', 'create'));
    }

    public function test_payment_income_realtime_supports_period_filters(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');

        try {
            $admin = $this->admin();

            $this->actingAs($admin)
                ->get(route('monitoring.payment-income-realtime.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Monitoring/Report')
                    ->where('data.filters.period', 'today')
                    ->where('data.filters.start_date', '2026-11-05')
                    ->where('data.filters.end_date', '2026-11-05')
                    ->where('data.periodFilter.value', 'today')
                    ->has('data.periodFilter.options', 4)
                    ->has('data.periodFilter.years'));

            $this->actingAs($admin)
                ->get(route('monitoring.payment-income-realtime.index', ['period' => 'week']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('data.filters.period', 'week')
                    ->where('data.filters.start_date', '2026-11-02')
                    ->where('data.filters.end_date', '2026-11-08')
                    ->where('data.periodFilter.rangeLabel', '2 - 8 Nov 2026'));

            $this->actingAs($admin)
                ->get(route('monitoring.payment-income-realtime.index', ['period' => 'month']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('data.filters.period', 'month')
                    ->where('data.filters.start_date', '2026-11-01')
                    ->where('data.filters.end_date', '2026-11-30')
                    ->where('data.periodFilter.rangeLabel', '1 - 30 Nov 2026'));

            $this->actingAs($admin)
                ->get(route('monitoring.payment-income-realtime.index', ['period' => 'year', 'year' => 2025]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('data.filters.period', 'year')
                    ->where('data.filters.year', 2025)
                    ->where('data.filters.start_date', '2025-01-01')
                    ->where('data.filters.end_date', '2025-12-31')
                    ->where('data.periodFilter.year', '2025')
                    ->where('data.periodFilter.rangeLabel', '1 Jan - 31 Des 2025'));

            $this->actingAs($admin)
                ->get(route('monitoring.payment-income-realtime.index', [
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-31',
                ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('data.filters.period', 'custom')
                    ->where('data.filters.start_date', '2026-10-01')
                    ->where('data.filters.end_date', '2026-10-31')
                    ->where('data.periodFilter.value', 'custom'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
