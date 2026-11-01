<?php

namespace App\Http\Controllers\Loyalty;

use App\Domain\Loyalty\Queries\CustomerLoyaltyPointQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\CustomerLoyaltyPointIndexRequest;
use Inertia\Inertia;

class CustomerLoyaltyPointController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view loyalty-customer-loyalty-points')->only(['index']);
    }

    public function index(CustomerLoyaltyPointIndexRequest $request, CustomerLoyaltyPointQuery $query)
    {
        $filters = $request->validated();

        return Inertia::render('Loyalty/Points', [
            'rows' => $query->paginate($filters['q'] ?? null),
            'filters' => [
                'q' => $filters['q'] ?? '',
            ],
            'urls' => [
                'index' => route('loyalty.customer-loyalty-points.index'),
            ],
        ]);
    }
}
