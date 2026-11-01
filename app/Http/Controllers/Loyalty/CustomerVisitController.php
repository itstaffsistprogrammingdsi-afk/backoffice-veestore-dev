<?php

namespace App\Http\Controllers\Loyalty;

use App\Domain\Loyalty\Queries\CustomerVisitQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\CustomerVisitIndexRequest;
use Inertia\Inertia;

class CustomerVisitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view loyalty-customer-visits')->only(['index']);
    }

    public function index(CustomerVisitIndexRequest $request, CustomerVisitQuery $query)
    {
        $filters = $request->validated();
        $filters['wa'] = $filters['wa'] ?? '';
        $filters['store_id'] = $filters['store_id'] ?? 'all';
        $filters['date_from'] = $filters['date_from'] ?? now()->toDateString();
        $filters['date_to'] = $filters['date_to'] ?? now()->toDateString();

        return Inertia::render('Loyalty/Visits', [
            'visits' => $query->paginate($filters),
            'stores' => $query->stores(),
            'filters' => $filters,
            'urls' => [
                'index' => route('loyalty.customer-visits.index'),
            ],
        ]);
    }
}
