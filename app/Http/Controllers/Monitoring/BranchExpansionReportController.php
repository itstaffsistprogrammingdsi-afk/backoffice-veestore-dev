<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchExpansionReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->regionRows($filters);
        $summary = $this->summary($rows);
        $paginated = $this->paginate($rows, $request, $filters);

        $provinces = DB::table('provinces')
            ->orderBy('name')
            ->get(['code', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Rekomendasi Cabang Baru',
            'description' => 'Gabungkan demografi kelurahan/kecamatan, pelanggan aktif, dan nilai penjualan untuk memprioritaskan wilayah pembukaan cabang offline.',
            'data' => [
                'rows' => $paginated,
                'topRegions' => $rows->take(10)->values(),
                'summary' => $summary,
                'provinces' => $provinces,
                'filters' => $filters,
                'levelOptions' => [
                    ['value' => 'district', 'label' => 'Kecamatan'],
                    ['value' => 'village', 'label' => 'Kelurahan / Desa'],
                ],
                'priorityOptions' => [
                    ['value' => 'high', 'label' => 'Prioritas tinggi'],
                    ['value' => 'medium', 'label' => 'Prioritas sedang'],
                    ['value' => 'watch', 'label' => 'Pantau'],
                ],
                'months' => $this->monthOptions(),
                'years' => $this->yearOptions(),
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function filters(Request $request): array
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $level = $request->query('level') === 'village' ? 'village' : 'district';
        $priority = trim((string) $request->query('priority', ''));
        $priority = in_array($priority, ['high', 'medium', 'watch'], true) ? $priority : '';

        return [
            'q' => trim((string) $request->query('q', '')),
            'level' => $level,
            'province_code' => trim((string) $request->query('province_code', '')),
            'regency_code' => trim((string) $request->query('regency_code', '')),
            'district_code' => trim((string) $request->query('district_code', '')),
            'priority' => $priority,
            'month' => (string) $month,
            'year' => (string) $year,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function monthOptions(): array
    {
        return [
            ['value' => '1', 'label' => 'Januari'],
            ['value' => '2', 'label' => 'Februari'],
            ['value' => '3', 'label' => 'Maret'],
            ['value' => '4', 'label' => 'April'],
            ['value' => '5', 'label' => 'Mei'],
            ['value' => '6', 'label' => 'Juni'],
            ['value' => '7', 'label' => 'Juli'],
            ['value' => '8', 'label' => 'Agustus'],
            ['value' => '9', 'label' => 'September'],
            ['value' => '10', 'label' => 'Oktober'],
            ['value' => '11', 'label' => 'November'],
            ['value' => '12', 'label' => 'Desember'],
        ];
    }

    private function yearOptions(): array
    {
        $current = (int) now()->year;

        return collect(range($current - 5, $current + 1))
            ->map(fn (int $year) => ['value' => (string) $year, 'label' => (string) $year])
            ->values()
            ->all();
    }

    private function regionRows(array $filters)
    {
        $start = Carbon::parse($filters['period_start'])->startOfDay();
        $end = Carbon::parse($filters['period_end'])->endOfDay();

        $paidStatuses = "LOWER(p.status::text) IN ('paid','success','settlement','capture','completed')";

        $posStats = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw($paidStatuses)
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, v.created_at)'), [$start, $end])
            ->select('v.customer_id')
            ->selectRaw('
                COUNT(DISTINCT p.id)::int AS pos_transactions,
                COALESCE(SUM(p.total_amount), 0)::numeric AS pos_spending,
                MAX(COALESCE(p.completed_at, p.created_at, v.created_at)) AS last_pos_at
            ')
            ->groupBy('v.customer_id');

        $ecomStats = DB::table('ecommerce_orders as eo')
            ->join('payments as ep', 'ep.id', '=', 'eo.payment_id')
            ->whereNull('ep.deleted_at')
            ->whereRaw("LOWER(ep.status::text) IN ('paid','success','settlement','capture','completed')")
            ->whereBetween('eo.created_at', [$start, $end])
            ->select('eo.customer_id')
            ->selectRaw('
                COUNT(*)::int AS ecom_orders,
                COALESCE(SUM(eo.grand_total), 0)::numeric AS ecom_spending,
                MAX(eo.created_at) AS last_ecom_at
            ')
            ->groupBy('eo.customer_id');

        $isVillage = $filters['level'] === 'village';

        $query = DB::table('customers as c')
            ->leftJoin('villages as v', 'v.code', '=', 'c.village_code')
            ->leftJoin('districts as d', 'd.code', '=', 'c.district_code')
            ->leftJoin('regencies as r', 'r.code', '=', 'c.regency_code')
            ->leftJoin('provinces as pr', 'pr.code', '=', 'c.province_code')
            ->leftJoinSub($posStats, 'pos', fn ($join) => $join->on('pos.customer_id', '=', 'c.id'))
            ->leftJoinSub($ecomStats, 'ecom', fn ($join) => $join->on('ecom.customer_id', '=', 'c.id'))
            ->whereNull('c.deleted_at')
            ->when($isVillage, fn ($q) => $q->whereNotNull('c.village_code'), fn ($q) => $q->whereNotNull('c.district_code'))
            ->when($filters['province_code'] !== '', fn ($q) => $q->where('c.province_code', $filters['province_code']))
            ->when($filters['regency_code'] !== '', fn ($q) => $q->where('c.regency_code', $filters['regency_code']))
            ->when($filters['district_code'] !== '', fn ($q) => $q->where('c.district_code', $filters['district_code']))
            ->when($filters['q'] !== '', function ($q) use ($filters, $isVillage) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like, $isVillage) {
                    $search->where('d.name', 'ILIKE', $like)
                        ->orWhere('r.name', 'ILIKE', $like)
                        ->orWhere('pr.name', 'ILIKE', $like);

                    if ($isVillage) {
                        $search->orWhere('v.name', 'ILIKE', $like);
                    }
                });
            });

        if ($isVillage) {
            $query->selectRaw('
                c.village_code AS region_code,
                c.district_code,
                COALESCE(v.name, \'Tanpa kelurahan\') AS region_name,
                COALESCE(d.name, \'-\') AS district_name,
                COALESCE(r.name, \'-\') AS regency_name,
                COALESCE(pr.name, \'-\') AS province_name
            ')->groupBy('c.village_code', 'c.district_code', 'v.name', 'd.name', 'r.name', 'pr.name');
        } else {
            $query->selectRaw('
                c.district_code AS region_code,
                c.district_code,
                COALESCE(d.name, \'Tanpa kecamatan\') AS region_name,
                COALESCE(d.name, \'-\') AS district_name,
                COALESCE(r.name, \'-\') AS regency_name,
                COALESCE(pr.name, \'-\') AS province_name
            ')->groupBy('c.district_code', 'd.name', 'r.name', 'pr.name');
        }

        $rows = $query
            ->selectRaw('
                COUNT(*)::int AS total_customers,
                COUNT(*) FILTER (
                    WHERE COALESCE(pos.pos_transactions, 0) + COALESCE(ecom.ecom_orders, 0) > 0
                )::int AS buying_customers,
                COALESCE(SUM(COALESCE(pos.pos_transactions, 0) + COALESCE(ecom.ecom_orders, 0)), 0)::int AS total_transactions,
                COALESCE(SUM(COALESCE(pos.pos_spending, 0)), 0)::numeric AS pos_spending,
                COALESCE(SUM(COALESCE(ecom.ecom_spending, 0)), 0)::numeric AS ecom_spending,
                COALESCE(SUM(COALESCE(pos.pos_spending, 0) + COALESCE(ecom.ecom_spending, 0)), 0)::numeric AS total_spending,
                MAX(GREATEST(pos.last_pos_at, ecom.last_ecom_at)) AS last_transaction_at
            ')
            ->get();

        $maxSpending = max(1, (float) $rows->max('total_spending'));
        $maxCustomers = max(1, (int) $rows->max('total_customers'));

        $mapped = $rows->map(function ($row) use ($maxSpending, $maxCustomers) {
            $spending = (float) $row->total_spending;
            $customers = (int) $row->total_customers;
            $score = round((($spending / $maxSpending) * 60) + (($customers / $maxCustomers) * 40), 1);

            $priority = $score >= 70 ? 'high' : ($score >= 45 ? 'medium' : 'watch');
            $priorityLabel = match ($priority) {
                'high' => 'Prioritas tinggi',
                'medium' => 'Prioritas sedang',
                default => 'Pantau',
            };

            return [
                'region_code' => $row->region_code,
                'region_name' => $row->region_name,
                'district_name' => $row->district_name,
                'regency_name' => $row->regency_name,
                'province_name' => $row->province_name,
                'total_customers' => $customers,
                'buying_customers' => (int) $row->buying_customers,
                'total_transactions' => (int) $row->total_transactions,
                'pos_spending' => (float) $row->pos_spending,
                'ecom_spending' => (float) $row->ecom_spending,
                'total_spending' => $spending,
                'average_spending' => $customers > 0 ? round($spending / $customers, 2) : 0,
                'last_transaction_at' => $row->last_transaction_at,
                'potential_score' => $score,
                'priority' => $priority,
                'priority_label' => $priorityLabel,
                'recommendation' => $priority === 'high'
                    ? 'Buka cabang atau gerai satelit'
                    : ($priority === 'medium' ? 'Kaji kemitraan / pop-up store' : 'Pantau pertumbuhan'),
            ];
        })
            ->when($filters['priority'] !== '', fn ($collection) => $collection->where('priority', $filters['priority']))
            ->sortByDesc('potential_score')
            ->values();

        return $mapped;
    }

    private function summary($rows): array
    {
        $top = $rows->first();

        return [
            'total_regions' => $rows->count(),
            'total_customers' => (int) $rows->sum('total_customers'),
            'buying_customers' => (int) $rows->sum('buying_customers'),
            'total_spending' => (float) $rows->sum('total_spending'),
            'total_transactions' => (int) $rows->sum('total_transactions'),
            'high_priority_regions' => $rows->where('priority', 'high')->count(),
            'top_region_name' => $top['region_name'] ?? '-',
            'top_region_score' => $top['potential_score'] ?? 0,
        ];
    }

    private function paginate($rows, Request $request, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = $filters['per_page'];

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );
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
