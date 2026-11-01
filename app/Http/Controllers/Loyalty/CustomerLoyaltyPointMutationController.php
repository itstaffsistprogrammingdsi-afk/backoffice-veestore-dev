<?php

namespace App\Http\Controllers\Loyalty;

use App\Domain\Loyalty\Queries\CustomerLoyaltyPointMutationQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\CustomerLoyaltyPointMutationIndexRequest;
use Inertia\Inertia;

class CustomerLoyaltyPointMutationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view loyalty-customer-loyalty-point-mutations')->only(['index']);
    }

    public function index(
        CustomerLoyaltyPointMutationIndexRequest $request,
        CustomerLoyaltyPointMutationQuery $query
    ) {
        $filters = $request->validated();

        return Inertia::render('Loyalty/Mutations', [
            'rows' => $query->paginate($filters),
            'clps' => $query->customerPoints(),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'type' => $filters['type'] ?? '',
                'clp' => $filters['clp'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'urls' => [
                'index' => route('loyalty.customer-loyalty-point-mutations.index'),
            ],
        ]);
    }
}
