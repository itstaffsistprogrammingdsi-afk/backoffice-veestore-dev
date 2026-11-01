<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use Carbon\Carbon;

class CustomerVisitController extends Controller
{
    public function __construct()
    {
        // Halaman read-only
        $this->middleware('permission:view loyalty-customer-visits')->only(['index']);
    }

    public function index(Request $request)
    {
        // filters
        $wa       = trim((string) $request->get('wa'));        // cari by wa_number
        $storeId  = $request->get('store_id', 'all');          // 'all' = semua cabang

        // Default tanggal = HARI INI jika user tidak mengisi
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        if (!$dateFrom && !$dateTo) {
            $dateFrom = Carbon::today()->toDateString();
            $dateTo   = Carbon::today()->toDateString();
        }

        // Normalisasi rentang
        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $end   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()     : null;
        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            [$dateFrom, $dateTo] = [$start->toDateString(), $end->toDateString()];
        }

        // Kolom virtual untuk filter & sort (pakai check_in_at jika ada, selain itu fallback created_at)
        $visitTsExpr = DB::raw('COALESCE(v.check_in_at, v.created_at)');

        $visits = DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->when($wa, fn($q) => $q->where('c.wa_number', 'ILIKE', "%{$wa}%"))
            ->when($storeId && $storeId !== 'all', fn($q) => $q->where('v.store_id', $storeId))
            // Filter tanggal berdasarkan COALESCE(check_in_at, created_at)
            ->when($start, fn($q) => $q->whereRaw('COALESCE(v.check_in_at, v.created_at) >= ?', [$start]))
            ->when($end,   fn($q) => $q->whereRaw('COALESCE(v.check_in_at, v.created_at) <= ?', [$end]))
            ->select([
                'v.id',
                'v.visit_code',
                'v.check_in_at',
                'v.check_out_at',
                'v.status',
                'v.created_at',
                DB::raw('COALESCE(v.check_in_at, v.created_at) AS visit_at'), // << untuk kolom Tanggal

                'c.id as customer_id',
                'c.name as customer_name',
                'c.wa_number',
                'c.email',

                's.id as store_id',
                's.name as store_name',
                's.code as store_code',
            ])
            ->orderByDesc($visitTsExpr)   // urut terbaru berdasar visit_at
            ->paginate(10)
            ->appends([
                'wa'        => $wa,
                'store_id'  => $storeId,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]);

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('content.loyalty.customer-visits.index', [
            'visits'   => $visits,
            'stores'   => $stores,
            'wa'       => $wa,
            'storeId'  => $storeId,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }
}
