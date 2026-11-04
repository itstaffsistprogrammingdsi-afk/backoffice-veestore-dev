<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
