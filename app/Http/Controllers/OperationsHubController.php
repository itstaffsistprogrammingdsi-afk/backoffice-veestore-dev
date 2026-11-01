<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsHubController extends Controller
{
    public function __invoke()
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
                'stock' => route('monitoring.stok-wms'),
            ],
        ]);
    }
}
