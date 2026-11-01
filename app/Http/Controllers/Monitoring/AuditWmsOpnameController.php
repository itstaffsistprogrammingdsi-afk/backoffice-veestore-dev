<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\AuditWmsOpnameExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class AuditWmsOpnameController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $q           = trim((string) $request->query('q', ''));
        $from        = $request->query('from');
        $to          = $request->query('to');
        $perPage     = 25;

        $rows = $this->baseQuery($request)
            ->orderByDesc('tanggal_dibuat')
            ->orderBy('warehouse_name')
            ->orderBy('product_name')
            ->paginate($perPage)
            ->withQueryString();

        $warehouses = DB::table('warehouses')
            ->when(
                DB::getSchemaBuilder()->hasColumn('warehouses', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Audit Wms Opnames',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'        => $rows,
            'warehouses'  => $warehouses,
            'warehouseId' => $warehouseId,
            'q'           => $q,
            'from'        => $from,
            'to'          => $to,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'audit-wms-stock-opname-' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AuditWmsOpnameExport($request), $filename);
    }

    public function detail(Request $request)
    {
        $headerId  = $request->input('headerId');
        $productId = $request->input('productId');

        $details = DB::table('warehouse_stock_opname_details as wsod')
            ->join('rfid_tags as rt', 'rt.id', '=', 'wsod.rfid_tag_id')
            ->join('items as i', 'i.rfid_tag_id', '=', 'rt.id')
            ->select([
                'rt.value as rfid',
                DB::raw('(wsod.status)::text as status'),
                'i.product_id',
            ])
            ->where('wsod.warehouse_stock_opname_id', $headerId)
            ->where('i.product_id', $productId)
            ->whereIn(DB::raw('(wsod.status)::text'), ['missing', 'extra'])
            ->orderBy('wsod.status')
            ->orderBy('rt.value')
            ->get();

        $missing = $details->where('status', 'missing')->pluck('rfid')->values()->toArray();
        $extra   = $details->where('status', 'extra')->pluck('rfid')->values()->toArray();

        $maxRows = max(count($missing), count($extra));

        $result = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $result[] = [
                'no'           => $i + 1,
                'rfid_missing' => $missing[$i] ?? null,
                'rfid_extra'   => $extra[$i] ?? null,
            ];
        }

        return response()->json($result);
    }

    private function baseQuery(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $q           = trim((string) $request->query('q', ''));
        $from        = $request->query('from');
        $to          = $request->query('to');

        $conditionExpr = $this->resolveConditionExpression();

        $query = DB::table('warehouse_stock_opnames as o')
            ->join('warehouse_stock_opname_details as d', 'd.warehouse_stock_opname_id', '=', 'o.id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'd.rfid_tag_id')
            ->leftJoin('items as it', 'it.rfid_tag_id', '=', 'rt.id')
            ->leftJoin('products as p', 'p.id', '=', 'it.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'o.warehouse_id')
            ->when($warehouseId, fn ($qq) => $qq->where('o.warehouse_id', $warehouseId))
            ->when($from, fn ($qq) => $qq->whereDate('o.created_at', '>=', $from))
            ->when($to, fn ($qq) => $qq->whereDate('o.created_at', '<=', $to));

        $hasApprovedBy = Schema::hasTable('users')
            && Schema::hasColumn('warehouse_stock_opnames', 'approved_by');

        if ($hasApprovedBy) {
            $query->leftJoin('users as approved_user', 'approved_user.id', '=', 'o.approved_by');
        }

        if ($q !== '') {
            $like = '%' . strtolower($q) . '%';

            $query->where(function ($sub) use ($like, $conditionExpr) {
                $sub->whereRaw("LOWER(COALESCE(o.code, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(w.name, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(rt.value, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE((o.status)::text, '')) LIKE ?", [$like]);

                if ($hasApprovedBy) {
                    $sub->orWhereRaw("LOWER(COALESCE(approved_user.name, '')) LIKE ?", [$like]);
                }

                if ($conditionExpr !== 'NULL') {
                    $sub->orWhereRaw("LOWER(COALESCE(($conditionExpr)::text, '')) LIKE ?", [$like]);
                }
            });
        }

        $query->groupBy([
            'o.id',
            'o.code',
            'o.status',
            'o.created_at',
            'o.completed_at',
            'o.warehouse_id',
            'w.name',
            'p.id',
            'p.code',
            'p.name',
        ]);

        if ($hasApprovedBy) {
            $query->groupBy('approved_user.name');
        }

        $query->selectRaw("
            o.id AS opname_id,
            o.code AS opname_code,
            (o.status)::text AS opname_status,
            o.created_at AS tanggal_dibuat,
            o.completed_at AS tanggal_selesai,
            COALESCE(MAX(d.scanned_at), o.completed_at, o.created_at) AS scan_time,

            o.warehouse_id,
            w.name AS warehouse_name,

            " . ($hasApprovedBy ? "COALESCE(approved_user.name, '-')" : "'-'") . " AS approved_by_name,

            p.id AS product_id,
            p.code AS product_code,
            p.name AS product_name,

            SUM(CASE WHEN (d.status)::text = 'pending' THEN 1 ELSE 0 END) AS qty_pending,
            SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END) AS qty_system,
            SUM(CASE WHEN (d.status)::text IN ('match', 'extra') THEN 1 ELSE 0 END) AS qty_scanned,
            SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END) AS qty_match,
SUM(CASE WHEN (d.status)::text = 'missing' THEN 1 ELSE 0 END) AS qty_missing,
SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END) AS qty_extra,
0 AS qty_mismatch,

            (
                SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
            ) AS qty_diff,

            CASE
                WHEN (
                    SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                    - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
                ) = 0 THEN 'Balanced'
                WHEN (
                    SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                    - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
                ) > 0 THEN 'Over'
                ELSE 'Shortage'
            END AS status_label
        ");

        if ($conditionExpr !== 'NULL') {
            $query->selectRaw("
                COALESCE(
                    STRING_AGG(DISTINCT COALESCE(($conditionExpr)::text, '-'), ', '),
                    '-'
                ) AS item_conditions
            ");
        } else {
            $query->selectRaw("'-' AS item_conditions");
        }

        $query->selectRaw("
            COALESCE(
                STRING_AGG(DISTINCT COALESCE(rt.value, '-'), ', '),
                '-'
            ) AS rfid_list
        ");

        return $query;
    }

    private function resolveConditionExpression(): string
    {
        $schema = DB::getSchemaBuilder();

        if ($schema->hasColumn('items', 'condition')) {
            return 'it.condition';
        }

        if ($schema->hasColumn('items', 'item_condition')) {
            return 'it.item_condition';
        }

        if ($schema->hasColumn('items', 'barang_condition')) {
            return 'it.barang_condition';
        }

        if ($schema->hasColumn('items', 'status')) {
            return 'it.status';
        }

        if ($schema->hasColumn('items', 'item_status')) {
            return 'it.item_status';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'condition')) {
            return 'd.condition';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'item_condition')) {
            return 'd.item_condition';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'item_status')) {
            return 'd.item_status';
        }

        return 'NULL';
    }
}
