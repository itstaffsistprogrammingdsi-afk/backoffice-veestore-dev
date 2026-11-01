<?php

namespace App\Http\Controllers\Reports;

use App\Exports\VoucherUsageExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class VoucherReportController extends Controller
{
    public function index(Request $request)
    {

        $voucher = $request->voucher;
        $start = $request->start;
        $end = $request->end;

        $query = DB::table('vouchers as v')
            ->leftJoin('ecommerce_order_vouchers as ev', 'ev.master_id', '=', 'v.id')
            ->leftJoin(
                'ecommerce_orders as eo',
                'eo.checkout_voucher_snapshot_id',
                '=',
                'ev.id'
            )
            ->leftJoin('store_carts as sc', 'sc.voucher_id', '=', 'v.id');

        if ($voucher) {
            $query->where('v.code', $voucher);
        }

        if ($start) {
            $query->whereDate('eo.created_at', '>=', $start);
        }

        if ($end) {
            $query->whereDate('eo.created_at', '<=', $end);
        }

        $vouchers = $query->select(

            'v.id',
            'v.code',
            'v.name',
            'v.type',
            'v.value',
            'v.context',

            DB::raw("
count(distinct case
when eo.status='paid'
then eo.id
end) as ecommerce_used
"),

            DB::raw("
count(distinct case
when sc.status='paid'
then sc.id
end) as store_used
"),

            DB::raw("
count(distinct case when eo.status='paid' then eo.id end)
+
count(distinct case when sc.status='paid' then sc.id end)
as used_total
")

        )
            ->groupBy(
                'v.id',
                'v.code',
                'v.name',
                'v.type',
                'v.value',
                'v.context'
            )
            ->havingRaw("
count(distinct case when eo.status='paid' then eo.id end)
+
count(distinct case when sc.status='paid' then sc.id end)
> 0
")
            ->orderByDesc('used_total')
            ->paginate(15)
            ->withQueryString();

        $voucherList = DB::table('vouchers')
            ->orderBy('code')
            ->pluck('code');

        return Inertia::render('Reports/Voucher', [
            'vouchers' => $vouchers,
            'voucherList' => $voucherList,
            'filters' => [
                'voucher' => $voucher,
                'start' => $start,
                'end' => $end,
            ],
            'urls' => [
                'index' => route('reports.voucher.index'),
                'export' => route('reports.voucher.export'),
                'detail' => route('reports.voucher.detail', ['id' => '__ID__']),
            ],
        ]);

    }

    public function export(Request $request)
    {
        return Excel::download(new VoucherUsageExport($request), 'voucher-report.xlsx');
    }

    public function detail($id)
    {

        $ecommerce = DB::table('ecommerce_order_vouchers as ev')
            ->join(
                'ecommerce_orders as eo',
                'eo.checkout_voucher_snapshot_id',
                '=',
                'ev.id'
            )
            ->join(
                'customers as c',
                'c.id',
                '=',
                'eo.customer_id'
            )
            ->select(
                'eo.order_number',
                'c.name as customer_name',
                'ev.value',
                'eo.created_at',
                DB::raw("'Ecommerce' as source")
            )
            ->where('ev.master_id', $id)
            ->where('eo.status', 'paid');

        $store = DB::table('store_carts as sc')
            ->leftJoin(
                'customers as c',
                'c.id',
                '=',
                'sc.customer_id'
            )
            ->select(
                DB::raw("'-' as order_number"),
                'c.name as customer_name',
                'sc.voucher_discount as value',
                'sc.created_at',
                DB::raw("'Store' as source")
            )
            ->where('sc.voucher_id', $id)
            ->where('sc.status', 'paid');

        $data = $ecommerce
            ->unionAll($store)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($data);

    }
}
