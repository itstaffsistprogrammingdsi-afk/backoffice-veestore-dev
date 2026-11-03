<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessOverviewController extends Controller
{
    public function index(Request $request, BusinessOverviewQuery $query)
    {
        return Inertia::render('Reports/BusinessOverview', $query->build($request->query()) + [
            'urls' => [
                'index' => route('reports.business-overview.index'),
                'sales' => route('monitoring.payment-income-realtime.index'),
                'sales_omset' => route('monitoring.omset-cabang.index'),
                'top_products' => route('monitoring.produk-terlaris.index'),
                'orders' => route('monitoring.fulfillment-control.index'),
                'ecommerce_orders' => route('monitoring.order-handled.index'),
                'store_orders' => route('monitoring.order-handled.store-orders'),
                'store_returns' => route('monitoring.order-handled.store-returns'),
                'stock_store' => route('monitoring.stok-percabang'),
                'stock_warehouse' => route('monitoring.stok-wms'),
                'warehouse_inbound' => route('monitoring.warehouse-inbound'),
                'warehouse_outbound' => route('monitoring.warehouse-outbound.index'),
                'store_inbound' => route('monitoring.store-inbound.index'),
                'store_outbound' => route('monitoring.store-outbound.index'),
                'expiring' => route('monitoring.stok-mendekati-kedaluwarsa.index'),
                'slow_moving' => route('monitoring.product-slowmoving.index'),
                'aging' => route('monitoring.product-aging.index'),
                'monitoring' => route('monitoring.command-center.index'),
            ],
        ]);
    }
}
