<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\ReferralScoreReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReferralScoreReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->reportData($request);

        $data['filters'] = [
            'q' => $data['q'],
            'status' => $data['status'],
            'referrer_id' => $data['referrerId'],
            'store_id' => $data['storeId'],
            'from' => $data['from'],
            'to' => $data['to'],
        ];

        return Inertia::render('Monitoring/Report', [
            'title' => 'Referral & Afiliasi',
            'description' => 'Pantau referral, referrer, status transaksi pertama, cabang registrasi, dan poin afiliasi yang diperoleh.',
            'data' => $data,
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.referral-score-report.export')],
        ]);
    }

    public function reportData(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $status = $this->normalizeNullableString($request->query('status'));
        $referrerId = $this->normalizeNullableString($request->query('referrer_id'));
        $storeId = $this->normalizeNullableString($request->query('store_id'));

        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        // Show / per_page dihilangkan dari tampilan.
        // Pagination dibuat tetap 10 data per halaman.
        $perPage = 10;

        $baseQuery = $this->baseReferralQuery(
            q: $q,
            status: $status,
            referrerId: $referrerId,
            storeId: $storeId,
            from: $from,
            to: $to
        );

        $successStatusSql = "
            LOWER(COALESCE(x.status::text, '')) IN (
                'success',
                'sukses',
                'completed',
                'complete',
                'approved',
                'converted',
                'done'
            )
        ";

        $pendingStatusSql = "
            LOWER(COALESCE(x.status::text, '')) IN (
                'pending',
                'waiting',
                'process',
                'processing',
                'registered'
            )
        ";

        $summary = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COUNT(*)::int as total_referral,
                COUNT(DISTINCT x.referrer_id)::int as total_referrer,
                COUNT(DISTINCT x.referee_id)::int as total_referee,
                COUNT(*) FILTER (WHERE {$successStatusSql})::int as total_success,
                COUNT(*) FILTER (WHERE {$pendingStatusSql})::int as total_pending,
                COUNT(*) FILTER (
                    WHERE NOT ({$successStatusSql})
                      AND NOT ({$pendingStatusSql})
                )::int as total_other_status,
                COALESCE(SUM(x.referrer_score), 0) as total_score_row
            ")
            ->first();

        $topReferrers = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                x.referrer_id,
                COALESCE(x.referrer_name, 'Referrer Tidak Diketahui') as referrer_name,
                x.referrer_email,
                x.referrer_wa_number,
                COUNT(*)::int as total_referral,
                COUNT(DISTINCT x.referee_id)::int as total_referee,
                COUNT(*) FILTER (WHERE {$successStatusSql})::int as total_success,
                COUNT(*) FILTER (WHERE {$pendingStatusSql})::int as total_pending,
                COALESCE(MAX(x.referrer_score), 0) as score,
                MAX(x.created_at) as last_referral_at
            ")
            ->groupBy(
                'x.referrer_id',
                'x.referrer_name',
                'x.referrer_email',
                'x.referrer_wa_number'
            )
            ->orderByDesc('total_referral')
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        $topReferrerChartRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(x.referrer_name, 'Referrer Tidak Diketahui') as label,
                COUNT(*)::int as total_referral,
                COALESCE(MAX(x.referrer_score), 0) as score
            ")
            ->groupBy('x.referrer_name')
            ->orderByDesc('total_referral')
            ->limit(10)
            ->get();

        $statusChartRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(x.status::text, 'Tidak Diketahui') as label,
                COUNT(*)::int as total
            ")
            ->groupByRaw("COALESCE(x.status::text, 'Tidak Diketahui')")
            ->orderByDesc('total')
            ->get();

        $trendRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                TO_CHAR(DATE(x.created_at), 'DD Mon') as label,
                DATE(x.created_at) as tanggal,
                COUNT(*)::int as total_referral,
                COUNT(*) FILTER (WHERE {$successStatusSql})::int as total_success,
                COUNT(*) FILTER (WHERE {$pendingStatusSql})::int as total_pending
            ")
            ->groupByRaw('DATE(x.created_at)')
            ->orderBy('tanggal')
            ->get();

        $topCodeRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(NULLIF(x.code_used, ''), 'Tanpa Kode') as label,
                COUNT(*)::int as total_referral,
                COUNT(DISTINCT x.referrer_id)::int as total_referrer,
                COUNT(DISTINCT x.referee_id)::int as total_referee
            ")
            ->groupByRaw("COALESCE(NULLIF(x.code_used, ''), 'Tanpa Kode')")
            ->orderByDesc('total_referral')
            ->limit(10)
            ->get();

        $latestReferralRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw('
                x.id,
                x.code_used,
                x.status,
                x.created_at,
                x.referrer_name,
                x.referrer_email,
                x.referrer_branch_name,
                x.referee_name,
                x.referee_email,
                x.referrer_score
            ')
            ->orderByDesc('x.created_at')
            ->limit(10)
            ->get();

        $rows = $baseQuery
            ->orderByDesc('r.created_at')
            ->orderBy('referrer.name')
            ->paginate($perPage)
            ->withQueryString();

        $referrers = DB::table('customers')
            ->whereIn('id', function ($sub) {
                $sub->select('referrer_id')
                    ->from('referrals')
                    ->whereNotNull('referrer_id');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $statuses = DB::table('referrals')
            ->selectRaw('status::text as status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $affiliatePoints = DB::table('customer_loyalty_point_mutations')
            ->whereRaw("type::text IN ('referral_register','referral_first_tx')")
            ->select('customer_id')
            ->selectRaw("
                COALESCE(SUM(points) FILTER (WHERE type::text = 'referral_register'), 0)::numeric AS register_points,
                COALESCE(SUM(points) FILTER (WHERE type::text = 'referral_first_tx'), 0)::numeric AS first_tx_points,
                COALESCE(SUM(points), 0)::numeric AS total_points,
                MAX(created_at) AS last_point_at
            ")
            ->groupBy('customer_id');

        $affiliateRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->leftJoinSub($affiliatePoints, 'ap', fn ($join) => $join->on('ap.customer_id', '=', 'x.referrer_id'))
            ->selectRaw("
                x.referrer_id,
                COALESCE(MAX(x.referrer_name), 'Referrer Tidak Diketahui') AS referrer_name,
                MAX(x.referrer_email) AS referrer_email,
                MAX(x.referrer_wa_number) AS referrer_wa_number,
                COUNT(*)::int AS total_referral,
                COUNT(DISTINCT x.referee_id)::int AS total_referee,
                COUNT(*) FILTER (WHERE LOWER(COALESCE(x.status::text, '')) IN ('registered','first_transaction_done','success','completed','approved'))::int AS registered_referral,
                COUNT(*) FILTER (WHERE LOWER(COALESCE(x.status::text, '')) = 'first_transaction_done')::int AS first_transaction_referral,
                MAX(COALESCE(ap.register_points, 0))::numeric AS register_points,
                MAX(COALESCE(ap.first_tx_points, 0))::numeric AS first_tx_points,
                MAX(COALESCE(ap.total_points, 0))::numeric AS total_affiliate_points,
                MAX(ap.last_point_at) AS last_point_at
            ")
            ->groupBy('x.referrer_id')
            ->orderByDesc('total_affiliate_points')
            ->orderByDesc('total_referral')
            ->limit(100)
            ->get();

        $summary->total_affiliate_points = (float) $affiliateRows->sum(fn ($row) => (float) $row->total_affiliate_points);
        $summary->total_affiliate_referrer = $affiliateRows->count();
        $summary->total_first_transaction_referral = (int) $affiliateRows->sum('first_transaction_referral');

        return compact(
            'rows',
            'q',
            'status',
            'referrerId',
            'storeId',
            'from',
            'to',
            'referrers',
            'statuses',
            'stores',
            'summary',
            'topReferrers',
            'topReferrerChartRows',
            'statusChartRows',
            'trendRows',
            'topCodeRows',
            'latestReferralRows',
            'affiliateRows'
        );
    }

    public function export(Request $request)
    {
        $filename = 'monitoring-referral-score-report-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ReferralScoreReportExport($request), $filename);
    }

    private function baseReferralQuery(
        string $q = '',
        ?string $status = null,
        ?string $referrerId = null,
        ?string $storeId = null,
        ?string $from = null,
        ?string $to = null
    ) {
        $query = DB::table('referrals as r')
            ->leftJoin('customers as referrer', 'referrer.id', '=', 'r.referrer_id')
            ->leftJoin('customers as referee', 'referee.id', '=', 'r.referee_id')
            ->leftJoin('stores as referrer_store', function ($join) {
                $join->whereRaw('referrer_store.id::text = referrer.registration_source_id')
                    ->where('referrer.registration_channel', '=', 'store');
            })
            ->leftJoin('referrer_scores as rs', 'rs.customer_id', '=', 'r.referrer_id')
            ->select([
                'r.id',
                'r.referrer_id',
                'r.referee_id',
                'r.code_used',
                'r.status',
                'r.created_at',
                'r.updated_at',

                'referrer.name as referrer_name',
                'referrer.email as referrer_email',
                'referrer.wa_number as referrer_wa_number',
                'referrer.registration_channel as referrer_registration_channel',
                'referrer_store.name as referrer_branch_name',

                'referee.name as referee_name',
                'referee.email as referee_email',
                'referee.wa_number as referee_wa_number',

                DB::raw('COALESCE(rs.score, 0) as referrer_score'),
                DB::raw('COALESCE(rs.score, 0) as score'),

                DB::raw("
                    CASE
                        WHEN referee.name IS NOT NULL THEN 'Referral customer: ' || referee.name
                        ELSE 'Referral customer'
                    END as score_source
                "),
            ]);

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('referrer.name', 'ILIKE', $like)
                    ->orWhere('referrer.email', 'ILIKE', $like)
                    ->orWhere('referrer.wa_number', 'ILIKE', $like)
                    ->orWhere('referee.name', 'ILIKE', $like)
                    ->orWhere('referee.email', 'ILIKE', $like)
                    ->orWhere('referee.wa_number', 'ILIKE', $like)
                    ->orWhere('r.code_used', 'ILIKE', $like)
                    ->orWhereRaw('r.status::text ILIKE ?', [$like]);
            });
        }

        if ($status) {
            $query->whereRaw('r.status::text = ?', [$status]);
        }

        if ($referrerId) {
            $query->where('r.referrer_id', $referrerId);
        }

        if ($storeId) {
            $query->whereRaw('referrer_store.id::text = ?', [$storeId]);
        }

        if ($from && $to) {
            $query->whereBetween(DB::raw('r.created_at::date'), [$from, $to]);
        }

        return $query;
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
