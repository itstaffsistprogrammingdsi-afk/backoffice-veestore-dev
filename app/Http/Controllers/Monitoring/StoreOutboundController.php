<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreOutboundController extends Controller
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const OUTBOUND_TYPE_LABELS = [
        'store' => 'Store',
        'ecommerce' => 'Ecommerce',
        'return' => 'Retur',
        'transfer' => 'Transfer',
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

        $couriers = Schema::hasTable('couriers')
            ? DB::table('couriers')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        $storedTypes = DB::table('store_outbounds')
            ->whereNotNull('outbound_type')
            ->distinct()
            ->orderBy('outbound_type')
            ->pluck('outbound_type');

        $outboundTypes = collect(array_keys(self::OUTBOUND_TYPE_LABELS))
            ->merge($storedTypes)
            ->filter()
            ->unique()
            ->values();

        return view('content.monitoring.store-outbound.index', [
            'rows' => $rows,
            'rowItems' => $rows->items(),
            'summary' => $summary,
            'stores' => $stores,
            'couriers' => $couriers,
            'outboundTypes' => $outboundTypes,
            'outboundTypeLabels' => self::OUTBOUND_TYPE_LABELS,
            'filters' => $filters,
        ]);
    }

    private function getFilters(Request $request): array
    {
        $filters = [
            'store_id' => trim((string) $request->input('store_id', '')),
            'courier_id' => trim((string) $request->input('courier_id', '')),
            'outbound_type' => trim((string) $request->input('outbound_type', '')),
            'source' => trim((string) $request->input('source', '')),
            'shipping_status' => trim((string) $request->input('shipping_status', '')),
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
        $hasCouriers = Schema::hasTable('couriers');

        $query = DB::table('store_outbounds as so')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->leftJoin('ecommerce_orders as eo', 'eo.id', '=', 'so.ecommerce_order_id')
            ->leftJoin('payments as pay', 'pay.id', '=', 'so.payment_id')
            ->leftJoin('store_returns as sr', 'sr.id', '=', 'so.store_return_id')
            ->leftJoin('store_outbound_details as sod', 'sod.store_outbound_id', '=', 'so.id')
            ->leftJoin('items as i', 'i.id', '=', 'sod.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'sod.rfid_tag_id')
            ->when($hasCouriers, fn ($query) => $query->leftJoin('couriers as c', 'c.id', '=', 'so.courier_id'))
            ->whereNull('s.deleted_at')
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('so.store_id', $filters['store_id']))
            ->when($filters['courier_id'] !== '', fn ($query) => $query->where('so.courier_id', $filters['courier_id']))
            ->when($filters['outbound_type'] !== '', fn ($query) => $query->where('so.outbound_type', $filters['outbound_type']))
            ->when($filters['source'] === 'ecommerce', fn ($query) => $query->whereNotNull('so.ecommerce_order_id'))
            ->when($filters['source'] === 'store_cart', fn ($query) => $query->whereNotNull('so.store_cart_id'))
            ->when($filters['source'] === 'store_return', fn ($query) => $query->whereNotNull('so.store_return_id'))
            ->when($filters['source'] === 'other', fn ($query) => $query->whereNull('so.ecommerce_order_id')->whereNull('so.store_cart_id')->whereNull('so.store_return_id'))
            ->when($filters['shipping_status'] === 'with_shipping', fn ($query) => $query->whereRaw("NULLIF(BTRIM(COALESCE(so.shipping_number, eo.shipping_number, '')), '') IS NOT NULL"))
            ->when($filters['shipping_status'] === 'without_shipping', fn ($query) => $query->whereRaw("NULLIF(BTRIM(COALESCE(so.shipping_number, eo.shipping_number, '')), '') IS NULL"))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($keyword) {
                    $search->where('eo.order_number', 'ILIKE', $keyword)
                        ->orWhere('pay.transaction_number', 'ILIKE', $keyword)
                        ->orWhere('sr.return_number', 'ILIKE', $keyword)
                        ->orWhere('so.shipping_number', 'ILIKE', $keyword)
                        ->orWhere('eo.shipping_number', 'ILIKE', $keyword)
                        ->orWhere('so.outbound_type', 'ILIKE', $keyword)
                        ->orWhere('s.name', 'ILIKE', $keyword)
                        ->orWhere('s.code', 'ILIKE', $keyword)
                        ->orWhere('p.name', 'ILIKE', $keyword)
                        ->orWhere('p.code', 'ILIKE', $keyword)
                        ->orWhere('rt.serial_number', 'ILIKE', $keyword)
                        ->orWhere('rt.value', 'ILIKE', $keyword);
                });
            });

        $this->applyEventDateFilter($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->selectRaw('
                so.id,
                so.store_id,
                s.name as store_name,
                s.code as store_code,
                so.ecommerce_order_id,
                so.store_cart_id,
                so.courier_id,
                so.outbound_type,
                so.shipment_date,
                so.created_at,
                so.item_quantity,
                so.payment_id,
                so.store_return_id,
                COALESCE(NULLIF(BTRIM(so.shipping_number), \'\'), NULLIF(BTRIM(eo.shipping_number), \'\'), \'-\') as shipping_number,
                COALESCE(eo.order_number, \'-\') as ecommerce_order_number,
                COALESCE(pay.transaction_number, \'-\') as payment_number,
                COALESCE(sr.return_number, \'-\') as store_return_number,
                CASE
                    WHEN so.ecommerce_order_id IS NOT NULL THEN \'Ecommerce\'
                    WHEN so.store_cart_id IS NOT NULL THEN \'Store Cart\'
                    WHEN so.store_return_id IS NOT NULL THEN \'Store Return\'
                    ELSE \'Lainnya\'
                END as source_label
            ')
            ->when($hasCouriers, fn ($query) => $query->selectRaw("COALESCE(c.name, '') as courier_name"), fn ($query) => $query->selectRaw("'' as courier_name"))
            ->selectRaw('COUNT(DISTINCT sod.id)::int as total_items')
            ->selectRaw('COUNT(DISTINCT i.product_id)::int as total_sku')
            ->selectRaw('COUNT(DISTINCT sod.rfid_tag_id)::int as total_rfid')
            ->groupBy(
                'so.id', 'so.store_id', 's.name', 's.code', 'so.ecommerce_order_id', 'so.store_cart_id',
                'so.courier_id', 'so.outbound_type', 'so.shipment_date', 'so.created_at', 'so.item_quantity',
                'so.payment_id', 'so.store_return_id', 'so.shipping_number', 'eo.shipping_number',
                'eo.order_number', 'pay.transaction_number', 'sr.return_number',
                ...($hasCouriers ? ['c.name'] : [])
            )
            ->orderByRaw('COALESCE(so.shipment_date, so.created_at) DESC')
            ->orderByDesc('total_items');
    }

    private function getFilteredRows(array $filters)
    {
        return $this->baseQuery($filters)->paginate($filters['per_page'])->withQueryString();
    }

    private function getSummary(array $filters): object
    {
        $query = $this->baseQuery($filters)->reorder();

        return DB::query()->fromSub($query, 'store_outbound_rows')->selectRaw('
            COUNT(*)::int as total_outbounds,
            COALESCE(SUM(store_outbound_rows.total_sku), 0)::int as total_sku,
            COALESCE(SUM(store_outbound_rows.total_items), 0)::int as total_items,
            COALESCE(SUM(store_outbound_rows.total_rfid), 0)::int as total_rfid,
            COUNT(*) FILTER (WHERE store_outbound_rows.shipping_number <> \'-\')::int as total_with_shipping,
            COUNT(*) FILTER (WHERE store_outbound_rows.ecommerce_order_id IS NOT NULL)::int as total_ecommerce,
            COUNT(*) FILTER (WHERE store_outbound_rows.store_return_id IS NOT NULL)::int as total_returns
        ')->first();
    }

    private function attachDetailData(Collection $rows): void
    {
        $detailMap = $this->getDetailSummaryByOutboundIds($rows->pluck('id')->filter()->values()->all());

        $rows->transform(function ($row) use ($detailMap) {
            $details = collect($detailMap->get($row->id, []));
            $row->product_details = $details->map(fn ($detail) => [
                'product_code' => $detail->product_code,
                'product_name' => $detail->product_name,
                'qty' => (int) $detail->qty,
                'rfid_count' => (int) $detail->rfid_count,
                'rfid_summary' => $detail->rfid_summary,
                'released_by_summary' => $detail->released_by_summary,
            ])->values();
            $row->product_summary = $details->map(fn ($detail) => ($detail->product_name ?? '-').' ('.(int) $detail->qty.')')->take(3)->implode(', ');
            $row->product_summary_more = max(0, $details->count() - 3);
            $row->event_date = $row->shipment_date ?? $row->created_at;
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->total_rfid = (int) $row->total_rfid;
            $row->outbound_type_label = self::OUTBOUND_TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: '-');

            return $row;
        });
    }

    private function getDetailSummaryByOutboundIds(array $outboundIds): Collection
    {
        if (empty($outboundIds)) {
            return collect();
        }

        $hasUsers = Schema::hasTable('users');

        return DB::table('store_outbound_details as sod')
            ->join('items as i', 'i.id', '=', 'sod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'sod.rfid_tag_id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u', 'u.id', '=', 'sod.rfid_releaser_user_id'))
            ->whereIn('sod.store_outbound_id', $outboundIds)
            ->selectRaw("\n                sod.store_outbound_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(sod.id)::int as qty,
                COUNT(DISTINCT sod.rfid_tag_id)::int as rfid_count,
                COALESCE(STRING_AGG(DISTINCT NULLIF(COALESCE(rt.serial_number::text, rt.value), ''), ', '), '-') as rfid_summary
            ")
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(STRING_AGG(DISTINCT NULLIF(u.name, ''), ', '), '-') as released_by_summary"), fn ($query) => $query->selectRaw("'-' as released_by_summary"))
            ->groupBy('sod.store_outbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->groupBy('store_outbound_id');
    }

    private function applyEventDateFilter($query, ?string $dateFrom, ?string $dateTo): void
    {
        if (! $dateFrom && ! $dateTo) {
            return;
        }

        $dateFrom ??= $dateTo;
        $dateTo ??= $dateFrom;
        $start = Carbon::createFromFormat('Y-m-d', $dateFrom, self::APP_TIMEZONE)->startOfDay()->utc();
        $end = Carbon::createFromFormat('Y-m-d', $dateTo, self::APP_TIMEZONE)->endOfDay()->utc();

        $query->whereBetween(DB::raw('COALESCE(so.shipment_date, so.created_at)'), [$start, $end]);
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
