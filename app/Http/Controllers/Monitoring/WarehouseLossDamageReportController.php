<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WarehouseLossDamageReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $filtered = $this->filteredQuery($filters);

        $rows = (clone $filtered)
            ->orderByDesc('occurred_at')
            ->orderBy('warehouse_name')
            ->orderBy('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = (clone $filtered)
            ->selectRaw("
                COUNT(*)::int as total_issues,
                COUNT(*) FILTER (WHERE issue_type = 'Hilang')::int as total_missing,
                COUNT(*) FILTER (WHERE issue_type = 'Rusak')::int as total_damaged,
                COUNT(*) FILTER (WHERE issue_type = 'Ekstra')::int as total_extra,
                COUNT(DISTINCT product_name)::int as affected_products,
                COUNT(DISTINCT warehouse_name)::int as affected_warehouses,
                COUNT(DISTINCT pic_name) FILTER (WHERE pic_name <> '-')::int as involved_users
            ")
            ->first();

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Barang Hilang & Rusak WMS',
            'description' => 'Barang hilang, ekstra, atau berubah kondisi di penyimpanan WMS beserta barang yang saat ini masih dalam kondisi Bad.',
            'data' => [
                'rows' => $rows,
                'summary' => (array) $summary,
                'warehouses' => $warehouses,
                'issueTypes' => [
                    ['value' => 'Hilang', 'label' => 'Hilang'],
                    ['value' => 'Rusak', 'label' => 'Rusak'],
                    ['value' => 'Ekstra', 'label' => 'Ekstra'],
                ],
                'filters' => $filters,
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        $union = $this->opnameIssueQuery()->unionAll($this->currentBadStockQuery());

        return DB::query()
            ->fromSub($union, 'issues')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('reference', 'ILIKE', $like)
                        ->orWhere('product_code', 'ILIKE', $like)
                        ->orWhere('product_name', 'ILIKE', $like)
                        ->orWhere('warehouse_name', 'ILIKE', $like)
                        ->orWhere('location_name', 'ILIKE', $like)
                        ->orWhere('pic_name', 'ILIKE', $like)
                        ->orWhere('description', 'ILIKE', $like);
                });
            })
            ->when($filters['warehouse_id'] !== '', fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when($filters['issue_type'] !== '', fn ($query) => $query->where('issue_type', $filters['issue_type']))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $filters['to']));
    }

    private function opnameIssueQuery()
    {
        return DB::table('warehouse_stock_opname_details as d')
            ->join('warehouse_stock_opnames as o', 'o.id', '=', 'd.warehouse_stock_opname_id')
            ->join('warehouses as w', 'w.id', '=', 'o.warehouse_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'd.rfid_tag_id')
            ->leftJoin('items as i', 'i.rfid_tag_id', '=', 'd.rfid_tag_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as after_condition', 'after_condition.id', '=', 'd.change_to_condition_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'd.created_by')
            ->leftJoin('users as condition_actor', 'condition_actor.id', '=', 'd.change_to_condition_by')
            ->leftJoin('users as move_actor', 'move_actor.id', '=', 'd.move_to_location_by')
            ->leftJoin('locations as move_location', 'move_location.id', '=', 'd.move_to_location_id')
            ->leftJoin('locations as opname_location', 'opname_location.id', '=', 'o.location_id')
            ->where(function ($query): void {
                $query->whereIn(DB::raw('(d.status)::text'), ['missing', 'extra'])
                    ->orWhereRaw("LOWER(COALESCE(after_condition.status::text, after_condition.name)) = 'bad'");
            })
            ->selectRaw("
                'Opname WMS' AS source_label,
                o.code AS reference,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                COALESCE(opname_location.name, move_location.name, '-') AS location_name,
                COALESCE(p.code, '-') AS product_code,
                COALESCE(p.name, '-') AS product_name,
                COALESCE(rt.value, '-') AS rfid_value,
                (d.status)::text AS opname_status,
                COALESCE(after_condition.name, '-') AS condition_after,
                CASE
                    WHEN (d.status)::text = 'missing' THEN 'Hilang'
                    WHEN (d.status)::text = 'extra' THEN 'Ekstra'
                    ELSE 'Rusak'
                END AS issue_type,
                1::int AS qty,
                COALESCE(condition_actor.name, move_actor.name, creator.name, '-') AS pic_name,
                COALESCE(d.scanned_at, d.updated_at, o.completed_at, o.created_at) AS occurred_at,
                CASE
                    WHEN (d.status)::text = 'missing' THEN 'Barang tidak ditemukan saat opname WMS'
                    WHEN (d.status)::text = 'extra' THEN 'Barang ditemukan saat opname tetapi tidak tercatat di sistem'
                    ELSE 'Kondisi barang berubah menjadi Bad saat opname WMS'
                END AS description
            ");
    }

    private function currentBadStockQuery()
    {
        $latestConditionHistory = DB::query()
            ->fromSub(
                DB::table('item_condition_histories as ich')
                    ->select('ich.item_id', 'ich.changed_by', 'ich.changed_at', 'ich.created_at')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY ich.item_id ORDER BY COALESCE(ich.changed_at, ich.created_at) DESC) AS row_rank'),
                'ranked_history'
            )
            ->where('row_rank', 1);

        return DB::table('items as i')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->leftJoin('locations as loc', 'loc.id', '=', 'i.current_location_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->leftJoinSub($latestConditionHistory, 'last_history', function ($join): void {
                $join->on('last_history.item_id', '=', 'i.id')
                    ->where('last_history.row_rank', '=', 1);
            })
            ->leftJoin('users as actor', 'actor.id', '=', 'last_history.changed_by')
            ->whereIn(DB::raw('i.status::text'), ['warehouse_stock'])
            ->whereRaw("LOWER(COALESCE(ic.status::text, ic.name)) = 'bad'")
            ->selectRaw("
                'Stok Saat Ini' AS source_label,
                COALESCE(rt.value, i.id::text) AS reference,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                COALESCE(loc.name, '-') AS location_name,
                COALESCE(p.code, '-') AS product_code,
                COALESCE(p.name, '-') AS product_name,
                COALESCE(rt.value, '-') AS rfid_value,
                NULL::text AS opname_status,
                COALESCE(ic.name, 'Bad') AS condition_after,
                'Rusak' AS issue_type,
                1::int AS qty,
                COALESCE(actor.name, '-') AS pic_name,
                COALESCE(last_history.changed_at, last_history.created_at, i.updated_at) AS occurred_at,
                'Stok WMS saat ini masih dalam kondisi Bad' AS description
            ");
    }

    private function filters(Request $request): array
    {
        $from = $this->normalizeDate($request->query('from')) ?: now()->startOfMonth()->toDateString();
        $to = $this->normalizeDate($request->query('to')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $issueType = trim((string) $request->query('issue_type', ''));
        $perPage = (int) ($request->query('per_page', 25) ?: 25);

        if (! in_array($issueType, ['Hilang', 'Rusak', 'Ekstra'], true)) {
            $issueType = '';
        }

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'issue_type' => $issueType,
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
