<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\WarehouseOutboundExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        return view('content.monitoring.warehouse-outbound.index', [
            'rows' => $rows,
            'rowItems' => $rows->items(),
            'summary' => $summary,
            'warehouses' => $warehouses,
            'couriers' => $couriers,
            'outboundTypes' => $outboundTypes,
            'outboundTypeLabels' => self::OUTBOUND_TYPE_LABELS,
            'filters' => $filters,
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

        $exportFilters = [
            'warehouse_name' => $warehouseName,
            'courier_name' => $courierName,
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
        return [
            'warehouse_id' => trim((string) $request->input('warehouse_id', '')),
            'courier_id' => trim((string) $request->input('courier_id', '')),
            'outbound_type' => trim((string) $request->input('outbound_type', '')),
            'release_status' => trim((string) $request->input('release_status', '')),
            'qc_status' => trim((string) $request->input('qc_status', '')),
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

        $query = DB::table('warehouse_outbounds as wo')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoin('warehouse_outbound_details as wod', 'wod.warehouse_outbound_id', '=', 'wo.id')
            ->leftJoin('items as i', 'i.id', '=', 'wod.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_outbound', 'u_outbound.id', '=', 'wo.user_id'))
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_qc', 'u_qc.id', '=', 'wo.qc_passed_by'))
            ->when($hasCouriers, fn ($query) => $query->leftJoin('couriers as c', 'c.id', '=', 'wo.courier_id'))
            ->whereNull('w.deleted_at')
            ->when($filters['warehouse_id'] !== '', fn ($query) => $query->where('wo.warehouse_id', $filters['warehouse_id']))
            ->when($filters['courier_id'] !== '', fn ($query) => $query->where('wo.courier_id', $filters['courier_id']))
            ->when($filters['outbound_type'] !== '', fn ($query) => $query->where('wo.outbound_type', $filters['outbound_type']))
            ->when($filters['release_status'] === 'released', fn ($query) => $query->whereNotNull('wo.released_at'))
            ->when($filters['release_status'] === 'unreleased', fn ($query) => $query->whereNull('wo.released_at'))
            ->when($filters['qc_status'] === 'passed', fn ($query) => $query->whereNotNull('wo.qc_passed_at'))
            ->when($filters['qc_status'] === 'pending', fn ($query) => $query->whereNull('wo.qc_passed_at'))
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
                wo.qc_passed_by
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
            COUNT(*) FILTER (WHERE NULLIF(TRIM(COALESCE(outbound_rows.shipping_number, \'\')), \'\') IS NULL)::int as total_without_shipping
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
}
