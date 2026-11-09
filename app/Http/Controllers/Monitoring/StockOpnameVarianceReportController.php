<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StockOpnameVarianceReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $base = $this->baseQuery($start, $end)
            ->when($filters['store_id'] !== '', fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when($filters['location_id'] !== '', fn ($q) => $q->where('location_id', $filters['location_id']));

        $filtered = DB::query()
            ->fromSub(clone $base, 'x')
            ->when($filters['opname_status'] !== '', fn ($q) => $q->where('opname_status', $filters['opname_status']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('product_name', 'ILIKE', $like)
                        ->orWhere('product_code', 'ILIKE', $like)
                        ->orWhere('store_name', 'ILIKE', $like)
                        ->orWhere('location_name', 'ILIKE', $like)
                        ->orWhere('opname_code', 'ILIKE', $like)
                        ->orWhere('pic_name', 'ILIKE', $like)
                        ->orWhere('approver_name', 'ILIKE', $like)
                        ->orWhere('difference_reason', 'ILIKE', $like);
                });
            });

        $itemRows = (clone $filtered)
            ->orderByDesc('report_at')
            ->orderByDesc('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $rows = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                x.session_id,
                MAX(x.opname_code) AS opname_code,
                MAX(x.opname_type) AS opname_type,
                MAX(x.opname_status) AS opname_status,
                MAX(x.counting_round) AS counting_round,
                x.store_id,
                MAX(x.store_name) AS store_name,
                MAX(x.store_code) AS store_code,
                MAX(x.location_name) AS location_name,
                MAX(x.pic_name) AS pic_name,
                MAX(x.approver_name) AS approver_name,
                MAX(x.approval_status_label) AS approval_status_label,
                MAX(x.scheduled_at) AS scheduled_at,
                MAX(x.started_at) AS started_at,
                MAX(x.finished_at) AS finished_at,
                MAX(x.approved_at) AS approved_at,
                MAX(x.report_at) AS report_at,
                COUNT(*)::int AS item_count,
                COUNT(*) FILTER (WHERE x.difference_qty <> 0)::int AS discrepancy_items,
                COUNT(*) FILTER (WHERE x.difference_qty < 0)::int AS shortage_items,
                COUNT(*) FILTER (WHERE x.difference_qty > 0)::int AS surplus_items,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS total_discrepancy_qty,
                COALESCE(SUM(x.difference_value), 0)::numeric AS net_discrepancy_value,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS total_discrepancy_value
            ')
            ->groupBy('x.session_id', 'x.store_id')
            ->orderByDesc('report_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $sessionIds = collect($rows->items())->pluck('session_id')->filter()->values()->all();
        $sessionDetails = collect();

        if ($sessionIds !== []) {
            $sessionDetails = DB::query()
                ->fromSub(clone $filtered, 'x')
                ->whereIn('x.session_id', $sessionIds)
                ->selectRaw('
                    x.session_id,
                    x.product_code,
                    x.product_name,
                    x.location_name,
                    x.system_stock,
                    x.physical_stock,
                    x.difference_qty,
                    x.unit_price,
                    x.difference_value,
                    x.difference_reason,
                    x.additional_note,
                    x.approval_status_label
                ')
                ->orderBy('x.product_name')
                ->get()
                ->groupBy('session_id');
        }

        $canDecide = $this->canDecide();
        $rows->through(function ($row) use ($sessionDetails, $canDecide) {
            $row->details = $sessionDetails->get($row->session_id, collect())->values()->all();
            $row->can_decide = $canDecide
                && $row->approved_at === null
                && in_array(strtolower((string) $row->opname_status), ['pending_approval', 'pending'], true);

            return $row;
        });

        $summary = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.session_id)::int AS total_sessions,
                COUNT(*)::int AS total_items,
                COUNT(*) FILTER (WHERE x.difference_qty <> 0)::int AS discrepancy_items,
                COUNT(*) FILTER (WHERE x.difference_qty < 0)::int AS shortage_items,
                COUNT(*) FILTER (WHERE x.difference_qty > 0)::int AS surplus_items,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS total_discrepancy_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS total_discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                COALESCE(SUM(x.difference_value), 0)::numeric AS net_discrepancy_value
            ')
            ->first();

        $storeSummary = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                x.store_id,
                x.store_name,
                COUNT(DISTINCT x.session_id)::int AS session_count,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS discrepancy_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                MAX(x.report_at) AS last_report_at,
                STRING_AGG(DISTINCT x.pic_name, \', \') AS pic_names
            ')
            ->groupBy('x.store_id', 'x.store_name')
            ->orderByDesc('discrepancy_value')
            ->get();

        $periodExpr = match ($filters['group_by']) {
            'monthly' => "DATE_TRUNC('month', x.report_at)",
            'weekly' => "DATE_TRUNC('week', x.report_at)",
            default => "DATE_TRUNC('day', x.report_at)",
        };

        $periodFormat = $filters['group_by'] === 'monthly' ? 'Mon YYYY' : 'DD Mon YYYY';

        $periodRows = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw("
                {$periodExpr} AS period_start,
                TO_CHAR({$periodExpr}, '{$periodFormat}') AS period_label,
                x.store_id,
                x.store_name,
                COUNT(DISTINCT x.session_id)::int AS session_count,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS discrepancy_qty,
                COALESCE(SUM(CASE WHEN x.difference_qty < 0 THEN ABS(x.difference_qty) ELSE 0 END), 0)::int AS shortage_qty,
                COALESCE(SUM(CASE WHEN x.difference_qty > 0 THEN x.difference_qty ELSE 0 END), 0)::int AS surplus_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                STRING_AGG(DISTINCT x.pic_name, ', ') AS pic_names,
                STRING_AGG(DISTINCT x.approver_name, ', ') AS approver_names,
                MAX(x.report_at) AS last_report_at
            ")
            ->groupByRaw("{$periodExpr}, x.store_id, x.store_name")
            ->orderByDesc('period_start')
            ->orderByDesc('discrepancy_value')
            ->limit(500)
            ->get();

        $reasonRows = DB::query()
            ->fromSub(clone $base, 'x')
            ->selectRaw('
                x.difference_reason,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(x.difference_qty), 0)::int AS difference_qty,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS absolute_qty,
                COALESCE(SUM(x.difference_value), 0)::numeric AS difference_value
            ')
            ->where('x.difference_reason', '<>', '-')
            ->groupBy('x.difference_reason')
            ->orderByDesc('absolute_qty')
            ->get();

        $stores = DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $statuses = DB::table('store_opname_sessions')
            ->selectRaw('DISTINCT status::text AS status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');
        $locations = DB::table('locations')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Stok Opname, Selisih & Penyesuaian',
            'description' => 'Rekap selisih stok opname gudang kecil/toko dan display: nilai selisih, PIC, status persetujuan, serta rekap harian, mingguan, dan bulanan.',
            'data' => [
                'rows' => $rows,
                'opnameItems' => $itemRows,
                'storeSummary' => $storeSummary,
                'periodRows' => $periodRows,
                'reasonRows' => $reasonRows,
                'summary' => (array) $summary,
                'stores' => $stores,
                'locations' => $locations,
                'statuses' => $statuses,
                'filters' => $filters,
                'groupByOptions' => [
                    ['value' => 'daily', 'label' => 'Harian (tutup toko)'],
                    ['value' => 'weekly', 'label' => 'Mingguan (Manager Store)'],
                    ['value' => 'monthly', 'label' => 'Bulanan (HO)'],
                ],
            ],
            'filters' => $request->query(),
            'urls' => [
                'index' => url()->current(),
                'approve' => route('monitoring.stock-opname-variance.approve', ['id' => '__ID__']),
                'reject' => route('monitoring.stock-opname-variance.reject', ['id' => '__ID__']),
            ],
            'canDecide' => $canDecide,
        ]);
    }

    public function approve(Request $request, string $id)
    {
        return $this->decide($request, $id, 'approved');
    }

    public function reject(Request $request, string $id)
    {
        return $this->decide($request, $id, 'rejected');
    }

    private function decide(Request $request, string $id, string $status)
    {
        abort_unless($this->canDecide(), 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = DB::table('store_opname_sessions')->where('id', $id)->first();

        if (! $session) {
            return back()->with('error', 'Sesi stok opname tidak ditemukan.');
        }

        $currentStatus = strtolower((string) $session->status);

        if (! in_array($currentStatus, ['pending_approval', 'pending'], true)) {
            return back()->with('error', 'Hanya sesi opname yang menunggu persetujuan yang dapat diproses.');
        }

        $items = DB::table('store_opname_session_items as osi')
            ->leftJoin('products as p', 'p.id', '=', 'osi.product_id')
            ->leftJoin('locations as l', 'l.id', '=', 'osi.location_id')
            ->where('osi.opname_session_id', $id)
            ->get([
                'osi.product_id',
                'p.code as product_code',
                'p.name as product_name',
                'l.name as location_name',
                'osi.system_stock',
                'osi.physical_stock',
                'osi.discrepancy_reason',
                'osi.discrepancy_notes',
            ]);

        $decisions = $items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_code' => $item->product_code,
            'product_name' => $item->product_name,
            'location_name' => $item->location_name,
            'system_stock' => (int) $item->system_stock,
            'physical_stock' => (int) $item->physical_stock,
            'difference_qty' => (int) $item->physical_stock - (int) $item->system_stock,
            'discrepancy_reason' => $item->discrepancy_reason,
            'discrepancy_notes' => $item->discrepancy_notes,
        ])->values()->all();

        DB::transaction(function () use ($id, $session, $status, $validated, $decisions) {
            $now = now();

            $update = $status === 'approved'
                ? [
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => $now,
                    'updated_at' => $now,
                ]
                : [
                    'status' => 'rejected',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => $now,
                    'rejection_reasons' => $validated['reason'] ?? 'Ditolak HO/BO',
                    'updated_at' => $now,
                ];

            DB::table('store_opname_sessions')->where('id', $id)->update($update);

            DB::table('store_opname_approval_audits')->insert([
                'id' => (string) Str::uuid(),
                'opname_session_id' => $id,
                'counting_round' => (int) ($session->counting_round ?? 1),
                'actor_id' => auth()->id(),
                'action' => $status,
                'reason' => $validated['reason'] ?? null,
                'reviewed_at' => $now,
                'acted_at' => $now,
                'item_decisions' => json_encode($decisions),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with(
            'success',
            $status === 'approved'
                ? 'Sesi stok opname disetujui dan keputusan penyesuaian tercatat.'
                : 'Sesi stok opname ditolak dan alasan tercatat.'
        );
    }

    private function canDecide(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('approve store-opname-variance') || $user->can('manage store-opname')) {
            return true;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(preg_replace('/[^a-z0-9]/', '', $role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));
    }

    private function baseQuery(Carbon $start, Carbon $end)
    {
        $rankedPrices = DB::table('product_prices')
            ->whereRaw("LOWER(price_type::text) = 'base'")
            ->select(['product_id', 'price'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY valid_from DESC NULLS LAST, created_at DESC) AS row_rank');

        $latestPrices = DB::query()
            ->fromSub($rankedPrices, 'ranked_prices')
            ->where('row_rank', 1);

        return DB::table('store_opname_session_items as osi')
            ->join('store_opname_sessions as os', 'os.id', '=', 'osi.opname_session_id')
            ->leftJoin('stores as s', 's.id', '=', 'os.store_id')
            ->leftJoin('locations as l', 'l.id', '=', 'osi.location_id')
            ->leftJoin('products as p', 'p.id', '=', 'osi.product_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'os.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'os.approved_by')
            ->leftJoinSub($latestPrices, 'price', fn ($join) => $join->on('price.product_id', '=', 'osi.product_id'))
            ->whereBetween(DB::raw('COALESCE(os.finished_at, os.started_at, os.scheduled_at, os.created_at)'), [$start, $end])
            ->selectRaw("
                os.id AS session_id,
                os.name AS opname_code,
                os.type AS opname_type,
                os.status::text AS opname_status,
                os.counting_round,
                os.scheduled_at,
                os.started_at,
                os.finished_at,
                os.approved_at,
                os.created_at AS session_created_at,
                os.store_id,
                COALESCE(s.name, '-') AS store_name,
                COALESCE(s.code, '-') AS store_code,
                osi.location_id,
                COALESCE(l.name, '-') AS location_name,
                COALESCE(l.type::text, '-') AS location_type,
                osi.product_id,
                COALESCE(p.code, '-') AS product_code,
                COALESCE(p.name, '-') AS product_name,
                COALESCE(osi.system_stock, 0)::int AS system_stock,
                COALESCE(osi.physical_stock, 0)::int AS physical_stock,
                (COALESCE(osi.physical_stock, 0) - COALESCE(osi.system_stock, 0))::int AS difference_qty,
                COALESCE(price.price, 0)::numeric AS unit_price,
                ((COALESCE(osi.physical_stock, 0) - COALESCE(osi.system_stock, 0)) * COALESCE(price.price, 0))::numeric AS difference_value,
                COALESCE(osi.discrepancy_reason, '-') AS difference_reason,
                COALESCE(osi.discrepancy_notes, '-') AS additional_note,
                COALESCE(creator.name, '-') AS pic_name,
                COALESCE(approver.name, '-') AS approver_name,
                CASE
                    WHEN os.approved_at IS NOT NULL THEN 'Disetujui'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('pending_approval','pending') THEN 'Menunggu persetujuan'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('rejected') THEN 'Ditolak'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('in_progress','counting') THEN 'Berjalan'
                    ELSE COALESCE(os.status::text, '-')
                END AS approval_status_label,
                COALESCE(os.finished_at, os.started_at, os.scheduled_at, os.created_at) AS report_at
            ");
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->startOfMonth()->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $groupBy = $request->query('group_by');
        $groupBy = in_array($groupBy, ['daily', 'weekly', 'monthly'], true) ? $groupBy : 'daily';

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'location_id' => trim((string) $request->query('location_id', '')),
            'opname_status' => trim((string) $request->query('opname_status', '')),
            'group_by' => $groupBy,
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
