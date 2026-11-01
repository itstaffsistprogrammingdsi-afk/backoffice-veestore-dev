<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreInboundController extends Controller
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const QC_STATUS_LABELS = [
        'all_passed' => 'Semua Lolos QC',
        'partial_passed' => 'QC Sebagian',
        'pending' => 'Menunggu QC',
        'rejected' => 'Ditolak QC',
    ];

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        $rows = $this->getFilteredRows($filters);
        $summary = $this->getSummary($filters);

        $this->attachDetailData($rows->getCollection());

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $storedStatuses = DB::table('store_inbounds')
            ->whereNotNull('qc_status')
            ->distinct()
            ->orderBy('qc_status')
            ->pluck('qc_status');

        $qcStatuses = collect(array_keys(self::QC_STATUS_LABELS))
            ->merge($storedStatuses)
            ->filter()
            ->unique()
            ->values();

        return view('content.monitoring.store-inbound.index', [
            'rows' => $rows,
            'rowItems' => $rows->items(),
            'summary' => $summary,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'qcStatuses' => $qcStatuses,
            'qcStatusLabels' => self::QC_STATUS_LABELS,
            'filters' => $filters,
        ]);
    }

    private function getFilters(Request $request): array
    {
        $filters = [
            'store_id' => trim((string) $request->input('store_id', '')),
            'warehouse_source_id' => trim((string) $request->input('warehouse_source_id', '')),
            'qc_status' => trim((string) $request->input('qc_status', '')),
            'item_qc_status' => trim((string) $request->input('item_qc_status', '')),
            'date_from' => $this->normalizeDate($request->input('date_from')),
            'date_to' => $this->normalizeDate($request->input('date_to')),
            'q' => trim((string) $request->input('q', '')),
            'per_page' => $this->normalizePerPage($request->input('per_page', 10)),
        ];

        if ($filters['date_from'] && $filters['date_to'] && $filters['date_from'] > $filters['date_to']) {
            [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
        }

        return $filters;
    }

    private function baseQuery(array $filters)
    {
        $hasUsers = Schema::hasTable('users');

        $query = DB::table('store_inbounds as si')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'si.warehouse_source_id')
            ->leftJoin('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->leftJoin('items as i', 'i.id', '=', 'sid.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u', 'u.id', '=', 'si.received_by'))
            ->whereNull('s.deleted_at')
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('si.store_id', $filters['store_id']))
            ->when($filters['warehouse_source_id'] !== '', fn ($query) => $query->where('si.warehouse_source_id', $filters['warehouse_source_id']))
            ->when($filters['qc_status'] !== '', fn ($query) => $query->where('si.qc_status', $filters['qc_status']))
            ->when($filters['item_qc_status'] === 'all_checked', function ($query) {
                $query->havingRaw('COUNT(sid.id) = COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0)');
            })
            ->when($filters['item_qc_status'] === 'pending', function ($query) {
                $query->havingRaw('COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0) < COUNT(sid.id)');
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($keyword) {
                    $search->where('si.invoice_number', 'ILIKE', $keyword)
                        ->orWhere('si.delivery_order_number', 'ILIKE', $keyword)
                        ->orWhere('si.note', 'ILIKE', $keyword)
                        ->orWhereRaw('si.order_id::text ILIKE ?', [$keyword])
                        ->orWhere('s.name', 'ILIKE', $keyword)
                        ->orWhere('s.code', 'ILIKE', $keyword)
                        ->orWhere('w.name', 'ILIKE', $keyword)
                        ->orWhere('w.code', 'ILIKE', $keyword)
                        ->orWhere('p.name', 'ILIKE', $keyword)
                        ->orWhere('p.code', 'ILIKE', $keyword);
                });
            });

        $this->applyReceivedDateFilter($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->selectRaw('
                si.id,
                si.store_id,
                s.name as store_name,
                s.code as store_code,
                si.warehouse_source_id,
                COALESCE(w.name, \'-\') as warehouse_source_name,
                COALESCE(w.code, \'-\') as warehouse_source_code,
                si.invoice_number,
                si.delivery_order_number,
                si.quantity_item,
                si.grand_total,
                si.received_date,
                si.created_at,
                si.note,
                si.order_id,
                si.qc_status
            ')
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u.name, '') as received_by_name"), fn ($query) => $query->selectRaw("'' as received_by_name"))
            ->selectRaw('COUNT(DISTINCT sid.id)::int as total_items')
            ->selectRaw('COUNT(DISTINCT i.product_id)::int as total_sku')
            ->selectRaw('COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0)::int as qc_checked_items')
            ->selectRaw('COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 0 ELSE 1 END), 0)::int as qc_pending_items')
            ->groupBy(
                'si.id', 'si.store_id', 's.name', 's.code', 'si.warehouse_source_id', 'w.name', 'w.code',
                'si.invoice_number', 'si.delivery_order_number', 'si.quantity_item', 'si.grand_total',
                'si.received_date', 'si.created_at', 'si.note', 'si.order_id', 'si.qc_status',
                ...($hasUsers ? ['u.name'] : [])
            )
            ->orderByDesc('si.received_date')
            ->orderByDesc('total_items');
    }

    private function getFilteredRows(array $filters)
    {
        return $this->baseQuery($filters)->paginate($filters['per_page'])->withQueryString();
    }

    private function getSummary(array $filters): object
    {
        $query = $this->baseQuery($filters)->reorder();

        return DB::query()->fromSub($query, 'store_inbound_rows')->selectRaw('
            COUNT(*)::int as total_inbounds,
            COALESCE(SUM(store_inbound_rows.total_sku), 0)::int as total_sku,
            COALESCE(SUM(store_inbound_rows.total_items), 0)::int as total_items,
            COALESCE(SUM(store_inbound_rows.grand_total), 0)::numeric as total_value,
            COALESCE(SUM(store_inbound_rows.qc_checked_items), 0)::int as total_qc_checked_items,
            COALESCE(SUM(store_inbound_rows.qc_pending_items), 0)::int as total_qc_pending_items,
            COUNT(*) FILTER (WHERE store_inbound_rows.qc_status = \'all_passed\')::int as total_all_passed,
            COUNT(*) FILTER (WHERE store_inbound_rows.qc_status = \'partial_passed\')::int as total_partial_passed
        ')->first();
    }

    private function attachDetailData(Collection $rows): void
    {
        $detailMap = $this->getDetailSummaryByInboundIds($rows->pluck('id')->filter()->values()->all());

        $rows->transform(function ($row) use ($detailMap) {
            $details = collect($detailMap->get($row->id, []));
            $row->product_details = $details->map(fn ($detail) => [
                'product_code' => $detail->product_code,
                'product_name' => $detail->product_name,
                'qty' => (int) $detail->qty,
                'qc_checked' => (int) $detail->qc_checked,
                'qc_pending' => (int) $detail->qc_pending,
                'condition_summary' => $detail->condition_summary,
            ])->values();
            $row->product_summary = $details->map(fn ($detail) => ($detail->product_name ?? '-').' ('.(int) $detail->qty.')')->take(3)->implode(', ');
            $row->product_summary_more = max(0, $details->count() - 3);
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->qc_checked_items = (int) $row->qc_checked_items;
            $row->qc_pending_items = (int) $row->qc_pending_items;
            $row->qc_status_label = self::QC_STATUS_LABELS[$row->qc_status] ?? ($row->qc_status ?: 'Belum ada status QC');

            return $row;
        });
    }

    private function getDetailSummaryByInboundIds(array $inboundIds): Collection
    {
        if (empty($inboundIds)) {
            return collect();
        }

        $conditionBreakdown = DB::table('store_inbound_details as sid')
            ->join('items as i', 'i.id', '=', 'sid.item_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('sid.store_inbound_id', $inboundIds)
            ->selectRaw("sid.store_inbound_id, i.product_id, COALESCE(NULLIF(TRIM(ic.name), ''), 'Tanpa Kondisi') as condition_name, COUNT(sid.id)::int as qty")
            ->groupBy('sid.store_inbound_id', 'i.product_id', DB::raw("COALESCE(NULLIF(TRIM(ic.name), ''), 'Tanpa Kondisi')"))
            ->orderBy('sid.store_inbound_id')
            ->orderBy('i.product_id')
            ->orderByRaw('COUNT(sid.id) DESC')
            ->get()
            ->groupBy(fn ($row) => $row->store_inbound_id.'|'.$row->product_id);

        return DB::table('store_inbound_details as sid')
            ->join('items as i', 'i.id', '=', 'sid.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereIn('sid.store_inbound_id', $inboundIds)
            ->selectRaw("\n                sid.store_inbound_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(sid.id)::int as qty,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0)::int as qc_checked,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 0 ELSE 1 END), 0)::int as qc_pending
            ")
            ->groupBy('sid.store_inbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(function ($row) use ($conditionBreakdown) {
                $breakdown = collect($conditionBreakdown->get($row->store_inbound_id.'|'.$row->product_id, []));
                $row->condition_summary = $breakdown->isEmpty()
                    ? '-'
                    : $breakdown->map(fn ($item) => $item->condition_name.': '.(int) $item->qty)->implode(', ');

                return $row;
            })
            ->groupBy('store_inbound_id');
    }

    private function applyReceivedDateFilter($query, ?string $dateFrom, ?string $dateTo): void
    {
        if (! $dateFrom && ! $dateTo) {
            return;
        }

        $dateFrom ??= $dateTo;
        $dateTo ??= $dateFrom;
        $start = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->startOfDay()->utc();
        $end = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->endOfDay()->utc();

        $query->whereBetween('si.received_date', [$start, $end]);
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
}
