<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoyaltyPointMutation;
use App\Models\CustomerLoyaltyPoint;
use Illuminate\Http\Request;

class CustomerLoyaltyPointMutationController extends Controller
{
       public function __construct()
    {
        // Hanya butuh izin View (read-only)
        $this->middleware('permission:view loyalty-customer-loyalty-point-mutations')->only(['index']);
    }
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));          // search nama/email/wa
        $type   = $request->get('type');                          // 'in' / 'out'
        $clpId  = $request->get('clp');                           // filter by customer_loyalty_point_id
        $dateFrom = $request->get('from');                        // YYYY-MM-DD
        $dateTo   = $request->get('to');                          // YYYY-MM-DD

        $rows = CustomerLoyaltyPointMutation::with(['loyaltyPoint.customer'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('loyaltyPoint.customer', function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('email', 'ILIKE', "%{$q}%")
                        ->orWhere('wa_number', 'ILIKE', "%{$q}%");
                });
            })
            ->when(in_array($type, ['in', 'out'], true), fn($qr) => $qr->where('type', $type))
            ->when(!empty($clpId), fn($qr) => $qr->where('customer_loyalty_point_id', $clpId))
            ->when($dateFrom, fn($qr) => $qr->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($qr) => $qr->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // untuk dropdown quick filter CLP (opsional)
        $clps = CustomerLoyaltyPoint::with('customer')->orderBy('updated_at', 'desc')->limit(50)->get();

        return view('content.loyalty.customer-loyalty-point-mutations.index', compact(
            'rows',
            'q',
            'type',
            'clpId',
            'dateFrom',
            'dateTo',
            'clps'
        ));
    }
}