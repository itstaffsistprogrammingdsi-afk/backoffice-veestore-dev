<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoyaltyPoint;
use Illuminate\Http\Request;

class CustomerLoyaltyPointController extends Controller
{
      public function __construct()
    {
        // Halaman read-only
        $this->middleware('permission:view loyalty-customer-loyalty-points')->only(['index']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = CustomerLoyaltyPoint::with(['customer'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('customer', function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('email', 'ILIKE', "%{$q}%")
                        ->orWhere('wa_number', 'ILIKE', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('content.loyalty.customer-loyalty-points.index', compact('rows', 'q'));
    }
}