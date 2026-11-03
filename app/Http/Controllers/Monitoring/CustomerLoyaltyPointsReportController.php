<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\CustomerLoyaltyPointsReportExport;
use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerLoyaltyPointsReportController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $mutationType = $this->normalizeNullableString($request->query('mutation_type'));
        $type = $this->normalizeNullableString($request->query('type'));
        $registrationChannel = $this->normalizeNullableString($request->query('registration_channel'));
        $registrationStoreId = $this->normalizeNullableString($request->query('registration_store_id'));

        if (!in_array($registrationChannel, ['store', 'ecommerce'], true)) {
            $registrationChannel = null;
        }

        // Show / per_page dihilangkan dari tampilan.
        // Data tetap 10 per halaman.
        $perPage = 10;

        $pointRate = $this->pointRate();

        $mutationSummary = $this->mutationSummaryQuery(
            mutationType: $mutationType,
            type: $type,
            q: $q
        );

        $latestBalance = DB::table('customer_loyalty_points as clp')
            ->selectRaw("
                clp.customer_id,
                COALESCE(MAX(clp.balance), 0) as current_balance
            ")
            ->groupBy('clp.customer_id');

        $baseRowsQuery = DB::table('customers as c')
            ->leftJoinSub($latestBalance, 'clp_balance', function ($join) {
                $join->on('clp_balance.customer_id', '=', 'c.id');
            })
            ->leftJoinSub($mutationSummary, 'mut_summary', function ($join) {
                $join->on('mut_summary.customer_id', '=', 'c.id');
            })
            ->leftJoin('stores as s', function ($join) {
                $join->whereRaw('s.id::text = c.registration_source_id')
                    ->where('c.registration_channel', '=', 'store');
            })
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',
                'c.registration_channel',
                'c.registration_source_id',

                DB::raw("
                    CASE
                        WHEN c.registration_channel = 'store'
                            THEN COALESCE(s.name, 'Store Tidak Diketahui')
                        WHEN c.registration_channel = 'ecommerce'
                            THEN 'Ecommerce'
                        ELSE 'Tidak Diketahui'
                    END as branch_name
                "),

                DB::raw('COALESCE(clp_balance.current_balance, 0) as current_balance'),
                DB::raw('COALESCE(mut_summary.total_mutations, 0) as total_mutations'),
                DB::raw('COALESCE(mut_summary.total_point_in, 0) as total_point_in'),
                DB::raw('COALESCE(mut_summary.total_point_out, 0) as total_point_out'),
                DB::raw('mut_summary.last_mutation_at as last_mutation_at'),
            ])
            ->whereNull('c.deleted_at');

        if ($q !== '') {
            $like = "%{$q}%";

            $baseRowsQuery->where(function ($sub) use ($like) {
                $sub->where('c.name', 'ILIKE', $like)
                    ->orWhere('c.email', 'ILIKE', $like)
                    ->orWhere('c.wa_number', 'ILIKE', $like)
                    ->orWhereExists(function ($exists) use ($like) {
                        $exists->select(DB::raw(1))
                            ->from('customer_loyalty_point_mutations as search_mut')
                            ->whereColumn('search_mut.customer_id', 'c.id')
                            ->where(function ($inner) use ($like) {
                                $inner->where('search_mut.description', 'ILIKE', $like)
                                    ->orWhere('search_mut.ref', 'ILIKE', $like)
                                    ->orWhereRaw('search_mut.type::text ILIKE ?', [$like])
                                    ->orWhereRaw('search_mut.mutation_type::text ILIKE ?', [$like])
                                    ->orWhereRaw('search_mut.ref_id::text ILIKE ?', [$like]);
                            });
                    });
            });
        }

        if ($registrationChannel) {
            $baseRowsQuery->where('c.registration_channel', $registrationChannel);
        }

        if ($registrationStoreId) {
            $baseRowsQuery
                ->where('c.registration_channel', 'store')
                ->where('c.registration_source_id', (string) $registrationStoreId);
        }

        $summary = DB::query()
            ->fromSub(clone $baseRowsQuery, 'x')
            ->selectRaw("
                COUNT(*)::int as total_customer,
                COALESCE(SUM(x.current_balance), 0) as total_current_balance,
                COALESCE(SUM(x.total_point_in), 0) as total_point_in,
                COALESCE(SUM(x.total_point_out), 0) as total_point_out,
                COALESCE(SUM(x.total_mutations), 0)::int as total_mutations,
                COUNT(*) FILTER (WHERE x.current_balance > 0)::int as active_point_customer,
                COUNT(*) FILTER (WHERE x.registration_channel = 'store')::int as total_store_customer,
                COUNT(*) FILTER (WHERE x.registration_channel = 'ecommerce')::int as total_ecommerce_customer
            ")
            ->first();

        $branchChartRows = DB::query()
            ->fromSub(clone $baseRowsQuery, 'x')
            ->selectRaw("
                x.branch_name as label,
                COUNT(*)::int as total_customer,
                COALESCE(SUM(x.current_balance), 0) as total_balance,
                COALESCE(SUM(x.total_point_in), 0) as total_point_in,
                COALESCE(SUM(x.total_point_out), 0) as total_point_out,
                COALESCE(SUM(x.total_mutations), 0)::int as total_mutations
            ")
            ->groupBy('x.branch_name')
            ->orderByDesc('total_balance')
            ->limit(15)
            ->get();

        $topBranches = DB::query()
            ->fromSub(clone $baseRowsQuery, 'x')
            ->selectRaw("
                x.branch_name,
                COUNT(*)::int as total_customer,
                COALESCE(SUM(x.current_balance), 0) as total_balance,
                COALESCE(SUM(x.total_point_in), 0) as total_point_in,
                COALESCE(SUM(x.total_point_out), 0) as total_point_out,
                COALESCE(SUM(x.total_mutations), 0)::int as total_mutations
            ")
            ->groupBy('x.branch_name')
            ->orderByDesc('total_balance')
            ->limit(10)
            ->get();

        $topMembers = DB::query()
            ->fromSub(clone $baseRowsQuery, 'x')
            ->selectRaw("
                x.customer_id,
                x.customer_name,
                x.customer_email,
                x.customer_wa_number,
                x.branch_name,
                x.current_balance,
                x.total_point_in,
                x.total_point_out,
                x.total_mutations,
                x.last_mutation_at
            ")
            ->orderByDesc('x.current_balance')
            ->limit(10)
            ->get();

        $mutationTypeLabelSql = "
            CASE COALESCE(clpm.type::text, '')
                WHEN 'earn' THEN 'Earn'
                WHEN 'redeem' THEN 'Redeem'
                WHEN 'expire' THEN 'Expire'
                WHEN 'adjust' THEN 'Adjustment'
                WHEN 'referral_register' THEN 'Referral Register'
                WHEN 'referral_first_tx' THEN 'Referral First Transaction'
                WHEN 'void_reversal' THEN 'Void Reversal'
                ELSE 'Tidak Diketahui'
            END
        ";

        $mutationTypeChartRows = $this->mutationDashboardQuery(
            mutationType: $mutationType,
            type: $type,
            q: $q,
            registrationChannel: $registrationChannel,
            registrationStoreId: $registrationStoreId
        )
            ->selectRaw("
                {$mutationTypeLabelSql} as label,
                COUNT(*)::int as total
            ")
            ->groupByRaw($mutationTypeLabelSql)
            ->orderByDesc('total')
            ->get();

        $mutationTrendRows = $this->mutationDashboardQuery(
            mutationType: $mutationType,
            type: $type,
            q: $q,
            registrationChannel: $registrationChannel,
            registrationStoreId: $registrationStoreId
        )
            ->selectRaw("
                TO_CHAR(DATE(clpm.created_at), 'DD Mon') as label,
                DATE(clpm.created_at) as tanggal,
                COUNT(*)::int as total_mutation,
                COALESCE(SUM(CASE WHEN clpm.points > 0 THEN clpm.points ELSE 0 END), 0) as point_in,
                COALESCE(SUM(CASE WHEN clpm.points < 0 THEN ABS(clpm.points) ELSE 0 END), 0) as point_out
            ")
            ->where('clpm.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupByRaw('DATE(clpm.created_at)')
            ->orderBy('tanggal')
            ->get();

        $rows = $baseRowsQuery
            ->orderByDesc(DB::raw('COALESCE(mut_summary.last_mutation_at, c.updated_at, c.created_at)'))
            ->orderBy('c.name')
            ->paginate($perPage)
            ->withQueryString();

        $customerIds = $rows->getCollection()
            ->pluck('customer_id')
            ->filter()
            ->values()
            ->all();

        $detailRows = collect();

        if (!empty($customerIds)) {
            $detailRows = $this->allMutationDetailQuery()
                ->whereIn('clpm.customer_id', $customerIds)
                ->orderBy('c.name')
                ->orderByDesc('clpm.created_at')
                ->get()
                ->groupBy('customer_id');
        }

        $mutationTypes = DB::table('customer_loyalty_point_mutations')
            ->selectRaw('mutation_type::text as mutation_type')
            ->whereNotNull('mutation_type')
            ->distinct()
            ->orderBy('mutation_type')
            ->pluck('mutation_type');

        $types = DB::table('customer_loyalty_point_mutations')
            ->selectRaw('type::text as type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $typeOptions = collect([
            'adjust' => 'Adjustment',
            'earn' => 'Earn',
            'expire' => 'Expire',
            'redeem' => 'Redeem',
            'referral_first_tx' => 'Referral First Transaction',
            'referral_register' => 'Referral Register',
            'void_reversal' => 'Void Reversal',
        ])->only($types->toArray());

        $stores = DB::table('stores')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Customer Loyalty Points Report',
            'description' => 'Laporan monitoring.',
            'data' => compact(
            'rows',
            'detailRows',
            'q',
            'mutationType',
            'type',
            'registrationChannel',
            'registrationStoreId',
            'mutationTypes',
            'types',
            'typeOptions',
            'pointRate',
            'summary',
            'branchChartRows',
            'topBranches',
            'topMembers',
            'mutationTypeChartRows',
            'mutationTrendRows',
            'stores'
        ),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.customer-loyalty-points-report.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'customer-loyalty-points-report-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new CustomerLoyaltyPointsReportExport($request),
            $filename
        );
    }

    private function pointRate(): array
    {
        $raw = Settings::get('points.rate', []);

        $oldIdrBase = (int) ($raw['idr_base'] ?? 1000);
        $oldPointsPerBase = (float) (
            $raw['points_per_base']
            ?? (($raw['points_per_idr'] ?? 0.001) * max($oldIdrBase, 1))
        );

        $inIdrBase = (float) data_get($raw, 'in.idr_base', max($oldIdrBase, 1));
        $inPointsPerBase = (float) data_get($raw, 'in.points_per_base', max($oldPointsPerBase, 0.000001));
        $inPointsPerIdr = (float) data_get(
            $raw,
            'in.points_per_idr',
            $inIdrBase > 0 ? $inPointsPerBase / $inIdrBase : 0.001
        );

        $outPointsBase = (float) data_get($raw, 'out.points_base', 1);
        $outIdrPerBase = (float) data_get($raw, 'out.idr_per_base', 1000);
        $outIdrPerPoint = (float) data_get(
            $raw,
            'out.idr_per_point',
            $outPointsBase > 0 ? $outIdrPerBase / $outPointsBase : 1000
        );

        return [
            'in' => [
                'idr_base' => max($inIdrBase, 1),
                'points_per_base' => max($inPointsPerBase, 0.000001),
                'points_per_idr' => max($inPointsPerIdr, 0.000000001),
            ],
            'out' => [
                'points_base' => max($outPointsBase, 0.000001),
                'idr_per_base' => max($outIdrPerBase, 1),
                'idr_per_point' => max($outIdrPerPoint, 0),
            ],
        ];
    }

    private function mutationSummaryQuery(
        ?string $mutationType = null,
        ?string $type = null,
        string $q = ''
    ) {
        $query = DB::table('customer_loyalty_point_mutations as clpm')
            ->selectRaw("
                clpm.customer_id,
                COUNT(clpm.id) as total_mutations,
                COALESCE(SUM(CASE WHEN clpm.points > 0 THEN clpm.points ELSE 0 END), 0) as total_point_in,
                COALESCE(SUM(CASE WHEN clpm.points < 0 THEN ABS(clpm.points) ELSE 0 END), 0) as total_point_out,
                MAX(clpm.created_at) as last_mutation_at
            ")
            ->groupBy('clpm.customer_id');

        if ($mutationType) {
            $query->whereRaw('clpm.mutation_type::text = ?', [$mutationType]);
        }

        if ($type) {
            $query->whereRaw('clpm.type::text = ?', [$type]);
        }

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('clpm.description', 'ILIKE', $like)
                    ->orWhere('clpm.ref', 'ILIKE', $like)
                    ->orWhereRaw('clpm.type::text ILIKE ?', [$like])
                    ->orWhereRaw('clpm.mutation_type::text ILIKE ?', [$like])
                    ->orWhereRaw('clpm.ref_id::text ILIKE ?', [$like])
                    ->orWhereExists(function ($exists) use ($like) {
                        $exists->select(DB::raw(1))
                            ->from('customers as c_search')
                            ->whereColumn('c_search.id', 'clpm.customer_id')
                            ->where(function ($inner) use ($like) {
                                $inner->where('c_search.name', 'ILIKE', $like)
                                    ->orWhere('c_search.email', 'ILIKE', $like)
                                    ->orWhere('c_search.wa_number', 'ILIKE', $like);
                            });
                    });
            });
        }

        return $query;
    }

    private function mutationDashboardQuery(
        ?string $mutationType = null,
        ?string $type = null,
        string $q = '',
        ?string $registrationChannel = null,
        ?string $registrationStoreId = null
    ) {
        $query = DB::table('customer_loyalty_point_mutations as clpm')
            ->leftJoin('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('stores as s', function ($join) {
                $join->whereRaw('s.id::text = c.registration_source_id')
                    ->where('c.registration_channel', '=', 'store');
            })
            ->whereNull('c.deleted_at');

        if ($mutationType) {
            $query->whereRaw('clpm.mutation_type::text = ?', [$mutationType]);
        }

        if ($type) {
            $query->whereRaw('clpm.type::text = ?', [$type]);
        }

        if ($registrationChannel) {
            $query->where('c.registration_channel', $registrationChannel);
        }

        if ($registrationStoreId) {
            $query
                ->where('c.registration_channel', 'store')
                ->where('c.registration_source_id', (string) $registrationStoreId);
        }

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('c.name', 'ILIKE', $like)
                    ->orWhere('c.email', 'ILIKE', $like)
                    ->orWhere('c.wa_number', 'ILIKE', $like)
                    ->orWhere('clpm.description', 'ILIKE', $like)
                    ->orWhere('clpm.ref', 'ILIKE', $like)
                    ->orWhereRaw('clpm.type::text ILIKE ?', [$like])
                    ->orWhereRaw('clpm.mutation_type::text ILIKE ?', [$like])
                    ->orWhereRaw('clpm.ref_id::text ILIKE ?', [$like]);
            });
        }

        return $query;
    }

    private function allMutationDetailQuery()
    {
        return DB::table('customer_loyalty_point_mutations as clpm')
            ->leftJoin('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('customer_loyalty_points as clp', 'clp.customer_id', '=', 'clpm.customer_id')
            ->leftJoin('stores as s', function ($join) {
                $join->whereRaw('s.id::text = c.registration_source_id')
                    ->where('c.registration_channel', '=', 'store');
            })
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
                'c.registration_channel',

                DB::raw("
                    CASE
                        WHEN c.registration_channel = 'store'
                            THEN COALESCE(s.name, 'Store Tidak Diketahui')
                        WHEN c.registration_channel = 'ecommerce'
                            THEN 'Ecommerce'
                        ELSE 'Tidak Diketahui'
                    END as branch_name
                "),

                DB::raw('COALESCE(clp.balance, 0) as current_balance'),

                DB::raw("
                    CASE COALESCE(clpm.type::text, '')
                        WHEN 'earn' THEN 'Earn'
                        WHEN 'redeem' THEN 'Redeem'
                        WHEN 'expire' THEN 'Expire'
                        WHEN 'adjust' THEN 'Adjustment'
                        WHEN 'referral_register' THEN 'Referral Register'
                        WHEN 'referral_first_tx' THEN 'Referral First Transaction'
                        WHEN 'void_reversal' THEN 'Void Reversal'
                        ELSE COALESCE(clpm.type::text, '-')
                    END as type_label
                "),

                DB::raw("
                    CASE COALESCE(clpm.ref::text, '')
                        WHEN 'payment' THEN 'Payment'
                        WHEN 'ecommerce_order' THEN 'Ecommerce Order'
                        WHEN 'store_visit_payment' THEN 'Store Visit Payment'
                        WHEN 'customer' THEN 'Customer'
                        WHEN 'customer_loyalty_point_mutation' THEN 'Loyalty Point Mutation'
                        WHEN 'void_transaction' THEN 'Void Transaction'
                        ELSE COALESCE(clpm.ref::text, '-')
                    END as ref_label
                "),

                DB::raw("
                    CASE
                        WHEN clpm.points > 0 THEN clpm.points
                        ELSE 0
                    END as point_in
                "),

                DB::raw("
                    CASE
                        WHEN clpm.points < 0 THEN ABS(clpm.points)
                        ELSE 0
                    END as point_out
                "),
            ]);
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
