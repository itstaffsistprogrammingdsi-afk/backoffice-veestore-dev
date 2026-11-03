<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\RedeemPointReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RedeemPointReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $baseQuery = $this->baseQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COUNT(DISTINCT x.customer_id)::int AS total_customer,
                COUNT(x.id)::int AS total_mutations,
                COALESCE(SUM(x.redeem_points), 0)::numeric AS total_redeem_points,
                COALESCE(SUM(CASE WHEN DATE(x.created_at) = CURRENT_DATE THEN x.redeem_points ELSE 0 END), 0)::numeric AS total_redeem_today,
                COALESCE(SUM(CASE WHEN x.created_at >= date_trunc('month', CURRENT_DATE) THEN x.redeem_points ELSE 0 END), 0)::numeric AS total_redeem_this_month,
                MAX(x.created_at) AS latest_redeem_at
            ")
            ->first();

        $rows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                x.customer_id,
                MAX(x.customer_name) AS customer_name,
                MAX(x.customer_email) AS customer_email,
                MAX(x.customer_wa_number) AS customer_wa_number,
                MAX(x.customer_referral_code) AS customer_referral_code,
                MAX(x.current_balance) AS current_balance,
                COUNT(x.id)::int AS total_mutations,
                COALESCE(SUM(x.redeem_points), 0)::numeric AS total_redeem_points,
                MAX(x.created_at) AS last_redeem_at
            ")
            ->groupBy('x.customer_id')
            ->orderByDesc(DB::raw('MAX(x.created_at)'))
            ->paginate($filters['per_page'])
            ->withQueryString();

        $customerIds = $rows
            ->getCollection()
            ->pluck('customer_id')
            ->filter()
            ->values()
            ->all();

        $detailMutations = collect();

        if (!empty($customerIds)) {
            $detailMutations = DB::query()
                ->fromSub(clone $baseQuery, 'x')
                ->select([
                    'x.id',
                    'x.customer_id',
                    'x.ref_id',
                    'x.type',
                    'x.points',
                    'x.redeem_points',
                    'x.balance_after',
                    'x.description',
                    'x.created_at',
                    'x.updated_at',
                    'x.ref',
                    'x.customer_loyalty_point_id',
                    'x.mutation_type',
                    'x.expired_at',
                    'x.has_expiration',
                    'x.used_points',
                    'x.customer_name',
                    'x.customer_email',
                    'x.customer_wa_number',
                    'x.customer_referral_code',
                    'x.current_balance',
                ])
                ->whereIn('x.customer_id', $customerIds)
                ->orderByDesc('x.created_at')
                ->get()
                ->groupBy('customer_id');
        }

        $latestRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->select([
                'x.id',
                'x.customer_id',
                'x.customer_name',
                'x.customer_email',
                'x.customer_wa_number',
                'x.redeem_points',
                'x.balance_after',
                'x.ref',
                'x.description',
                'x.created_at',
            ])
            ->orderByDesc('x.created_at')
            ->limit(10)
            ->get();

        $dailyChartRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                TO_CHAR(DATE(x.created_at), 'DD Mon') AS label,
                DATE(x.created_at) AS tanggal,
                COALESCE(SUM(x.redeem_points), 0)::numeric AS total
            ")
            ->where('x.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupByRaw('DATE(x.created_at)')
            ->orderBy('tanggal')
            ->get();

        $topCustomerRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(x.customer_name, 'Customer Tidak Diketahui') AS label,
                COALESCE(SUM(x.redeem_points), 0)::numeric AS total
            ")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $customers = DB::table('customers')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'wa_number']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Redeem Point Report',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows' => $rows,
            'detailMutations' => $detailMutations,
            'latestRows' => $latestRows,
            'dailyChartRows' => $dailyChartRows,
            'topCustomerRows' => $topCustomerRows,
            'summary' => $summary,
            'customers' => $customers,

            'q' => $filters['q'],
            'customerId' => $filters['customer_id'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.redeem-point-report.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'redeem-point-report-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new RedeemPointReportExport($request), $filename);
    }

    private function filters(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'customer_id' => $request->query('customer_id'),
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
        ];
    }

    private function baseQuery(array $filters)
    {
        return DB::table('customer_loyalty_point_mutations as clpm')
            ->join('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('customer_loyalty_points as clp', 'clp.customer_id', '=', 'c.id')
            ->select([
                'clpm.id',
                'clpm.customer_id',
                'clpm.ref_id',
                'clpm.type',
                'clpm.points',
                'clpm.balance_after',
                'clpm.description',
                'clpm.created_at',
                'clpm.updated_at',
                'clpm.ref',
                'clpm.customer_loyalty_point_id',
                'clpm.mutation_type',
                'clpm.expired_at',
                'clpm.has_expiration',
                'clpm.used_points',

                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',
                'c.referral_code as customer_referral_code',

                'clp.balance as current_balance',

                DB::raw('ABS(COALESCE(clpm.points, 0)) as redeem_points'),
            ])
            ->where('clpm.type', 'redeem')
            ->when($filters['q'] !== '', function ($builder) use ($filters) {
                $like = "%{$filters['q']}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('c.wa_number', 'ILIKE', $like)
                        ->orWhere('c.referral_code', 'ILIKE', $like)
                        ->orWhere('clpm.description', 'ILIKE', $like)
                        ->orWhere('clpm.ref', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.ref_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.customer_id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($filters['customer_id'], function ($builder) use ($filters) {
                $builder->where('clpm.customer_id', $filters['customer_id']);
            })
            ->whereBetween(DB::raw('clpm.created_at::date'), [
                $filters['from'],
                $filters['to'],
            ]);
    }
}
