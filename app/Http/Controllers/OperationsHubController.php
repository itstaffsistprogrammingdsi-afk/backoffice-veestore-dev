<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsHubController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Operations/Index', [
            'stats' => [
                'warehouses' => DB::table('warehouses')->whereNull('deleted_at')->count(),
                'stores' => DB::table('stores')->whereNull('deleted_at')->count(),
                'products' => DB::table('products')->whereNull('deleted_at')->count(),
                'ecommerce_orders' => DB::table('ecommerce_orders')->whereIn('status', ['paid', 'processed', 'packing', 'shipped'])->count(),
            ],
            'urls' => [
                'warehouses' => route('master-data.warehouses.index'),
                'stores' => route('master-data.stores.index'),
                'ecommerce' => route('ecommerce.orders.index'),
                'stock' => route('operations.stock.index'),
                'inbound' => route('operations.inbound.index'),
                'outbound' => route('operations.outbound.index'),
                'customer_demographics' => route('operations.customers.demographics'),
                'store_orders' => route('operations.store-orders.index'),
                'store_returns' => route('operations.store-returns.index'),
            ],
            'can_review_store_orders' => $request->user()?->can('view branch-order-cabang') ?? false,
            'can_review_store_returns' => $request->user()?->can('view branch-retur-cabang') ?? false,
        ]);
    }
}
