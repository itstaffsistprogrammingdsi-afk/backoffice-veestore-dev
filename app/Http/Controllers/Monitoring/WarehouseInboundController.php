<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\WarehouseInboundExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseInboundController extends Controller
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const INBOUND_TYPE_LABELS = [
        'supplier' => 'Supplier',
        'store_return' => 'Retur Store',
        'warehouse_transfer' => 'Transfer Warehouse',
    ];

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->getFilteredRows($filters);
        $summary = $this->getSummary($filters);

        $inboundIds = $rows->getCollection()->pluck('id')->filter()->values()->all();
        $detailMap = $this->getDetailSummaryByInboundIds($inboundIds);

        $rows->getCollection()->transform(function ($row) use ($detailMap) {
            $details = collect($detailMap->get($row->id, []));

            $row->product_details = $details->map(function ($d) {
                return [
                    'detail_id' => $d->detail_id,
                    'product_id' => $d->product_id,
                    'product_code' => $d->product_code,
                    'product_name' => $d->product_name,
                    'quantity' => (int) $d->quantity,
                    'expired_date' => $d->expired_date,
                    'price_idr' => $d->price_idr !== null ? (float) $d->price_idr : 0,
                    'exchange_currency' => $d->exchange_currency,
                    'exchange_rate' => $d->exchange_rate !== null ? (float) $d->exchange_rate : null,
                    'exchange_amount' => $d->exchange_amount !== null ? (float) $d->exchange_amount : null,
                    'note' => $d->note,
                    'item_count' => (int) $d->item_count,
                    'qty_good' => (int) $d->qty_good,
                    'qty_bad' => (int) $d->qty_bad,
                    'qty_other' => (int) $d->qty_other,
                    'last_qc_at' => $d->last_qc_at,
                    'condition_summary' => $d->condition_summary,
                ];
            })->values();

            $row->product_summary = $details->map(function ($d) {
                return ($d->product_name ?? '-').' ('.(int) $d->quantity.')';
            })->take(3)->implode(', ');

            $row->product_summary_more = max(0, $details->count() - 3);
            $row->event_date = $row->received_date;
            $row->total_qty = (int) $row->total_qty;
            $row->total_sku = (int) $row->total_sku;
            $row->detail_value = (float) ($row->detail_value ?? 0);

            return $row;
        });

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $suppliers = DB::table('suppliers')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $storedInboundTypes = DB::table('warehouse_inbounds')
            ->select('inbound_type')
            ->whereNotNull('inbound_type')
            ->distinct()
            ->orderBy('inbound_type')
            ->pluck('inbound_type');

        $inboundTypes = collect(array_keys(self::INBOUND_TYPE_LABELS))
            ->merge($storedInboundTypes)
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Warehouse Inbound',
            'description' => 'Laporan monitoring.',
            'data' => [
                'rows' => $rows,
                'rowItems' => $rows->items(),
                'summary' => $summary,
                'warehouses' => $warehouses,
                'suppliers' => $suppliers,
                'inboundTypes' => $inboundTypes,
                'inboundTypeLabels' => self::INBOUND_TYPE_LABELS,
                'filters' => $filters,
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.warehouse-inbound.export'), 'operations' => route('operations.inbound.index')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->getFilteredRowsForExport($filters);

        $inboundIds = $rows->pluck('id')->filter()->values()->all();
        $detailMap = $this->getDetailSummaryByInboundIds($inboundIds);

        $warehouseName = 'Semua Warehouse';
        if ($filters['warehouse_id'] !== '') {
            $warehouseName = DB::table('warehouses')
                ->where('id', $filters['warehouse_id'])
                ->value('name') ?? 'Semua Warehouse';
        }

        $sourceWarehouseName = 'Semua Warehouse Asal';
        if ($filters['source_warehouse_id'] !== '') {
            $sourceWarehouseName = DB::table('warehouses')
                ->where('id', $filters['source_warehouse_id'])
                ->value('name') ?? 'Semua Warehouse Asal';
        }

        $supplierName = 'Semua Supplier';
        if ($filters['supplier_id'] !== '') {
            $supplierName = DB::table('suppliers')
                ->where('id', $filters['supplier_id'])
                ->value('name') ?? 'Semua Supplier';
        }

        $exportFilters = [
            'warehouse_name' => $warehouseName,
            'source_warehouse_name' => $sourceWarehouseName,
            'supplier_name' => $supplierName,
            'inbound_type' => $filters['inbound_type']
                ? (self::INBOUND_TYPE_LABELS[$filters['inbound_type']] ?? $filters['inbound_type'])
                : 'Semua Tipe',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'q' => $filters['q'],
        ];

        $filename = 'laporan-warehouse-inbound-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new WarehouseInboundExport($rows, $detailMap, $exportFilters),
            $filename
        );
    }

    private function getFilters(Request $request): array
    {
        return [
            'warehouse_id' => trim((string) $request->input('warehouse_id', '')),
            'source_warehouse_id' => trim((string) $request->input('source_warehouse_id', '')),
            'supplier_id' => trim((string) $request->input('supplier_id', '')),
            'inbound_type' => trim((string) $request->input('inbound_type', '')),
            'date_from' => $this->normalizeDate($request->input('date_from')),
            'date_to' => $this->normalizeDate($request->input('date_to')),
            'q' => trim((string) $request->input('q', '')),
            'per_page' => $this->normalizePerPage($request->input('per_page', 10)),
        ];
    }

    private function baseQuery(array $filters)
    {
        $warehouseId = $filters['warehouse_id'] ?? '';
        $sourceWarehouseId = $filters['source_warehouse_id'] ?? '';
        $supplierId = $filters['supplier_id'] ?? '';
        $inboundType = $filters['inbound_type'] ?? '';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $q = $filters['q'] ?? '';

        $hasUsers = Schema::hasTable('users');

        $valueSummary = DB::table('warehouse_inbound_details as wid_value')
            ->selectRaw('
                wid_value.warehouse_inbound_id,
                COALESCE(SUM(wid_value.quantity * COALESCE(wid_value.price_idr, 0)), 0)::numeric as detail_value
            ')
            ->groupBy('wid_value.warehouse_inbound_id');

        $query = DB::table('warehouse_inbounds as wi')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->leftJoin('warehouses as sw', 'sw.id', '=', 'wi.source_warehouse_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->leftJoin('store_returns as sr', 'sr.id', '=', 'wi.store_return_id')
            ->leftJoin('stores as return_store', 'return_store.id', '=', 'sr.store_id')
            ->leftJoin('warehouse_inbound_details as wid', 'wid.warehouse_inbound_id', '=', 'wi.id')
            ->leftJoin('products as p', 'p.id', '=', 'wid.product_id')
            ->leftJoinSub($valueSummary, 'value_summary', 'value_summary.warehouse_inbound_id', '=', 'wi.id')
            ->when($hasUsers, fn ($q2) => $q2->leftJoin('users as u_received', 'u_received.id', '=', 'wi.received_by'))
            ->when($hasUsers, fn ($q2) => $q2->leftJoin('users as u_created', 'u_created.id', '=', 'wi.created_by'))
            ->whereNull('w.deleted_at')
            ->whereNotNull('wi.received_date')
            ->when($warehouseId !== '', fn ($q2) => $q2->where('wi.warehouse_id', $warehouseId))
            ->when($sourceWarehouseId !== '', fn ($q2) => $q2->where('wi.source_warehouse_id', $sourceWarehouseId))
            ->when($supplierId !== '', fn ($q2) => $q2->where('wi.supplier_id', $supplierId))
            ->when($inboundType !== '', fn ($q2) => $q2->where('wi.inbound_type', $inboundType))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($wq) use ($q) {
                    $wq->where('wi.invoice_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wi.delivery_order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wi.batch_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wi.outbound_reference_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wi.inbound_type', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%")
                        ->orWhere('sw.name', 'ILIKE', "%{$q}%")
                        ->orWhere('sw.code', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('sr.return_number', 'ILIKE', "%{$q}%")
                        ->orWhere('return_store.name', 'ILIKE', "%{$q}%")
                        ->orWhere('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%");
                });
            });

        $this->applyReceivedDateFilter($query, $dateFrom, $dateTo);

        return $query
            ->selectRaw('
                wi.id,
                wi.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                wi.source_warehouse_id,
                COALESCE(sw.name, \'-\') as source_warehouse_name,
                COALESCE(sw.code, \'-\') as source_warehouse_code,
                wi.supplier_id,
                COALESCE(s.name, \'-\') as supplier_name,
                wi.invoice_number,
                wi.delivery_order_number,
                wi.quantity_item,
                wi.grand_total,
                COALESCE(value_summary.detail_value, 0)::numeric as detail_value,
                wi.received_date,
                wi.created_at,
                wi.inbound_type,
                wi.store_return_id,
                COALESCE(sr.return_number, \'-\') as store_return_number,
                COALESCE(return_store.name, \'-\') as return_store_name,
                wi.batch_number,
                wi.outbound_reference_number,
                CASE
                    WHEN wi.inbound_type = \'supplier\' THEN COALESCE(s.name, \'-\')
                    WHEN wi.inbound_type = \'store_return\' THEN COALESCE(return_store.name, \'-\')
                    WHEN wi.inbound_type = \'warehouse_transfer\' THEN COALESCE(sw.name, \'-\')
                    ELSE COALESCE(s.name, return_store.name, sw.name, \'-\')
                END as origin_name,
                CASE
                    WHEN wi.inbound_type = \'supplier\' THEN COALESCE(wi.invoice_number, wi.delivery_order_number, \'-\')
                    WHEN wi.inbound_type = \'store_return\' THEN COALESCE(sr.return_number, \'-\')
                    WHEN wi.inbound_type = \'warehouse_transfer\' THEN COALESCE(wi.outbound_reference_number, \'-\')
                    ELSE COALESCE(wi.outbound_reference_number, sr.return_number, wi.invoice_number, wi.delivery_order_number, \'-\')
                END as origin_reference
            ')
            ->when(
                $hasUsers,
                fn ($q2) => $q2->selectRaw("COALESCE(u_received.name, '') as received_by_name"),
                fn ($q2) => $q2->selectRaw("'' as received_by_name")
            )
            ->when(
                $hasUsers,
                fn ($q2) => $q2->selectRaw("COALESCE(u_created.name, '') as created_by_name"),
                fn ($q2) => $q2->selectRaw("'' as created_by_name")
            )
            ->selectRaw('COUNT(DISTINCT wid.product_id)::int as total_sku')
            ->selectRaw('COALESCE(SUM(wid.quantity), 0)::int as total_qty')
            ->groupBy(
                'wi.id',
                'wi.warehouse_id',
                'w.name',
                'w.code',
                'wi.source_warehouse_id',
                'sw.name',
                'sw.code',
                'wi.supplier_id',
                's.name',
                'sr.return_number',
                'return_store.name',
                'wi.invoice_number',
                'wi.delivery_order_number',
                'wi.quantity_item',
                'wi.grand_total',
                'value_summary.detail_value',
                'wi.received_date',
                'wi.created_at',
                'wi.inbound_type',
                'wi.store_return_id',
                'wi.batch_number',
                'wi.outbound_reference_number',
                ...($hasUsers ? ['u_received.name', 'u_created.name'] : [])
            )
            ->orderByDesc('wi.received_date')
            ->orderByDesc('total_qty');
    }

    private function applyReceivedDateFilter($query, ?string $dateFrom, ?string $dateTo): void
    {
        if (! $dateFrom && ! $dateTo) {
            return;
        }

        if ($dateFrom && ! $dateTo) {
            $start = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->startOfDay()->utc();
            $end = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->endOfDay()->utc();
            $query->whereBetween('wi.received_date', [$start, $end]);

            return;
        }

        if (! $dateFrom && $dateTo) {
            $start = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->startOfDay()->utc();
            $end = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->endOfDay()->utc();
            $query->whereBetween('wi.received_date', [$start, $end]);

            return;
        }

        if ($dateFrom && $dateTo) {
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            $start = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->startOfDay()->utc();
            $end = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->endOfDay()->utc();
            $query->whereBetween('wi.received_date', [$start, $end]);
        }
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = trim($date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function getFilteredRows(array $filters)
    {
        return $this->baseQuery($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    private function getSummary(array $filters): object
    {
        $query = $this->baseQuery($filters)->reorder();

        $summary = DB::query()
            ->fromSub($query, 'inbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_inbounds,
                COALESCE(SUM(inbound_rows.total_qty), 0)::int as total_qty,
                COALESCE(SUM(inbound_rows.detail_value), 0)::numeric as total_value,
                COALESCE(SUM(inbound_rows.grand_total), 0)::numeric as total_header_value,
                COUNT(DISTINCT inbound_rows.warehouse_id)::int as total_warehouses,
                COUNT(DISTINCT NULLIF(inbound_rows.origin_name, \'-\'))::int as total_origins
            ')
            ->first();

        $summary->total_value = (float) ($summary->total_value ?? 0);
        $summary->total_header_value = (float) ($summary->total_header_value ?? 0);

        $documentIds = DB::query()
            ->fromSub($this->baseQuery($filters)->reorder(), 'inbound_rows')
            ->pluck('id')
            ->all();

        $q = (string) ($filters['q'] ?? '');

        $skuQuery = DB::table('warehouse_inbound_details as wid')
            ->whereIn('wid.warehouse_inbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $skuQuery->join('products as p', 'p.id', '=', 'wid.product_id')
                ->where(function ($sub) use ($q) {
                    $sub->where('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%");
                });
        }

        $summary->total_sku = empty($documentIds)
            ? 0
            : (int) $skuQuery->distinct('wid.product_id')->count('wid.product_id');

        return $summary;
    }

    private function qMatchesProduct(string $q): bool
    {
        if ($q === '') {
            return false;
        }

        return DB::table('products')
            ->where(function ($sub) use ($q) {
                $sub->where('code', 'ILIKE', "%{$q}%")
                    ->orWhere('name', 'ILIKE', "%{$q}%");
            })
            ->exists();
    }

    private function getFilteredRowsForExport(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->get()
            ->map(function ($r) {
                $r->event_date = $r->received_date;
                $r->total_qty = (int) $r->total_qty;
                $r->total_sku = (int) $r->total_sku;
                $r->detail_value = (float) ($r->detail_value ?? 0);

                return $r;
            });
    }

    private function getDetailSummaryByInboundIds(array $inboundIds): Collection
    {
        if (empty($inboundIds)) {
            return collect();
        }

        $latestQcSub = DB::table('warehouse_qc as wq')
            ->selectRaw('
                DISTINCT ON (wq.item_id)
                wq.item_id,
                wq.item_condition_id,
                COALESCE(wq.performed_at, wq.created_at) as qc_at
            ')
            ->orderBy('wq.item_id')
            ->orderByRaw('COALESCE(wq.performed_at, wq.created_at) DESC')
            ->orderByDesc('wq.id');

        $itemConditionSummary = DB::table('warehouse_inbound_details as wid')
            ->leftJoin('items as i', 'i.warehouse_inbound_detail_id', '=', 'wid.id')
            ->leftJoinSub($latestQcSub, 'lqc', function ($join) {
                $join->on('lqc.item_id', '=', 'i.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'lqc.item_condition_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->selectRaw('
                wid.id as detail_id,
                COUNT(i.id)::int as item_count,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'good\' THEN 1 ELSE 0 END)::int as qty_good,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'bad\' THEN 1 ELSE 0 END)::int as qty_bad,
                SUM(CASE WHEN i.id IS NOT NULL AND LOWER(COALESCE(ic.name, \'\')) NOT IN (\'good\', \'bad\') THEN 1 ELSE 0 END)::int as qty_other,
                MAX(lqc.qc_at) as last_qc_at
            ')
            ->groupBy('wid.id')
            ->get()
            ->keyBy('detail_id');

        $conditionBreakdown = DB::table('warehouse_inbound_details as wid')
            ->leftJoin('items as i', 'i.warehouse_inbound_detail_id', '=', 'wid.id')
            ->leftJoinSub($latestQcSub, 'lqc', function ($join) {
                $join->on('lqc.item_id', '=', 'i.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'lqc.item_condition_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->whereNotNull('i.id')
            ->selectRaw('
                wid.id as detail_id,
                COALESCE(NULLIF(TRIM(ic.name), \'\'), \'Tanpa Kondisi\') as condition_name,
                COUNT(i.id)::int as qty
            ')
            ->groupBy('wid.id', DB::raw('COALESCE(NULLIF(TRIM(ic.name), \'\'), \'Tanpa Kondisi\')'))
            ->orderBy('wid.id')
            ->orderByRaw('COUNT(i.id) DESC')
            ->orderByRaw('COALESCE(NULLIF(TRIM(ic.name), \'\'), \'Tanpa Kondisi\') ASC')
            ->get()
            ->groupBy('detail_id');

        return DB::table('warehouse_inbound_details as wid')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->selectRaw('
                wid.id as detail_id,
                wid.warehouse_inbound_id,
                wid.product_id,
                p.code as product_code,
                p.name as product_name,
                wid.quantity,
                wid.expired_date,
                wid.note,
                wid.exchange_currency,
                wid.exchange_rate,
                wid.exchange_amount,
                wid.price_idr
            ')
            ->orderBy('p.name')
            ->get()
            ->map(function ($row) use ($itemConditionSummary, $conditionBreakdown) {
                $cond = $itemConditionSummary->get($row->detail_id);
                $breakdowns = collect($conditionBreakdown->get($row->detail_id, []));

                $row->item_count = (int) ($cond->item_count ?? 0);
                $row->qty_good = (int) ($cond->qty_good ?? 0);
                $row->qty_bad = (int) ($cond->qty_bad ?? 0);
                $row->qty_other = (int) ($cond->qty_other ?? 0);
                $row->last_qc_at = $cond->last_qc_at ?? null;

                $row->condition_summary = $breakdowns->isNotEmpty()
                    ? $breakdowns->map(function ($b) {
                        return ($b->condition_name ?? 'Tanpa Kondisi').': '.(int) $b->qty;
                    })->implode(', ')
                    : '-';

                return $row;
            })
            ->groupBy('warehouse_inbound_id');
    }

    private function normalizePerPage($value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [10, 25, 50, 100, 200], true) ? $perPage : 10;
    }
}
