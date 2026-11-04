<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\WarehouseOutboundExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseOutboundController extends Controller
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const OUTBOUND_TYPE_LABELS = [
        'ecommerce' => 'Ecommerce',
        'destroy' => 'Destroy',
        'transfer' => 'Transfer',
        'return_to_supplier' => 'Retur ke Supplier',
        'store' => 'Store',
    ];

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        $rows = $this->getFilteredRows($filters);
        $summary = $this->getSummary($filters);

        $this->attachDetailData($rows->getCollection());

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $couriers = Schema::hasTable('couriers')
            ? DB::table('couriers')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $storedOutboundTypes = DB::table('warehouse_outbounds')
            ->select('outbound_type')
            ->whereNotNull('outbound_type')
            ->distinct()
            ->orderBy('outbound_type')
            ->pluck('outbound_type');

        $outboundTypes = collect(array_keys(self::OUTBOUND_TYPE_LABELS))
            ->merge($storedOutboundTypes)
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Warehouse Outbound',
            'description' => 'Laporan monitoring.',
            'data' => [
                'rows' => $rows,
                'rowItems' => $rows->items(),
                'summary' => $summary,
                'warehouses' => $warehouses,
                'stores' => $stores,
                'couriers' => $couriers,
                'outboundTypes' => $outboundTypes,
                'outboundTypeLabels' => self::OUTBOUND_TYPE_LABELS,
                'filters' => $filters,
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.warehouse-outbound.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);
        $rows = $this->getFilteredRowsForExport($filters);
        $detailMap = $this->getDetailSummaryByOutboundIds($rows->pluck('id')->all());

        $warehouseName = $filters['warehouse_id'] === ''
            ? 'Semua Warehouse'
            : (DB::table('warehouses')->where('id', $filters['warehouse_id'])->value('name') ?? 'Semua Warehouse');

        $courierName = $filters['courier_id'] === '' || ! Schema::hasTable('couriers')
            ? 'Semua Kurir'
            : (DB::table('couriers')->where('id', $filters['courier_id'])->value('name') ?? 'Semua Kurir');

        $storeName = $filters['store_id'] === ''
            ? 'Semua Cabang'
            : (DB::table('stores')->where('id', $filters['store_id'])->value('name') ?? 'Semua Cabang');

        $exportFilters = [
            'warehouse_name' => $warehouseName,
            'store_name' => $storeName,
            'courier_name' => $courierName,
            'reconciliation_status' => $this->reconciliationStatusLabel($filters['reconciliation_status']),
            'outbound_type' => $filters['outbound_type']
                ? (self::OUTBOUND_TYPE_LABELS[$filters['outbound_type']] ?? $filters['outbound_type'])
                : 'Semua Tipe',
            'release_status' => $this->releaseStatusLabel($filters['release_status']),
            'qc_status' => $this->qcStatusLabel($filters['qc_status']),
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'q' => $filters['q'],
        ];

        return Excel::download(
            new WarehouseOutboundExport($rows, $detailMap, $exportFilters),
            'laporan-warehouse-outbound-'.now(self::APP_TIMEZONE)->format('Ymd_His').'.xlsx'
        );
    }

    private function getFilters(Request $request): array
    {
        $storeId = trim((string) $request->input('store_id', ''));
        $reconciliationStatus = trim((string) $request->input('reconciliation_status', ''));

        return [
            'warehouse_id' => trim((string) $request->input('warehouse_id', '')),
            'store_id' => $storeId,
            'courier_id' => trim((string) $request->input('courier_id', '')),
            'outbound_type' => trim((string) $request->input('outbound_type', '')),
            'release_status' => trim((string) $request->input('release_status', '')),
            'qc_status' => trim((string) $request->input('qc_status', '')),
            'reconciliation_status' => in_array($reconciliationStatus, ['not_recorded', 'partial', 'recorded'], true)
                ? $reconciliationStatus
                : '',
            'date_from' => $this->normalizeDate($request->input('date_from')),
            'date_to' => $this->normalizeDate($request->input('date_to')),
            'q' => trim((string) $request->input('q', '')),
            'per_page' => $this->normalizePerPage($request->input('per_page', 10)),
        ];
    }

    private function baseQuery(array $filters)
    {
        $hasUsers = Schema::hasTable('users');
        $hasCouriers = Schema::hasTable('couriers');

        $storeInbound = DB::table('store_inbounds as si')
            ->leftJoin('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->whereNotNull('si.order_id')
            ->selectRaw('
                si.order_id,
                COUNT(sid.id) FILTER (WHERE si.received_date IS NOT NULL)::int as received_qty,
                MIN(si.received_date) as received_at,
                MIN(si.id::text) FILTER (WHERE si.received_date IS NOT NULL) as store_inbound_id
            ')
            ->groupBy('si.order_id');

        $query = DB::table('warehouse_outbounds as wo')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoin('warehouse_outbound_details as wod', 'wod.warehouse_outbound_id', '=', 'wo.id')
            ->leftJoin('items as i', 'i.id', '=', 'wod.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_orders as so', 'so.id', '=', 'wo.order_id')
            ->leftJoin('stores as dest_store', 'dest_store.id', '=', 'so.store_id')
            ->leftJoinSub($storeInbound, 'sinb', 'sinb.order_id', '=', 'so.id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_outbound', 'u_outbound.id', '=', 'wo.user_id'))
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_qc', 'u_qc.id', '=', 'wo.qc_passed_by'))
            ->when($hasCouriers, fn ($query) => $query->leftJoin('couriers as c', 'c.id', '=', 'wo.courier_id'))
            ->whereNull('w.deleted_at')
            ->when($filters['warehouse_id'] !== '', fn ($query) => $query->where('wo.warehouse_id', $filters['warehouse_id']))
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('so.store_id', $filters['store_id']))
            ->when($filters['courier_id'] !== '', fn ($query) => $query->where('wo.courier_id', $filters['courier_id']))
            ->when($filters['outbound_type'] !== '', fn ($query) => $query->where('wo.outbound_type', $filters['outbound_type']))
            ->when($filters['release_status'] === 'released', fn ($query) => $query->whereNotNull('wo.released_at'))
            ->when($filters['release_status'] === 'unreleased', fn ($query) => $query->whereNull('wo.released_at'))
            ->when($filters['qc_status'] === 'passed', fn ($query) => $query->whereNotNull('wo.qc_passed_at'))
            ->when($filters['qc_status'] === 'pending', fn ($query) => $query->whereNull('wo.qc_passed_at'))
            ->when($filters['reconciliation_status'] !== '', fn ($query) => $query->whereNotNull('wo.released_at'))
            ->when($filters['reconciliation_status'] === 'not_recorded', fn ($query) => $query
                ->whereNotNull('so.id')
                ->whereRaw('COALESCE(sinb.received_qty, 0) = 0'))
            ->when($filters['reconciliation_status'] === 'partial', fn ($query) => $query
                ->whereNotNull('so.id')
                ->whereRaw('COALESCE(sinb.received_qty, 0) > 0')
                ->whereRaw('COALESCE(sinb.received_qty, 0) < (SELECT COUNT(*)::int FROM warehouse_outbound_details wod_status WHERE wod_status.warehouse_outbound_id = wo.id)'))
            ->when($filters['reconciliation_status'] === 'recorded', fn ($query) => $query
                ->whereNotNull('so.id')
                ->whereRaw('COALESCE(sinb.received_qty, 0) >= (SELECT COUNT(*)::int FROM warehouse_outbound_details wod_status WHERE wod_status.warehouse_outbound_id = wo.id)')
                ->whereRaw('(SELECT COUNT(*)::int FROM warehouse_outbound_details wod_status WHERE wod_status.warehouse_outbound_id = wo.id) > 0'))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($keyword) {
                    $search->where('wo.invoice_number', 'ILIKE', $keyword)
                        ->orWhere('wo.delivery_order_number', 'ILIKE', $keyword)
                        ->orWhere('wo.order_number', 'ILIKE', $keyword)
                        ->orWhere('wo.shipping_number', 'ILIKE', $keyword)
                        ->orWhere('wo.outbound_type', 'ILIKE', $keyword)
                        ->orWhereRaw('wo.order_ref::text ILIKE ?', [$keyword])
                        ->orWhere('w.name', 'ILIKE', $keyword)
                        ->orWhere('w.code', 'ILIKE', $keyword)
                        ->orWhere('dest_store.name', 'ILIKE', $keyword)
                        ->orWhere('dest_store.code', 'ILIKE', $keyword)
                        ->orWhere('p.code', 'ILIKE', $keyword)
                        ->orWhere('p.name', 'ILIKE', $keyword);
                });
            });

        $this->applyEventDateFilter($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->selectRaw('
                wo.id,
                wo.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                wo.invoice_number,
                wo.delivery_order_number,
                wo.quantity_item,
                wo.courier_id,
                wo.shipment_date,
                wo.created_at,
                wo.order_id,
                wo.order_ref,
                wo.order_number,
                wo.outbound_type,
                wo.released_at,
                wo.shipping_number,
                wo.qc_passed_at,
                wo.qc_passed_by,
                so.store_id as destination_store_id,
                dest_store.name as destination_store_name,
                dest_store.code as destination_store_code,
                COALESCE(sinb.received_qty, 0)::int as store_received_qty,
                sinb.received_at as store_received_at,
                sinb.store_inbound_id
            ')
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u_outbound.name, '') as outbound_by_name"), fn ($query) => $query->selectRaw("'' as outbound_by_name"))
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u_qc.name, '') as qc_passed_by_name"), fn ($query) => $query->selectRaw("'' as qc_passed_by_name"))
            ->when($hasCouriers, fn ($query) => $query->selectRaw("COALESCE(c.name, '') as courier_name"), fn ($query) => $query->selectRaw("'' as courier_name"))
            ->selectRaw('COUNT(DISTINCT wod.id)::int as total_items')
            ->selectRaw('COUNT(DISTINCT i.product_id)::int as total_sku')
            ->groupBy(
                'wo.id', 'wo.warehouse_id', 'w.name', 'w.code', 'wo.invoice_number',
                'wo.delivery_order_number', 'wo.quantity_item', 'wo.courier_id', 'wo.shipment_date',
                'wo.created_at', 'wo.order_id', 'wo.order_ref', 'wo.order_number', 'wo.outbound_type',
                'wo.released_at', 'wo.shipping_number', 'wo.qc_passed_at', 'wo.qc_passed_by',
                'so.store_id', 'dest_store.name', 'dest_store.code', 'sinb.received_qty',
                'sinb.received_at', 'sinb.store_inbound_id',
                ...($hasUsers ? ['u_outbound.name', 'u_qc.name'] : []),
                ...($hasCouriers ? ['c.name'] : [])
            )
            ->orderByRaw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at) DESC')
            ->orderByDesc('total_items');
    }

    private function getFilteredRows(array $filters)
    {
        return $this->baseQuery($filters)->paginate($filters['per_page'])->withQueryString();
    }

    private function getFilteredRowsForExport(array $filters): Collection
    {
        $rows = $this->baseQuery($filters)->get();
        $this->attachDetailData($rows);

        return $rows;
    }

    private function getSummary(array $filters): object
    {
        $query = $this->baseQuery($filters)->reorder();

        return DB::query()->fromSub($query, 'outbound_rows')->selectRaw('
            COUNT(*)::int as total_outbounds,
            COALESCE(SUM(outbound_rows.total_sku), 0)::int as total_sku,
            COALESCE(SUM(outbound_rows.total_items), 0)::int as total_items,
            COUNT(*) FILTER (WHERE outbound_rows.released_at IS NOT NULL)::int as total_released,
            COUNT(*) FILTER (WHERE outbound_rows.qc_passed_at IS NOT NULL)::int as total_qc_passed,
            COUNT(*) FILTER (WHERE NULLIF(TRIM(COALESCE(outbound_rows.shipping_number, \'\')), \'\') IS NULL)::int as total_without_shipping,
            COUNT(*) FILTER (WHERE outbound_rows.destination_store_id IS NOT NULL AND outbound_rows.released_at IS NOT NULL)::int as total_store_transfers,
            COUNT(*) FILTER (WHERE outbound_rows.destination_store_id IS NOT NULL AND outbound_rows.released_at IS NOT NULL AND outbound_rows.store_received_qty <= 0)::int as store_not_recorded,
            COUNT(*) FILTER (WHERE outbound_rows.destination_store_id IS NOT NULL AND outbound_rows.released_at IS NOT NULL AND outbound_rows.store_received_qty > 0 AND outbound_rows.store_received_qty < outbound_rows.total_items)::int as store_partial,
            COUNT(*) FILTER (WHERE outbound_rows.destination_store_id IS NOT NULL AND outbound_rows.released_at IS NOT NULL AND outbound_rows.total_items > 0 AND outbound_rows.store_received_qty >= outbound_rows.total_items)::int as store_recorded
        ')->first();
    }

    private function attachDetailData(Collection $rows): void
    {
        $detailMap = $this->getDetailSummaryByOutboundIds($rows->pluck('id')->filter()->values()->all());

        $rows->transform(function ($row) use ($detailMap) {
            $details = collect($detailMap->get($row->id, []));

            $row->product_details = $details->map(function ($detail) {
                return [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'qty' => (int) $detail->qty,
                    'qty_good' => (int) $detail->qty_good,
                    'qty_bad' => (int) $detail->qty_bad,
                    'qty_other' => (int) $detail->qty_other,
                    'condition_summary' => $detail->condition_summary,
                ];
            })->values();
            $row->product_summary = $details->map(fn ($detail) => ($detail->product_name ?? '-').' ('.(int) $detail->qty.')')->take(3)->implode(', ');
            $row->product_summary_more = max(0, $details->count() - 3);
            $row->event_date = $row->shipment_date ?? $row->released_at ?? $row->created_at;
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->release_status = $row->released_at ? 'released' : 'unreleased';
            $row->qc_status = $row->qc_passed_at ? 'passed' : 'pending';
            $row->outbound_type_label = self::OUTBOUND_TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: '-');
            $row->store_received_qty = (int) ($row->store_received_qty ?? 0);
            $row->store_variance_qty = $row->destination_store_id
                ? max(0, $row->total_items - $row->store_received_qty)
                : 0;

            if ($row->destination_store_id && $row->released_at) {
                if ($row->store_received_qty <= 0) {
                    $row->store_recording_status = 'not_recorded';
                    $row->store_recording_status_label = 'Belum tercatat';
                } elseif ($row->store_received_qty >= $row->total_items) {
                    $row->store_recording_status = 'recorded';
                    $row->store_recording_status_label = 'Sudah tercatat';
                } else {
                    $row->store_recording_status = 'partial';
                    $row->store_recording_status_label = 'Tercatat sebagian';
                }
            } else {
                $row->store_recording_status = null;
                $row->store_recording_status_label = '-';
            }

            return $row;
        });
    }

    private function getDetailSummaryByOutboundIds(array $outboundIds): Collection
    {
        if (empty($outboundIds)) {
            return collect();
        }

        $conditionBreakdown = DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('wod.warehouse_outbound_id', $outboundIds)
            ->selectRaw("wod.warehouse_outbound_id, i.product_id, COALESCE(NULLIF(TRIM(ic.name), ''), 'Tanpa Kondisi') as condition_name, COUNT(wod.id)::int as qty")
            ->groupBy('wod.warehouse_outbound_id', 'i.product_id', DB::raw("COALESCE(NULLIF(TRIM(ic.name), ''), 'Tanpa Kondisi')"))
            ->orderBy('wod.warehouse_outbound_id')
            ->orderBy('i.product_id')
            ->orderByRaw('COUNT(wod.id) DESC')
            ->get()
            ->groupBy(fn ($row) => $row->warehouse_outbound_id.'|'.$row->product_id);

        return DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('wod.warehouse_outbound_id', $outboundIds)
            ->selectRaw(" 
                wod.warehouse_outbound_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(wod.id)::int as qty,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, '')) = 'good' THEN 1 ELSE 0 END)::int as qty_good,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, '')) = 'bad' THEN 1 ELSE 0 END)::int as qty_bad,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, '')) NOT IN ('good', 'bad') THEN 1 ELSE 0 END)::int as qty_other
            ")
            ->groupBy('wod.warehouse_outbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(function ($row) use ($conditionBreakdown) {
                $breakdown = collect($conditionBreakdown->get($row->warehouse_outbound_id.'|'.$row->product_id, []));
                $row->condition_summary = $breakdown->isEmpty()
                    ? '-'
                    : $breakdown->map(fn ($item) => $item->condition_name.': '.(int) $item->qty)->implode(', ');

                return $row;
            })
            ->groupBy('warehouse_outbound_id');
    }

    private function applyEventDateFilter($query, ?string $dateFrom, ?string $dateTo): void
    {
        if (! $dateFrom && ! $dateTo) {
            return;
        }

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $dateFrom ??= $dateTo;
        $dateTo ??= $dateFrom;
        $start = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->startOfDay()->utc();
        $end = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->endOfDay()->utc();

        $query->whereBetween(DB::raw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at)'), [$start, $end]);
    }

    private function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);

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

    private function normalizePerPage($value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [10, 25, 50, 100, 200], true) ? $perPage : 10;
    }

    private function releaseStatusLabel(string $status): string
    {
        return ['released' => 'Sudah release', 'unreleased' => 'Belum release'][$status] ?? 'Semua Status';
    }

    private function qcStatusLabel(string $status): string
    {
        return ['passed' => 'QC lolos', 'pending' => 'QC belum lolos'][$status] ?? 'Semua Status QC';
    }

    private function reconciliationStatusLabel(string $status): string
    {
        return [
            'not_recorded' => 'Belum tercatat di stok toko',
            'partial' => 'Tercatat sebagian',
            'recorded' => 'Sudah tercatat',
        ][$status] ?? 'Semua Status Stok Toko';
    }
}
