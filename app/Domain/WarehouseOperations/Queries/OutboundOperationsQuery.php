<?php

namespace App\Domain\WarehouseOperations\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OutboundOperationsQuery
{
    private const TYPE_LABELS = [
        'ecommerce' => 'Ecommerce',
        'destroy' => 'Destroy',
        'transfer' => 'Transfer',
        'return_to_supplier' => 'Retur ke Supplier',
        'store' => 'Store',
    ];

    public function build(string $q, ?string $warehouseId, ?string $status, ?string $channel, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->baseQuery($q, $warehouseId, $status, $channel, true, $from, $to)
            ->paginate(10)
            ->withQueryString();

        $details = $this->details($rows->getCollection()->pluck('id')->all());

        $rows->getCollection()->transform(function (object $row) use ($details) {
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->outbound_type_label = self::TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: 'Lainnya');
            $row->event_date = $row->shipment_date ?? $row->released_at ?? $row->created_at;
            $row->has_awb = trim((string) $row->shipping_number) !== '';
            $row->has_delivery_order = trim((string) $row->delivery_order_number) !== '';
            $row->requires_awb = $row->outbound_type === 'ecommerce';
            $row->requires_delivery_order = $row->outbound_type === 'store';

            if (! $row->qc_passed_at) {
                $row->status = 'qc_pending';
                $row->status_label = 'Menunggu QC';
            } elseif (! $row->released_at) {
                $row->status = 'release_pending';
                $row->status_label = 'Menunggu Release';
            } elseif ($row->requires_awb && ! $row->has_awb) {
                $row->status = 'missing_awb';
                $row->status_label = 'Belum Ada Resi';
            } elseif ($row->requires_delivery_order && ! $row->has_delivery_order) {
                $row->status = 'missing_delivery_order';
                $row->status_label = 'Belum Ada Surat Jalan';
            } else {
                $row->status = 'completed';
                $row->status_label = 'Selesai';
            }

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

            $row->product_details = collect($details->get($row->id, []))
                ->map(fn (object $detail) => [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'qty' => (int) $detail->qty,
                    'qty_good' => (int) $detail->qty_good,
                    'qty_bad' => (int) $detail->qty_bad,
                    'qty_other' => (int) $detail->qty_other,
                ])
                ->values()
                ->all();

            return $row;
        });

        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $warehouseId, null, $channel, false, $from, $to)->reorder(), 'outbound_rows')
            ->pluck('id')
            ->all();

        $skuBreakdown = $this->skuBreakdown($documentIds, $q);

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $warehouseId, $channel, $from, $to),
            'movementSummary' => $skuBreakdown['summary'],
            'skuRows' => $skuBreakdown['rows'],
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'qc_pending', 'label' => 'Menunggu QC'],
                ['value' => 'release_pending', 'label' => 'Menunggu Release'],
                ['value' => 'missing_awb', 'label' => 'Belum Ada Resi (Ecommerce)'],
                ['value' => 'missing_delivery_order', 'label' => 'Belum Ada Surat Jalan (Store)'],
                ['value' => 'completed', 'label' => 'Selesai'],
            ],
        ];
    }

    private function baseQuery(string $q, ?string $warehouseId, ?string $status, ?string $channel, bool $applyStatus = true, ?string $from = null, ?string $to = null)
    {
        $hasUsers = Schema::hasTable('users');
        $hasCouriers = Schema::hasTable('couriers');

        $storeInbound = DB::table('store_inbounds as si')
            ->leftJoin('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->whereNotNull('si.order_id')
            ->selectRaw('
                si.order_id,
                COUNT(sid.id) FILTER (WHERE si.received_date IS NOT NULL)::int as received_qty,
                MIN(si.received_date) as received_at
            ')
            ->groupBy('si.order_id');

        return DB::table('warehouse_outbounds as wo')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoin('store_orders as store_order', 'store_order.id', '=', 'wo.order_id')
            ->leftJoin('stores as destination_store', 'destination_store.id', '=', 'store_order.store_id')
            ->leftJoinSub($storeInbound, 'store_inbound', 'store_inbound.order_id', '=', 'store_order.id')
            ->when($hasCouriers, fn ($query) => $query->leftJoin('couriers as c', 'c.id', '=', 'wo.courier_id'))
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u', 'u.id', '=', 'wo.user_id'))
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as qc', 'qc.id', '=', 'wo.qc_passed_by'))
            ->whereNull('w.deleted_at')
            ->when($warehouseId, fn ($query) => $query->where('wo.warehouse_id', $warehouseId))
            ->when($channel, fn ($query) => $query->where('wo.outbound_type', $channel))
            ->when($applyStatus && $status === 'qc_pending', fn ($query) => $query->whereNull('wo.qc_passed_at'))
            ->when($applyStatus && $status === 'release_pending', fn ($query) => $query
                ->whereNotNull('wo.qc_passed_at')
                ->whereNull('wo.released_at'))
            ->when($applyStatus && $status === 'missing_awb', fn ($query) => $query
                ->whereNotNull('wo.qc_passed_at')
                ->whereNotNull('wo.released_at')
                ->whereRaw("LOWER(wo.outbound_type::text) = 'ecommerce'")
                ->whereRaw("NULLIF(TRIM(wo.shipping_number), '') IS NULL"))
            ->when($applyStatus && $status === 'missing_delivery_order', fn ($query) => $query
                ->whereNotNull('wo.qc_passed_at')
                ->whereNotNull('wo.released_at')
                ->whereRaw("LOWER(wo.outbound_type::text) = 'store'")
                ->whereRaw("NULLIF(TRIM(wo.delivery_order_number), '') IS NULL"))
            ->when($applyStatus && $status === 'completed', fn ($query) => $query
                ->whereNotNull('wo.qc_passed_at')
                ->whereNotNull('wo.released_at')
                ->whereRaw("NOT (LOWER(wo.outbound_type::text) = 'ecommerce' AND NULLIF(TRIM(wo.shipping_number), '') IS NULL)")
                ->whereRaw("NOT (LOWER(wo.outbound_type::text) = 'store' AND NULLIF(TRIM(wo.delivery_order_number), '') IS NULL)"))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at)::date <= ?', [$to]))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('wo.invoice_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wo.delivery_order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wo.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wo.shipping_number', 'ILIKE', "%{$q}%")
                        ->orWhere('wo.outbound_type', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%")
                        ->orWhere('destination_store.name', 'ILIKE', "%{$q}%")
                        ->orWhere('destination_store.code', 'ILIKE', "%{$q}%")
                        ->orWhereExists(function ($exists) use ($q) {
                            $exists->selectRaw('1')
                                ->from('warehouse_outbound_details as wod_search')
                                ->join('items as i_search', 'i_search.id', '=', 'wod_search.item_id')
                                ->join('products as p_search', 'p_search.id', '=', 'i_search.product_id')
                                ->whereColumn('wod_search.warehouse_outbound_id', 'wo.id')
                                ->where(function ($productQuery) use ($q) {
                                    $productQuery->where('p_search.code', 'ILIKE', "%{$q}%")
                                        ->orWhere('p_search.name', 'ILIKE', "%{$q}%");
                                });
                        });
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
                wo.order_ref,
                wo.outbound_type,
                wo.quantity_item,
                wo.shipment_date,
                wo.created_at,
                wo.released_at,
                wo.shipping_number,
                wo.qc_passed_at,
                wo.qc_passed_by,
                store_order.store_id as destination_store_id,
                destination_store.name as destination_store_name,
                destination_store.code as destination_store_code,
                COALESCE(store_inbound.received_qty, 0)::int as store_received_qty,
                store_inbound.received_at as store_received_at,
                (SELECT COUNT(*)::int FROM warehouse_outbound_details wod_count WHERE wod_count.warehouse_outbound_id = wo.id) as total_items,
                (SELECT COUNT(DISTINCT i_count.product_id)::int
                    FROM warehouse_outbound_details wod_count
                    JOIN items i_count ON i_count.id = wod_count.item_id
                    WHERE wod_count.warehouse_outbound_id = wo.id) as total_sku
            ')
            ->when($hasCouriers, fn ($query) => $query->selectRaw("COALESCE(c.name, '') as courier_name"), fn ($query) => $query->selectRaw("'' as courier_name"))
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u.name, '') as outbound_by_name"), fn ($query) => $query->selectRaw("'' as outbound_by_name"))
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(qc.name, '') as qc_passed_by_name"), fn ($query) => $query->selectRaw("'' as qc_passed_by_name"))
            ->orderByRaw('COALESCE(wo.shipment_date, wo.released_at, wo.created_at) DESC')
            ->orderByDesc('wo.created_at');
    }

    private function summary(string $q, ?string $warehouseId, ?string $channel, ?string $from = null, ?string $to = null): object
    {
        $query = $this->baseQuery($q, $warehouseId, null, $channel, false, $from, $to);

        return DB::query()
            ->fromSub($query, 'outbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_outbounds,
                COUNT(*) FILTER (WHERE qc_passed_at IS NULL)::int as qc_pending,
                COUNT(*) FILTER (WHERE qc_passed_at IS NOT NULL AND released_at IS NULL)::int as release_pending,
                COUNT(*) FILTER (WHERE qc_passed_at IS NOT NULL AND released_at IS NOT NULL AND LOWER(outbound_type::text) = \'ecommerce\' AND NULLIF(TRIM(shipping_number), \'\') IS NULL)::int as missing_awb,
                COUNT(*) FILTER (WHERE qc_passed_at IS NOT NULL AND released_at IS NOT NULL AND LOWER(outbound_type::text) = \'store\' AND NULLIF(TRIM(delivery_order_number), \'\') IS NULL)::int as missing_delivery_order,
                COUNT(*) FILTER (WHERE COALESCE(shipment_date, released_at, created_at)::date = CURRENT_DATE)::int as today_outbounds,
                COUNT(*) FILTER (WHERE destination_store_id IS NOT NULL AND released_at IS NOT NULL)::int as store_transfers,
                COUNT(*) FILTER (WHERE destination_store_id IS NOT NULL AND released_at IS NOT NULL AND store_received_qty <= 0)::int as store_not_recorded,
                COUNT(*) FILTER (WHERE destination_store_id IS NOT NULL AND released_at IS NOT NULL AND store_received_qty > 0 AND store_received_qty < total_items)::int as store_partial
            ')
            ->first();
    }

    private function skuBreakdown(array $documentIds, string $q): array
    {
        $summary = [
            'total_qty' => 0,
            'total_sku' => 0,
            'store_qty' => 0,
            'ecommerce_qty' => 0,
            'other_qty' => 0,
        ];

        if (empty($documentIds)) {
            return ['summary' => $summary, 'rows' => []];
        }

        $query = DB::table('warehouse_outbound_details as wod')
            ->join('warehouse_outbounds as wo', 'wo.id', '=', 'wod.warehouse_outbound_id')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereIn('wod.warehouse_outbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p.name', 'ILIKE', "%{$q}%");
            });
        }

        $summaryRow = (clone $query)
            ->selectRaw("
                COUNT(wod.id)::int as total_qty,
                COUNT(DISTINCT i.product_id)::int as total_sku,
                SUM(CASE WHEN wo.outbound_type = 'store' THEN 1 ELSE 0 END)::int as store_qty,
                SUM(CASE WHEN wo.outbound_type = 'ecommerce' THEN 1 ELSE 0 END)::int as ecommerce_qty,
                SUM(CASE WHEN wo.outbound_type NOT IN ('store', 'ecommerce') THEN 1 ELSE 0 END)::int as other_qty
            ")
            ->first();

        $summary = [
            'total_qty' => (int) ($summaryRow->total_qty ?? 0),
            'total_sku' => (int) ($summaryRow->total_sku ?? 0),
            'store_qty' => (int) ($summaryRow->store_qty ?? 0),
            'ecommerce_qty' => (int) ($summaryRow->ecommerce_qty ?? 0),
            'other_qty' => (int) ($summaryRow->other_qty ?? 0),
        ];

        $rows = $query
            ->selectRaw("
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(wod.id)::int as qty,
                COUNT(DISTINCT wod.warehouse_outbound_id)::int as document_count,
                SUM(CASE WHEN wo.outbound_type = 'store' THEN 1 ELSE 0 END)::int as store_qty,
                SUM(CASE WHEN wo.outbound_type = 'ecommerce' THEN 1 ELSE 0 END)::int as ecommerce_qty,
                SUM(CASE WHEN wo.outbound_type NOT IN ('store', 'ecommerce') THEN 1 ELSE 0 END)::int as other_qty
            ")
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get();

        $documentQuery = DB::table('warehouse_outbound_details as wod')
            ->join('warehouse_outbounds as wo', 'wo.id', '=', 'wod.warehouse_outbound_id')
            ->join('items as i_doc', 'i_doc.id', '=', 'wod.item_id')
            ->join('products as p_doc', 'p_doc.id', '=', 'i_doc.product_id')
            ->join('warehouses as w_doc', 'w_doc.id', '=', 'wo.warehouse_id')
            ->whereIn('wod.warehouse_outbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $documentQuery->where(function ($sub) use ($q) {
                $sub->where('p_doc.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p_doc.name', 'ILIKE', "%{$q}%");
            });
        }

        $documents = $documentQuery
            ->selectRaw('
                i_doc.product_id,
                wo.id,
                wo.order_number,
                wo.invoice_number,
                wo.delivery_order_number,
                wo.shipping_number,
                wo.outbound_type,
                wo.created_at,
                wo.shipment_date,
                w_doc.name as warehouse_name
            ')
            ->orderBy('wo.created_at')
            ->get()
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'id' => $row->id,
                'reference' => $row->order_number ?: $row->invoice_number ?: $row->delivery_order_number ?: 'Tanpa nomor',
                'type_label' => self::TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: 'Lainnya'),
                'warehouse_name' => $row->warehouse_name,
                'shipping_number' => $row->shipping_number,
                'date' => $row->shipment_date ?? $row->created_at,
            ])
            ->groupBy('product_id')
            ->map(fn ($documents) => $documents->unique('id')->values()->all());

        $rows = $rows
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'qty' => (int) $row->qty,
                'document_count' => (int) $row->document_count,
                'store_qty' => (int) $row->store_qty,
                'ecommerce_qty' => (int) $row->ecommerce_qty,
                'other_qty' => (int) $row->other_qty,
                'documents' => collect($documents->get($row->product_id, []))
                    ->map(fn (array $document) => [...$document])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return ['summary' => $summary, 'rows' => $rows];
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

    private function details(array $outboundIds): Collection
    {
        if (empty($outboundIds)) {
            return collect();
        }

        return DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('wod.warehouse_outbound_id', $outboundIds)
            ->selectRaw('
                wod.warehouse_outbound_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(wod.id)::int as qty,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'good\' THEN 1 ELSE 0 END)::int as qty_good,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'bad\' THEN 1 ELSE 0 END)::int as qty_bad,
                SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) NOT IN (\'good\', \'bad\') THEN 1 ELSE 0 END)::int as qty_other
            ')
            ->groupBy('wod.warehouse_outbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->groupBy('warehouse_outbound_id');
    }
}
