<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierInsightController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $itemTrace = $this->itemTraceQuery($filters);

        $supplierRows = DB::query()
            ->fromSub(clone $itemTrace, 'trace')
            ->select([
                'supplier_id',
                'supplier_name',
                'supplier_country',
                'supplier_phone',
            ])
            ->selectRaw('COUNT(DISTINCT inbound_id)::bigint AS total_inbounds')
            ->selectRaw('COUNT(DISTINCT item_id)::bigint AS total_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged)::bigint AS damaged_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_returned)::bigint AS returned_items')
            ->selectRaw('COUNT(DISTINCT product_id) FILTER (WHERE is_damaged OR is_returned)::bigint AS problem_products')
            ->selectRaw('MAX(received_date) AS last_inbound_at')
            ->selectRaw("
                ROUND(
                    COUNT(DISTINCT item_id) FILTER (WHERE is_damaged)::numeric
                    / NULLIF(COUNT(DISTINCT item_id), 0) * 100,
                    2
                ) AS damage_rate
            ")
            ->selectRaw("
                ROUND(
                    COUNT(DISTINCT item_id) FILTER (WHERE is_returned)::numeric
                    / NULLIF(COUNT(DISTINCT item_id), 0) * 100,
                    2
                ) AS return_rate
            ")
            ->groupBy('supplier_id', 'supplier_name', 'supplier_country', 'supplier_phone')
            ->orderByRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged OR is_returned) DESC')
            ->orderByDesc('damage_rate')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = DB::query()
            ->fromSub(clone $itemTrace, 'trace')
            ->selectRaw('COUNT(DISTINCT supplier_id)::bigint AS suppliers_monitored')
            ->selectRaw('COUNT(DISTINCT item_id)::bigint AS total_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged)::bigint AS damaged_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_returned)::bigint AS returned_items')
            ->selectRaw('COUNT(DISTINCT supplier_id) FILTER (WHERE is_damaged OR is_returned)::bigint AS suppliers_with_issue')
            ->first();

        $productRisks = DB::query()
            ->fromSub(clone $itemTrace, 'trace')
            ->select('supplier_id', 'supplier_name', 'product_id', 'product_name', 'product_code')
            ->selectRaw('COUNT(DISTINCT item_id)::bigint AS total_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged)::bigint AS damaged_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_returned)::bigint AS returned_items')
            ->selectRaw("STRING_AGG(DISTINCT NULLIF(condition_name, ''), ', ') FILTER (WHERE is_damaged) AS damage_conditions")
            ->selectRaw('MAX(qc_note) FILTER (WHERE is_damaged) AS latest_note')
            ->groupBy('supplier_id', 'supplier_name', 'product_id', 'product_name', 'product_code')
            ->havingRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged OR is_returned) > 0')
            ->orderByRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged OR is_returned) DESC')
            ->limit(15)
            ->get();

        $monthlyTrend = DB::query()
            ->fromSub(clone $itemTrace, 'trace')
            ->selectRaw("TO_CHAR(received_date, 'YYYY-MM') AS period")
            ->selectRaw('COUNT(DISTINCT item_id)::bigint AS received_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_damaged)::bigint AS damaged_items')
            ->selectRaw('COUNT(DISTINCT item_id) FILTER (WHERE is_returned)::bigint AS returned_items')
            ->groupByRaw("TO_CHAR(received_date, 'YYYY-MM')")
            ->orderBy('period')
            ->get();

        $suppliers = DB::table('suppliers')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $topRisk = $supplierRows->getCollection()->first();
        $damageRate = (int) ($summary->total_items ?? 0) > 0
            ? round(((int) ($summary->damaged_items ?? 0) / (int) $summary->total_items) * 100, 2)
            : 0;

        return view('content.monitoring.supplier-insight.index', compact(
            'supplierRows',
            'summary',
            'productRisks',
            'monthlyTrend',
            'suppliers',
            'filters',
            'topRisk',
            'damageRate'
        ));
    }

    private function itemTraceQuery(array $filters): Builder
    {
        $latestQc = DB::table('warehouse_qc as qc')
            ->joinSub(
                DB::table('warehouse_qc')
                    ->select('item_id')
                    ->selectRaw('MAX(COALESCE(performed_at, created_at)) AS latest_at')
                    ->groupBy('item_id'),
                'latest',
                fn ($join) => $join->on('latest.item_id', '=', 'qc.item_id')
                    ->on(DB::raw('COALESCE(qc.performed_at, qc.created_at)'), '=', 'latest.latest_at')
            )
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
            ->select([
                'qc.item_id',
                'qc.status as qc_status',
                'qc.note as qc_note',
                'ic.name as condition_name',
            ]);

        $returnedItems = DB::table('store_return_details')
            ->select('item_id')
            ->selectRaw('COUNT(*)::bigint AS return_count')
            ->groupBy('item_id');

        $query = DB::table('items as i')
            ->join('warehouse_inbound_details as wid', 'wid.id', '=', 'i.warehouse_inbound_detail_id')
            ->join('warehouse_inbounds as wi', 'wi.id', '=', 'wid.warehouse_inbound_id')
            ->join('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($latestQc, 'lqc', 'lqc.item_id', '=', 'i.id')
            ->leftJoinSub($returnedItems, 'ret', 'ret.item_id', '=', 'i.id')
            ->whereNull('s.deleted_at')
            ->whereNull('p.deleted_at')
            ->when($filters['supplier_id'], fn ($q) => $q->where('s.id', $filters['supplier_id']))
            ->when($filters['date_from'], fn ($q) => $q->whereDate('wi.received_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('wi.received_date', '<=', $filters['date_to']))
            ->when($filters['q'], function ($q) use ($filters) {
                $like = '%' . $filters['q'] . '%';
                $q->where(fn ($sub) => $sub->where('s.name', 'ILIKE', $like)
                    ->orWhere('p.name', 'ILIKE', $like)
                    ->orWhere('p.code', 'ILIKE', $like));
            })
            ->select([
                's.id as supplier_id',
                's.name as supplier_name',
                's.country as supplier_country',
                's.phone as supplier_phone',
                'wi.id as inbound_id',
                'wi.received_date',
                'i.id as item_id',
                'p.id as product_id',
                'p.name as product_name',
                'p.code as product_code',
                'lqc.condition_name',
                'lqc.qc_status',
                'lqc.qc_note',
            ])
            ->selectRaw("
                (
                    LOWER(COALESCE(lqc.qc_status::text, '')) IN ('rejected','reject','failed','bad','damaged')
                    OR LOWER(COALESCE(lqc.condition_name, '')) SIMILAR TO '%(rusak|reject|bad|damage|defect|cacat)%'
                ) AS is_damaged
            ")
            ->selectRaw('(COALESCE(ret.return_count, 0) > 0) AS is_returned');

        return $query;
    }

    private function filters(Request $request): array
    {
        $supplierId = trim((string) $request->input('supplier_id', ''));
        if ($supplierId !== '' && !Str::isUuid($supplierId)) {
            $supplierId = '';
        }

        $dateFrom = $this->dateOrDefault($request->input('date_from'), now()->subMonths(6)->startOfMonth());
        $dateTo = $this->dateOrDefault($request->input('date_to'), now());
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        return [
            'supplier_id' => $supplierId ?: null,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'q' => trim((string) $request->input('q', '')),
            'per_page' => $perPage,
        ];
    }

    private function dateOrDefault(mixed $value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::parse((string) $value) : $default->copy();
        } catch (\Throwable) {
            return $default->copy();
        }
    }
}
