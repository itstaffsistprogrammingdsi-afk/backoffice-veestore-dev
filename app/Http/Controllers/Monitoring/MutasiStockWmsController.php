<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\MutasiStockWmsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class MutasiStockWmsController extends Controller
{
    private const OUTBOUND_TYPES = [
        'ecommerce' => 'Ecommerce',
        'destroy' => 'Destroy',
        'transfer' => 'Transfer',
        'return_to_supplier' => 'Return to Supplier',
        'store' => 'Store',
    ];

    public function index(Request $request)
    {
        $filters = [
            'warehouse_id'  => trim((string) $request->input('warehouse_id', '')),
            'outbound_type' => trim((string) $request->input('outbound_type', '')),
            'date_from'     => trim((string) $request->input('date_from', '')),
            'date_to'       => trim((string) $request->input('date_to', '')),
            'q'             => trim((string) $request->input('q', '')),
        ];

        $rows = $this->getFilteredRows($filters);

        $outboundIds = $rows->getCollection()->pluck('id')->filter()->values()->all();
        $productDetails = $this->getProductDetailsByOutboundIds($outboundIds);

        $rows->getCollection()->transform(function ($row) use ($productDetails) {
            $details = collect($productDetails->get($row->id, []));

            $row->product_details = $details->map(function ($d) {
                return [
                    'product_code' => $d->product_code,
                    'product_name' => $d->product_name,
                    'qty'          => (int) $d->qty,
                ];
            })->values();

            $row->outbound_type_label = self::OUTBOUND_TYPES[$row->outbound_type] ?? ($row->outbound_type ?: '-');
            $row->event_date = $row->shipment_date ?? $row->released_at ?? $row->created_at;
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;

            return $row;
        });

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Mutasi Stock Wms',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'          => $rows,
            'rowItems'      => $rows->items(),
            'warehouses'    => $warehouses,
            'outboundTypes' => self::OUTBOUND_TYPES,
            'filters'       => $filters,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.mutasi-stock-wms.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = [
            'warehouse_id'  => trim((string) $request->input('warehouse_id', '')),
            'outbound_type' => trim((string) $request->input('outbound_type', '')),
            'date_from'     => trim((string) $request->input('date_from', '')),
            'date_to'       => trim((string) $request->input('date_to', '')),
            'q'             => trim((string) $request->input('q', '')),
        ];

        $rows = $this->getFilteredRowsForExport($filters);

        $warehouseName = 'Semua Warehouse';
        if ($filters['warehouse_id'] !== '') {
            $warehouseName = DB::table('warehouses')
                ->where('id', $filters['warehouse_id'])
                ->value('name') ?? 'Semua Warehouse';
        }

        $outboundTypeLabel = self::OUTBOUND_TYPES[$filters['outbound_type']] ?? ($filters['outbound_type'] ?: 'Semua Type');

        $outboundIds = $rows->pluck('id')->filter()->values()->all();
        $productDetails = $this->getProductDetailsByOutboundIds($outboundIds);

        $exportFilters = [
            'warehouse_name' => $warehouseName,
            'outbound_type'  => $outboundTypeLabel,
            'date_from'      => $filters['date_from'],
            'date_to'        => $filters['date_to'],
            'q'              => $filters['q'],
        ];

        $filename = 'laporan-mutasi-stock-wms-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new MutasiStockWmsExport($rows, $productDetails, $exportFilters),
            $filename
        );
    }

    private function baseQuery(array $filters)
    {
        $warehouseId  = $filters['warehouse_id'] ?? '';
        $outboundType = $filters['outbound_type'] ?? '';
        $dateFrom     = $filters['date_from'] ?? '';
        $dateTo       = $filters['date_to'] ?? '';
        $q            = $filters['q'] ?? '';

        $hasCouriers = Schema::hasTable('couriers');
        $hasUsers    = Schema::hasTable('users');

        return DB::table('warehouse_outbounds as wo')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoin('warehouse_outbound_details as wod', 'wod.warehouse_outbound_id', '=', 'wo.id')
            ->leftJoin('items as i', 'i.id', '=', 'wod.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->when($hasUsers, fn ($q2) => $q2->leftJoin('users as u', 'u.id', '=', 'wo.user_id'))
            ->when($hasCouriers, fn ($q2) => $q2->leftJoin('couriers as c', 'c.id', '=', 'wo.courier_id'))
            ->whereNull('w.deleted_at')
            ->when($warehouseId !== '', fn ($q2) => $q2->where('wo.warehouse_id', $warehouseId))
            ->when($outboundType !== '', fn ($q2) => $q2->where('wo.outbound_type', $outboundType))
            ->when($dateFrom !== '', fn ($q2) => $q2->whereDate(DB::raw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at)'), '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q2) => $q2->whereDate(DB::raw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at)'), '<=', $dateTo))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($wq) use ($q) {
                    $wq->where('wo.invoice_number', 'like', "%{$q}%")
                        ->orWhere('wo.delivery_order_number', 'like', "%{$q}%")
                        ->orWhere('wo.order_number', 'like', "%{$q}%")
                        ->orWhere('wo.shipping_number', 'like', "%{$q}%")
                        ->orWhere('wo.outbound_type', 'like', "%{$q}%")
                        ->orWhere('w.name', 'like', "%{$q}%")
                        ->orWhere('w.code', 'like', "%{$q}%")
                        ->orWhere('p.code', 'like', "%{$q}%")
                        ->orWhere('p.name', 'like', "%{$q}%");
                });
            })
            ->selectRaw('
                wo.id,
                wo.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                wo.invoice_number,
                wo.delivery_order_number,
                wo.order_number,
                wo.shipping_number,
                wo.outbound_type,
                wo.order_ref,
                wo.shipment_date,
                wo.released_at,
                wo.created_at
            ')
            ->when(
                $hasUsers,
                fn ($q2) => $q2->selectRaw("COALESCE(u.name, '') as user_name"),
                fn ($q2) => $q2->selectRaw("'' as user_name")
            )
            ->when(
                $hasCouriers,
                fn ($q2) => $q2->selectRaw("COALESCE(c.name, '') as courier_name"),
                fn ($q2) => $q2->selectRaw("'' as courier_name")
            )
            ->selectRaw('COUNT(wod.id)::int as total_items')
            ->selectRaw('COUNT(DISTINCT i.product_id)::int as total_sku')
            ->groupBy(
                'wo.id',
                'wo.warehouse_id',
                'w.name',
                'w.code',
                'wo.invoice_number',
                'wo.delivery_order_number',
                'wo.order_number',
                'wo.shipping_number',
                'wo.outbound_type',
                'wo.order_ref',
                'wo.shipment_date',
                'wo.released_at',
                'wo.created_at',
                ...($hasUsers ? ['u.name'] : []),
                ...($hasCouriers ? ['c.name'] : [])
            )
            ->orderByRaw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at) DESC')
            ->orderByDesc('total_items');
    }

    private function getFilteredRows(array $filters)
    {
        return $this->baseQuery($filters)
            ->paginate(10)
            ->withQueryString();
    }

    private function getFilteredRowsForExport(array $filters)
    {
        return $this->baseQuery($filters)
            ->get()
            ->map(function ($r) {
                $r->total_items = (int) $r->total_items;
                $r->total_sku   = (int) $r->total_sku;
                $r->event_date  = $r->shipment_date ?? $r->released_at ?? $r->created_at;
                $r->outbound_type_label = self::OUTBOUND_TYPES[$r->outbound_type] ?? ($r->outbound_type ?: '-');
                return $r;
            });
    }

    private function getProductDetailsByOutboundIds(array $outboundIds)
    {
        if (empty($outboundIds)) {
            return collect();
        }

        return DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereIn('wod.warehouse_outbound_id', $outboundIds)
            ->selectRaw('
                wod.warehouse_outbound_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(wod.id)::int as qty
            ')
            ->groupBy(
                'wod.warehouse_outbound_id',
                'p.id',
                'p.code',
                'p.name'
            )
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get()
            ->groupBy('warehouse_outbound_id');
    }
}
