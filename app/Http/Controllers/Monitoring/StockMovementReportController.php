<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockMovementReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $base = $this->movementQuery($start, $end);
        $filtered = DB::query()
            ->fromSub(clone $base, 'mv')
            ->when($filters['movement_type'] !== '', fn ($q) => $q->where('movement_type', $filters['movement_type']))
            ->when($filters['direction'] !== '', fn ($q) => $q->where('direction', $filters['direction']))
            ->when($filters['store_id'] !== '', fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when($filters['warehouse_id'] !== '', fn ($q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('reference', 'ILIKE', $like)
                        ->orWhere('store_name', 'ILIKE', $like)
                        ->orWhere('warehouse_name', 'ILIKE', $like)
                        ->orWhere('product_name', 'ILIKE', $like)
                        ->orWhere('product_code', 'ILIKE', $like)
                        ->orWhere('details', 'ILIKE', $like);
                });
            });

        $rows = (clone $filtered)
            ->orderByDesc('occurred_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = (clone $filtered)
            ->selectRaw('
                COUNT(*)::int AS total_movements,
                COUNT(*) FILTER (WHERE direction = \'masuk\')::int AS inbound_documents,
                COUNT(*) FILTER (WHERE direction = \'keluar\')::int AS outbound_documents,
                COUNT(*) FILTER (WHERE direction = \'ke display\')::int AS to_display_items,
                COUNT(*) FILTER (WHERE direction = \'ke gudang kecil\')::int AS to_store_stock_items,
                COALESCE(SUM(qty), 0)::int AS total_qty,
                COALESCE(SUM(qty) FILTER (WHERE direction = \'masuk\'), 0)::int AS inbound_qty,
                COALESCE(SUM(qty) FILTER (WHERE direction = \'keluar\'), 0)::int AS outbound_qty
            ')
            ->first();

        $dailyRows = DB::query()
            ->fromSub(clone $filtered, 'mv')
            ->selectRaw("
                DATE_TRUNC('day', occurred_at) AS period_start,
                TO_CHAR(DATE_TRUNC('day', occurred_at), 'DD Mon YYYY') AS period_label,
                movement_type,
                direction,
                COUNT(*)::int AS document_count,
                COALESCE(SUM(qty), 0)::int AS total_qty
            ")
            ->groupByRaw("DATE_TRUNC('day', occurred_at), movement_type, direction")
            ->orderByDesc('period_start')
            ->limit(400)
            ->get();

        $monthlyRows = DB::query()
            ->fromSub(clone $filtered, 'mv')
            ->selectRaw("
                DATE_TRUNC('month', occurred_at) AS period_start,
                TO_CHAR(DATE_TRUNC('month', occurred_at), 'Mon YYYY') AS period_label,
                movement_type,
                direction,
                COUNT(*)::int AS document_count,
                COALESCE(SUM(qty), 0)::int AS total_qty
            ")
            ->groupByRaw("DATE_TRUNC('month', occurred_at), movement_type, direction")
            ->orderByDesc('period_start')
            ->limit(200)
            ->get();

        $movementTypes = DB::query()
            ->fromSub(clone $filtered, 'mv')
            ->select('movement_type')
            ->distinct()
            ->orderBy('movement_type')
            ->pluck('movement_type');

        $stores = DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $warehouses = DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Stok Barang Keluar Masuk',
            'description' => 'Pergerakan stok real time dari gudang dan toko, rekap harian dan bulanan untuk stok opname, termasuk perpindahan gudang kecil ke display dan sebaliknya.',
            'data' => [
                'rows' => $rows,
                'dailyRows' => $dailyRows,
                'monthlyRows' => $monthlyRows,
                'summary' => (array) $summary,
                'stores' => $stores,
                'warehouses' => $warehouses,
                'movementTypes' => $movementTypes,
                'filters' => $filters,
                'directionOptions' => [
                    ['value' => 'masuk', 'label' => 'Masuk'],
                    ['value' => 'keluar', 'label' => 'Keluar'],
                    ['value' => 'ke display', 'label' => 'Gudang kecil ke display'],
                    ['value' => 'ke gudang kecil', 'label' => 'Display ke gudang kecil'],
                ],
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function movementQuery(Carbon $start, Carbon $end)
    {
        $warehouseIn = DB::table('warehouse_inbounds as wi')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->leftJoin('suppliers as sup', 'sup.id', '=', 'wi.supplier_id')
            ->whereBetween(DB::raw('COALESCE(wi.received_date, wi.created_at)'), [$start, $end])
            ->selectRaw("
                'Inbound Gudang' AS movement_type,
                'masuk' AS direction,
                NULL::uuid AS store_id,
                NULL::text AS store_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                NULL::uuid AS product_id,
                '-' AS product_code,
                '-' AS product_name,
                COALESCE(wi.quantity_item, 0)::int AS qty,
                COALESCE(wi.invoice_number, wi.delivery_order_number, wi.batch_number, wi.id::text) AS reference,
                COALESCE(wi.received_date, wi.created_at) AS occurred_at,
                COALESCE(wi.inbound_type::text, '-') || COALESCE(' / ' || sup.name, '') AS details
            ");

        $warehouseOut = DB::table('warehouse_outbounds as wo')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereBetween(DB::raw('COALESCE(wo.released_at, wo.shipment_date, wo.created_at)'), [$start, $end])
            ->selectRaw("
                'Outbound Gudang' AS movement_type,
                'keluar' AS direction,
                NULL::uuid AS store_id,
                NULL::text AS store_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                NULL::uuid AS product_id,
                '-' AS product_code,
                '-' AS product_name,
                COALESCE(wo.quantity_item, 0)::int AS qty,
                COALESCE(wo.invoice_number, wo.delivery_order_number, wo.order_number, wo.id::text) AS reference,
                COALESCE(wo.released_at, wo.shipment_date, wo.created_at) AS occurred_at,
                COALESCE(wo.outbound_type::text, '-') AS details
            ");

        $storeIn = DB::table('store_inbounds as si')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'si.warehouse_source_id')
            ->whereBetween(DB::raw('COALESCE(si.received_date, si.created_at)'), [$start, $end])
            ->selectRaw("
                'Inbound Toko' AS movement_type,
                'masuk' AS direction,
                s.id AS store_id,
                s.name AS store_name,
                w.id AS warehouse_id,
                COALESCE(w.name, '-') AS warehouse_name,
                NULL::uuid AS product_id,
                '-' AS product_code,
                '-' AS product_name,
                COALESCE(si.quantity_item, 0)::int AS qty,
                COALESCE(si.invoice_number, si.delivery_order_number, si.id::text) AS reference,
                COALESCE(si.received_date, si.created_at) AS occurred_at,
                COALESCE(si.qc_status::text, '-') AS details
            ");

        $storeOut = DB::table('store_outbounds as so')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->whereBetween(DB::raw('COALESCE(so.shipment_date, so.created_at)'), [$start, $end])
            ->selectRaw("
                'Outbound Toko' AS movement_type,
                'keluar' AS direction,
                s.id AS store_id,
                s.name AS store_name,
                NULL::uuid AS warehouse_id,
                NULL::text AS warehouse_name,
                NULL::uuid AS product_id,
                '-' AS product_code,
                '-' AS product_name,
                COALESCE(so.item_quantity, 0)::int AS qty,
                COALESCE(so.shipping_number, so.id::text) AS reference,
                COALESCE(so.shipment_date, so.created_at) AS occurred_at,
                COALESCE(so.outbound_type::text, '-') AS details
            ");

        $history = DB::query()
            ->fromSub(
                DB::table('item_location_histories as h')
                    ->select(['h.id', 'h.item_id', 'h.location_id', 'h.created_at', 'h.note'])
                    ->selectRaw('LAG(h.location_id) OVER (PARTITION BY h.item_id ORDER BY h.created_at, h.id) AS previous_location_id'),
                'h'
            )
            ->join('locations as current_loc', 'current_loc.id', '=', 'h.location_id')
            ->join('store_locations as sl', 'sl.location_id', '=', 'h.location_id')
            ->join('store_locations as prev_sl', 'prev_sl.location_id', '=', 'h.previous_location_id')
            ->join('stores as s', 's.id', '=', 'sl.store_id')
            ->leftJoin('locations as previous_loc', 'previous_loc.id', '=', 'h.previous_location_id')
            ->leftJoin('items as i', 'i.id', '=', 'h.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->whereBetween('h.created_at', [$start, $end])
            ->whereNotNull('h.previous_location_id')
            ->whereRaw('h.previous_location_id <> h.location_id')
            ->where(function ($q) {
                $q->whereRaw("current_loc.type::text = 'store_display'")
                    ->orWhereRaw("previous_loc.type::text = 'store_display'");
            });

        $displayTransfer = $history->selectRaw("
            'Pindah Stok Toko' AS movement_type,
            CASE
                WHEN current_loc.type::text = 'store_display' THEN 'ke display'
                WHEN previous_loc.type::text = 'store_display' THEN 'ke gudang kecil'
                ELSE 'ke gudang kecil'
            END AS direction,
            s.id AS store_id,
            s.name AS store_name,
            NULL::uuid AS warehouse_id,
            NULL::text AS warehouse_name,
            i.product_id,
            COALESCE(p.code, '-') AS product_code,
            COALESCE(p.name, '-') AS product_name,
            1::int AS qty,
            COALESCE(previous_loc.name, '-') || ' -> ' || current_loc.name AS reference,
            h.created_at AS occurred_at,
            COALESCE(previous_loc.type::text, '-') || ' ke ' || current_loc.type::text AS details
        ");

        return $warehouseIn
            ->unionAll($warehouseOut)
            ->unionAll($storeIn)
            ->unionAll($storeOut)
            ->unionAll($displayTransfer);
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
            'movement_type' => trim((string) $request->query('movement_type', '')),
            'direction' => trim((string) $request->query('direction', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
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
