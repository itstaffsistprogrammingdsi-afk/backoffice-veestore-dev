<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BusinessOverviewController extends Controller
{
    public function index(Request $request, BusinessOverviewQuery $query)
    {
        $data = $query->build($request->query());
        $data['payments'] = $query->paymentsSnapshot($request->query());

        return Inertia::render('Reports/BusinessOverview', $data + [
            'periodOptions' => [
                ['value' => 'today', 'label' => 'Hari ini'],
                ['value' => 'week', 'label' => 'Minggu ini'],
                ['value' => 'month', 'label' => 'Bulan ini'],
                ['value' => 'year', 'label' => 'Tahun ini'],
                ['value' => 'last_month', 'label' => 'Bulan lalu'],
            ],
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'warehouses' => DB::table('warehouses')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'urls' => [
                'index' => route('reports.business-overview.index'),
                'sales' => route('monitoring.payment-income-realtime.index'),
                'sales_omset' => route('monitoring.omset-cabang.index'),
                'top_products' => route('monitoring.produk-terlaris.index'),
                'orders' => route('monitoring.fulfillment-control.index'),
                'payments' => route('monitoring.payment-reconciliation.index'),
                'pending_transactions' => route('monitoring.pending-transactions.index'),
                'anomalies' => route('monitoring.transaction-anomalies.index'),
                'ecommerce_orders' => route('monitoring.order-handled.index'),
                'store_orders' => route('monitoring.order-handled.store-orders'),
                'store_returns' => route('monitoring.order-handled.store-returns'),
                'stock_store' => route('monitoring.stok-percabang'),
                'stock_warehouse' => route('monitoring.stok-wms'),
                'warehouse_inbound' => route('monitoring.warehouse-inbound'),
                'warehouse_outbound' => route('monitoring.warehouse-outbound.index'),
                'store_inbound' => route('monitoring.store-inbound.index'),
                'store_outbound' => route('monitoring.store-outbound.index'),
                'expiring' => route('monitoring.product-slowmoving.index', ['dataset' => 'expiring']),
                'slow_moving' => route('monitoring.product-slowmoving.index'),
                'aging' => route('monitoring.product-aging.index'),
                'monitoring' => route('monitoring.command-center.index'),
                'crm' => route('reports.crm.index'),
                'demographics' => route('reports.crm.demographics'),
                'preference' => route('reports.crm.customer-preference.index'),
            ],
        ]);
    }
}
