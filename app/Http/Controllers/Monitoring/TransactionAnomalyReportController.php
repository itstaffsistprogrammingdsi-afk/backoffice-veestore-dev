<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionAnomalyReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $base = $this->paymentQuery($start, $end)
            ->when($filters['store_id'] !== '', fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('transaction_number', 'ILIKE', $like)
                        ->orWhere('store_name', 'ILIKE', $like)
                        ->orWhere('customer_name', 'ILIKE', $like)
                        ->orWhere('payment_methods', 'ILIKE', $like);
                });
            });

        $derived = DB::query()
            ->fromSub($base, 'x')
            ->selectRaw("
                x.*,
                (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END) AS expected_amount,
                ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END)) AS recording_difference,
                (COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) AS change_difference,
                CASE
                    WHEN COALESCE(x.method_count, 0) = 0 THEN 'Metode pembayaran belum dicatat'
                    WHEN ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END)) > 0.01 THEN 'Selisih pencatatan metode'
                    WHEN x.channel = 'store' AND ABS(COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) > 0.01 THEN 'Selisih pembayaran dan kembalian'
                    WHEN LOWER(COALESCE(x.status::text, '')) NOT IN ('paid','success','settlement','capture','completed') THEN 'Pembayaran belum lunas'
                    ELSE 'Sesuai'
                END AS anomaly_type
            ");

        if ($filters['exception_only']) {
            $derived->whereRaw("
                CASE
                    WHEN COALESCE(x.method_count, 0) = 0 THEN 'Metode pembayaran belum dicatat'
                    WHEN ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END)) > 0.01 THEN 'Selisih pencatatan metode'
                    WHEN x.channel = 'store' AND ABS(COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) > 0.01 THEN 'Selisih pembayaran dan kembalian'
                    WHEN LOWER(COALESCE(x.status::text, '')) NOT IN ('paid','success','settlement','capture','completed') THEN 'Pembayaran belum lunas'
                    ELSE 'Sesuai'
                END <> 'Sesuai'
            ");
        }

        if ($filters['anomaly_type'] !== '') {
            $derived->whereRaw("
                CASE
                    WHEN COALESCE(x.method_count, 0) = 0 THEN 'Metode pembayaran belum dicatat'
                    WHEN ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END)) > 0.01 THEN 'Selisih pencatatan metode'
                    WHEN x.channel = 'store' AND ABS(COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) > 0.01 THEN 'Selisih pembayaran dan kembalian'
                    WHEN LOWER(COALESCE(x.status::text, '')) NOT IN ('paid','success','settlement','capture','completed') THEN 'Pembayaran belum lunas'
                    ELSE 'Sesuai'
                END = ?
            ", [$filters['anomaly_type']]);
        }

        $rows = $derived
            ->orderByDesc('occurred_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $rows->through(function ($row) {
            $row->method_breakdown = $this->decodeBreakdown($row->method_breakdown);
            $row->details = $row->method_breakdown;

            return $row;
        });

        $summaryQuery = DB::query()->fromSub(clone $base, 'x');
        $summary = $summaryQuery
            ->selectRaw("
                COUNT(*)::int AS total_payments,
                COUNT(*) FILTER (
                    WHERE COALESCE(x.method_count, 0) = 0
                       OR ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END)) > 0.01
                       OR (x.channel = 'store' AND ABS(COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) > 0.01)
                       OR LOWER(COALESCE(x.status::text, '')) NOT IN ('paid','success','settlement','capture','completed')
                )::int AS total_anomalies,
                COALESCE(SUM(x.total_amount), 0)::numeric AS total_sales,
                COALESCE(SUM(x.paid_amount), 0)::numeric AS total_paid,
                COALESCE(SUM(x.change_amount), 0)::numeric AS total_change,
                COALESCE(SUM(x.detail_total), 0)::numeric AS total_recorded,
                COALESCE(SUM(ABS(COALESCE(x.detail_total, 0) - (CASE WHEN x.channel = 'store' THEN COALESCE(x.paid_amount, 0) ELSE COALESCE(x.total_amount, 0) END))), 0)::numeric AS total_recording_difference,
                COALESCE(SUM(CASE WHEN x.channel = 'store' THEN ABS(COALESCE(x.paid_amount, 0) - COALESCE(x.total_amount, 0) - COALESCE(x.change_amount, 0)) ELSE 0 END), 0)::numeric AS total_change_difference
            ")
            ->first();

        $flowRows = $this->flowRows($start, $end, $filters['store_id']);
        $paymentMethodRows = $this->paymentMethodRows($start, $end, $filters['store_id']);
        $stores = DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Laporan Anomali Transaksi',
            'description' => 'Bandingkan permintaan barang, penjualan, penerimaan pembayaran, dan pencatatan pembayaran per metode (contoh BCA, BRI, tunai) beserta selisihnya.',
            'data' => [
                'rows' => $rows,
                'flowRows' => $flowRows,
                'paymentMethodRows' => $paymentMethodRows,
                'summary' => (array) $summary + [
                    'total_flow_difference' => (float) $flowRows->sum('recording_gap'),
                    'total_quantity_gap' => (int) $flowRows->sum('quantity_gap'),
                ],
                'stores' => $stores,
                'filters' => $filters,
                'anomalyTypes' => [
                    ['value' => 'Metode pembayaran belum dicatat', 'label' => 'Metode pembayaran belum dicatat'],
                    ['value' => 'Selisih pencatatan metode', 'label' => 'Selisih pencatatan metode'],
                    ['value' => 'Selisih pembayaran dan kembalian', 'label' => 'Selisih pembayaran dan kembalian'],
                    ['value' => 'Pembayaran belum lunas', 'label' => 'Pembayaran belum lunas'],
                ],
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function paymentQuery(Carbon $start, Carbon $end)
    {
        $methodName = $this->methodNameExpression();
        $methodType = $this->methodTypeExpression();

        $details = DB::table('payment_details as pd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->select('pd.payment_id')
            ->selectRaw("
                COALESCE(SUM(pd.amount), 0)::numeric AS detail_total,
                COUNT(pd.id)::int AS method_count,
                STRING_AGG({$methodName} || ': ' || TO_CHAR(pd.amount, 'FM999G999G999G999'), ', ' ORDER BY {$methodName}) AS payment_methods,
                JSON_AGG(JSON_BUILD_OBJECT(
                    'method', {$methodName},
                    'type', {$methodType},
                    'amount', pd.amount,
                    'status', pd.status::text
                ))::text AS method_breakdown
            ")
            ->groupBy('pd.payment_id');

        return DB::table('payments as p')
            ->leftJoin('store_visit_payments as svp', 'svp.payment_id', '=', 'p.id')
            ->leftJoin('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->leftJoin('stores as s', 's.id', '=', 'v.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoinSub($details, 'd', fn ($join) => $join->on('d.payment_id', '=', 'p.id'))
            ->whereNull('p.deleted_at')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$start, $end])
            ->select([
                'p.id as payment_id',
                'p.transaction_number',
                's.name as store_name',
                'c.name as customer_name',
                'p.total_amount',
                'p.paid_amount',
                'p.change_amount',
                'p.completed_at',
                'p.created_at',
                DB::raw('p.app::text as channel'),
                DB::raw('p.status::text as status'),
                DB::raw('COALESCE(p.completed_at, p.created_at) as occurred_at'),
                DB::raw('COALESCE(d.detail_total, 0)::numeric as detail_total'),
                DB::raw('COALESCE(d.method_count, 0)::int as method_count'),
                DB::raw("COALESCE(d.payment_methods, '-') as payment_methods"),
                DB::raw("COALESCE(d.method_breakdown, '[]') as method_breakdown"),
            ]);
    }

    private function flowRows(Carbon $start, Carbon $end, string $storeId): Collection
    {
        $requested = DB::table('store_orders as so')
            ->whereRaw("COALESCE(so.order_number, '') NOT LIKE 'TRF-%'")
            ->join('store_order_details as d', 'd.store_order_id', '=', 'so.id')
            ->whereRaw("LOWER(so.status::text) NOT IN ('draft','rejected','cancelled')")
            ->whereBetween(DB::raw('COALESCE(so.requested_at, so.created_at)'), [$start, $end])
            ->select('so.store_id')
            ->selectRaw('
                COUNT(DISTINCT so.id)::int AS request_count,
                COALESCE(SUM(COALESCE(d.approved_qty, d.requested_qty, 0)), 0)::int AS requested_qty
            ')
            ->groupBy('so.store_id')
            ->get()
            ->keyBy('store_id');

        $sold = DB::table('store_carts as sc')
            ->join('store_cart_details as d', 'd.cart_id', '=', 'sc.id')
            ->whereRaw("LOWER(sc.status::text) = 'paid'")
            ->whereBetween(DB::raw('COALESCE(sc.updated_at, sc.created_at)'), [$start, $end])
            ->select('sc.store_id')
            ->selectRaw('
                COUNT(DISTINCT sc.id)::int AS sale_count,
                COUNT(d.id)::int AS sold_qty,
                COALESCE(SUM(d.product_price_amount - COALESCE(d.discount_amount, 0)), 0)::numeric AS sold_value
            ')
            ->groupBy('sc.store_id')
            ->get()
            ->keyBy('store_id');

        $paidStatuses = "LOWER(p.status::text) IN ('paid','success','settlement','capture','completed')";

        $received = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw($paidStatuses)
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$start, $end])
            ->select('v.store_id')
            ->selectRaw('
                COUNT(DISTINCT p.id)::int AS payment_count,
                COALESCE(SUM(p.total_amount), 0)::numeric AS payment_total,
                COALESCE(SUM(p.paid_amount), 0)::numeric AS paid_total,
                COALESCE(SUM(p.change_amount), 0)::numeric AS change_total
            ')
            ->groupBy('v.store_id')
            ->get()
            ->keyBy('store_id');

        $recorded = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('payment_details as pd', 'pd.payment_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->whereRaw($paidStatuses)
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$start, $end])
            ->select('v.store_id')
            ->selectRaw('COALESCE(SUM(pd.amount), 0)::numeric AS recorded_total')
            ->groupBy('v.store_id')
            ->get()
            ->keyBy('store_id');

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->when($storeId !== '', fn ($q) => $q->where('id', $storeId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return $stores->map(function ($store) use ($requested, $sold, $received, $recorded) {
            $requestRow = $requested->get($store->id);
            $soldRow = $sold->get($store->id);
            $receivedRow = $received->get($store->id);
            $recordedRow = $recorded->get($store->id);

            $requestedQty = (int) ($requestRow->requested_qty ?? 0);
            $soldQty = (int) ($soldRow->sold_qty ?? 0);
            $paymentTotal = (float) ($receivedRow->payment_total ?? 0);
            $paidTotal = (float) ($receivedRow->paid_total ?? 0);
            $changeTotal = (float) ($receivedRow->change_total ?? 0);
            $recordedTotal = (float) ($recordedRow->recorded_total ?? 0);

            return [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'store_code' => $store->code,
                'request_count' => (int) ($requestRow->request_count ?? 0),
                'requested_qty' => $requestedQty,
                'sale_count' => (int) ($soldRow->sale_count ?? 0),
                'sold_qty' => $soldQty,
                'quantity_gap' => $requestedQty - $soldQty,
                'sold_value' => (float) ($soldRow->sold_value ?? 0),
                'payment_count' => (int) ($receivedRow->payment_count ?? 0),
                'payment_received' => $paymentTotal,
                'payment_paid' => $paidTotal,
                'payment_change' => $changeTotal,
                'payment_recorded' => $recordedTotal,
                'payment_gap' => round($paymentTotal - (float) ($soldRow->sold_value ?? 0), 2),
                'recording_gap' => round($recordedTotal - $paidTotal, 2),
                'change_gap' => round($paidTotal - $paymentTotal - $changeTotal, 2),
            ];
        })
            ->sortByDesc(fn (array $row) => abs($row['recording_gap']) + abs($row['change_gap']) + abs($row['quantity_gap']))
            ->values();
    }

    private function paymentMethodRows(Carbon $start, Carbon $end, string $storeId): Collection
    {
        return DB::table('payment_details as pd')
            ->join('payments as p', 'p.id', '=', 'pd.payment_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->leftJoin('store_visit_payments as svp', 'svp.payment_id', '=', 'p.id')
            ->leftJoin('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->leftJoin('stores as s', 's.id', '=', 'v.store_id')
            ->whereNull('p.deleted_at')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$start, $end])
            ->when($storeId !== '', fn ($q) => $q->where('v.store_id', $storeId))
            ->selectRaw("
                {$this->methodNameExpression()} AS payment_method_name,
                {$this->methodTypeExpression()} AS payment_method_type,
                COUNT(DISTINCT p.id)::int AS transaction_count,
                COALESCE(SUM(pd.amount), 0)::numeric AS recorded_amount,
                COALESCE(STRING_AGG(DISTINCT s.name, ', '), '-') AS store_names
            ")
            ->groupByRaw($this->methodNameExpression().', '.$this->methodTypeExpression())
            ->orderByDesc('recorded_amount')
            ->get();
    }

    private function methodNameExpression(): string
    {
        $json = "CASE WHEN pd.detail ~ '^\\s*\\{' THEN pd.detail::jsonb ELSE '{}'::jsonb END";

        $refName = "CASE WHEN pd.ref ~ '^\\s*\\{' THEN pd.ref::jsonb->>'name' ELSE pd.ref END";
        $refId = "CASE WHEN pd.ref ~ '^\\s*\\{' THEN pd.ref::jsonb->>'id' ELSE '' END";

        return "COALESCE(pm.name, NULLIF({$json}->'va_numbers'->0->>'bank', ''), NULLIF({$json}->>'issuer', ''), NULLIF({$json}->>'payment_type', ''), NULLIF({$json}->'acquirer'->>'name', ''), NULLIF({$json}->'acquirer'->>'id', ''), NULLIF({$json}->'channel'->>'id', ''), NULLIF({$refName}, ''), NULLIF({$refId}, ''), 'Metode tidak dikenal')";
    }

    private function methodTypeExpression(): string
    {
        $json = "CASE WHEN pd.detail ~ '^\\s*\\{' THEN pd.detail::jsonb ELSE '{}'::jsonb END";

        return "COALESCE(pm.type::text, NULLIF({$json}->>'payment_type', ''), NULLIF({$json}->'service'->>'id', ''), '-')";
    }

    private function decodeBreakdown(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->startOfMonth()->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'anomaly_type' => trim((string) $request->query('anomaly_type', '')),
            'exception_only' => $request->boolean('exception_only', true),
            'start_date' => $from,
            'end_date' => $to,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
