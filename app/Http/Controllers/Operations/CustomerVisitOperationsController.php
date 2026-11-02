<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Queries\CustomerVisitPurchaseQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerVisitOperationsController extends Controller
{
    public function __invoke(Request $request, CustomerVisitPurchaseQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $storeId = $storeId !== '' ? $storeId : null;

        return Inertia::render('Operations/CustomersVisits', [
            ...$query->build($q, $storeId),
            'stores' => DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
            ],
            'urls' => [
                'index' => route('operations.customers.index'),
                'manage_customers' => route('master-data.customers.index'),
                'store_stock' => route('operations.store-stock.index'),
            ],
        ]);
    }
}
