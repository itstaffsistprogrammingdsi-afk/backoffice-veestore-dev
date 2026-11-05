<?php

namespace App\Domain\WarehouseOperations\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InboundOperationsQuery
{
    public const TYPE_LABELS = [
        'supplier' => 'Supplier',
        'store_return' => 'Retur Store',
        'warehouse_transfer' => 'Transfer Warehouse',
    ];

    public function build(
        string $q,
        ?string $warehouseId,
        ?string $status,
        ?string $supplierId,
        ?string $inboundType = null,
        ?string $sourceWarehouseId = null,
        ?string $from = null,
        ?string $to = null,
    ): array {
        $rows = $this->baseQuery($q, $warehouseId, $status, $supplierId, $inboundType, $sourceWarehouseId, true, $from, $to)
            ->paginate(10)
            ->withQueryString();

        $details = $this->details($rows->getCollection()->pluck('id')->all());

        $rows->getCollection()->transform(function (object $row) use ($details) {
            $row->total_sku = (int) $row->total_sku;
            $row->total_qty = (int) $row->total_qty;
            $row->item_count = (int) $row->item_count;
            $row->qty_good = (int) $row->qty_good;
            $row->qty_bad = (int) $row->qty_bad;
            $row->qty_other = (int) $row->qty_other;
            $row->grand_total = (float) ($row->grand_total ?? 0);
            $row->quantity_item = (int) ($row->quantity_item ?? 0);
            $row->status = $row->received_date ? 'received' : 'pending';
            $row->status_label = $row->status === 'received' ? 'Diterima' : 'Menunggu penerimaan';
            $row->inbound_type_label = self::TYPE_LABELS[$row->inbound_type] ?? ($row->inbound_type ?: 'Lainnya');
            $row->product_details = collect($details->get($row->id, []))
                ->map(fn (object $detail) => [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'quantity' => (int) $detail->quantity,
                    'expired_date' => $detail->expired_date,
                    'price_idr' => (float) ($detail->price_idr ?? 0),
                    'exchange_currency' => $detail->exchange_currency,
                    'exchange_rate' => $detail->exchange_rate !== null ? (float) $detail->exchange_rate : null,
                    'exchange_amount' => $detail->exchange_amount !== null ? (float) $detail->exchange_amount : null,
                    'item_count' => (int) $detail->item_count,
                    'qty_good' => (int) $detail->qty_good,
                    'qty_bad' => (int) $detail->qty_bad,
                    'qty_other' => (int) $detail->qty_other,
                    'last_qc_at' => $detail->last_qc_at,
                    'condition_summary' => $detail->condition_summary,
                    'note' => $detail->note,
                ])
                ->values()
                ->all();

            return $row;
        });

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $warehouseId, $supplierId, $inboundType, $sourceWarehouseId, $from, $to),
            'skuRows' => $this->skuBreakdown($q, $warehouseId, $supplierId, $inboundType, $sourceWarehouseId, $from, $to),
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'pending', 'label' => 'Menunggu penerimaan'],
                ['value' => 'received', 'label' => 'Diterima'],
            ],
            'inboundTypes' => collect(self::TYPE_LABELS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    private function baseQuery(
        string $q,
        ?string $warehouseId,
        ?string $status,
        ?string $supplierId,
        ?string $inboundType,
        ?string $sourceWarehouseId,
        bool $applyStatus,
        ?string $from,
        ?string $to,
    ) {
        $hasUsers = Schema::hasTable('users');

        $latestQc = DB::table('warehouse_qc as wq')
            ->selectRaw('
                DISTINCT ON (wq.item_id)
                wq.item_id,
                wq.item_condition_id,
                COALESCE(wq.performed_at, wq.created_at) as qc_at
            ')
            ->orderBy('wq.item_id')
            ->orderByRaw('COALESCE(wq.performed_at, wq.created_at) DESC')
            ->orderByDesc('wq.id');

        $itemSummary = DB::table('warehouse_inbound_details as wid_qc')
            ->join('items as i_qc', 'i_qc.warehouse_inbound_detail_id', '=', 'wid_qc.id')
            ->leftJoinSub($latestQc, 'latest_qc', 'latest_qc.item_id', '=', 'i_qc.id')
            ->leftJoin('item_conditions as ic_qc', 'ic_qc.id', '=', 'latest_qc.item_condition_id')
            ->selectRaw('
                wid_qc.warehouse_inbound_id,
                COUNT(i_qc.id)::int as item_count,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic_qc.name, \'\')) = \'good\' THEN 1 ELSE 0 END), 0)::int as qty_good,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic_qc.name, \'\')) = \'bad\' THEN 1 ELSE 0 END), 0)::int as qty_bad,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic_qc.name, \'\')) NOT IN (\'good\', \'bad\') THEN 1 ELSE 0 END), 0)::int as qty_other,
                MAX(latest_qc.qc_at) as last_qc_at
            ')
            ->groupBy('wid_qc.warehouse_inbound_id');

        return DB::table('warehouse_inbounds as wi')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->leftJoin('warehouses as sw', 'sw.id', '=', 'wi.source_warehouse_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->leftJoin('store_returns as sr', 'sr.id', '=', 'wi.store_return_id')
            ->leftJoin('stores as return_store', 'return_store.id', '=', 'sr.store_id')
            ->leftJoin('warehouse_inbound_details as wid', 'wid.warehouse_inbound_id', '=', 'wi.id')
            ->leftJoin('products as p', 'p.id', '=', 'wid.product_id')
            ->leftJoinSub($itemSummary, 'item_summary', 'item_summary.warehouse_inbound_id', '=', 'wi.id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_received', 'u_received.id', '=', 'wi.received_by'))
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_created', 'u_created.id', '=', 'wi.created_by'))
            ->whereNull('w.deleted_at')
            ->when($warehouseId, fn ($query) => $query->where('wi.warehouse_id', $warehouseId))
            ->when($sourceWarehouseId, fn ($query) => $query->where('wi.source_warehouse_id', $sourceWarehouseId))
            ->when($supplierId, fn ($query) => $query->where('wi.supplier_id', $supplierId))
            ->when($inboundType, fn ($query) => $query->where('wi.inbound_type', $inboundType))
            ->when($applyStatus && $status === 'pending', fn ($query) => $query->whereNull('wi.received_date'))
            ->when($applyStatus && $status === 'received', fn ($query) => $query->whereNotNull('wi.received_date'))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(wi.received_date, wi.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(wi.received_date, wi.created_at)::date <= ?', [$to]))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('wi.invoice_number', 'ILIKE', "%{$q}%")
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
            })
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
                wi.store_return_id,
                COALESCE(sr.return_number, \'-\') as store_return_number,
                COALESCE(return_store.name, \'-\') as return_store_name,
                wi.inbound_type,
                wi.invoice_number,
                wi.delivery_order_number,
                wi.batch_number,
                wi.outbound_reference_number,
                wi.quantity_item,
                wi.grand_total,
                wi.received_date,
                wi.created_at,
                COALESCE(item_summary.item_count, 0)::int as item_count,
                COALESCE(item_summary.qty_good, 0)::int as qty_good,
                COALESCE(item_summary.qty_bad, 0)::int as qty_bad,
                COALESCE(item_summary.qty_other, 0)::int as qty_other,
                item_summary.last_qc_at,
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
                END as origin_reference,
                COUNT(DISTINCT wid.product_id)::int as total_sku,
                COALESCE(SUM(wid.quantity), 0)::int as total_qty
            ')
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u_received.name, '') as received_by_name"), fn ($query) => $query->selectRaw("'' as received_by_name"))
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u_created.name, '') as created_by_name"), fn ($query) => $query->selectRaw("'' as created_by_name"))
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
                'wi.store_return_id',
                'sr.return_number',
                'return_store.name',
                'wi.inbound_type',
                'wi.invoice_number',
                'wi.delivery_order_number',
                'wi.batch_number',
                'wi.outbound_reference_number',
                'wi.quantity_item',
                'wi.grand_total',
                'wi.received_date',
                'wi.created_at',
                'item_summary.item_count',
                'item_summary.qty_good',
                'item_summary.qty_bad',
                'item_summary.qty_other',
                'item_summary.last_qc_at',
                ...($hasUsers ? ['u_received.name', 'u_created.name'] : [])
            )
            ->orderByRaw('COALESCE(wi.received_date, wi.created_at) DESC')
            ->orderByDesc('total_qty');
    }

    private function summary(
        string $q,
        ?string $warehouseId,
        ?string $supplierId,
        ?string $inboundType,
        ?string $sourceWarehouseId,
        ?string $from,
        ?string $to,
    ): object {
        $query = $this->baseQuery($q, $warehouseId, null, $supplierId, $inboundType, $sourceWarehouseId, false, $from, $to)->reorder();

        return DB::query()
            ->fromSub($query, 'inbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_inbounds,
                COUNT(*) FILTER (WHERE received_date IS NULL)::int as pending_inbounds,
                COUNT(*) FILTER (WHERE received_date IS NOT NULL AND received_date::date = CURRENT_DATE)::int as received_today,
                COALESCE(SUM(total_sku), 0)::int as total_sku,
                COALESCE(SUM(total_qty), 0)::int as total_qty,
                COALESCE(SUM(item_count), 0)::int as total_items,
                COALESCE(SUM(qty_good), 0)::int as total_good,
                COALESCE(SUM(qty_bad), 0)::int as total_bad,
                COALESCE(SUM(qty_other), 0)::int as total_other,
                COALESCE(SUM(grand_total), 0)::numeric as total_value,
                COUNT(DISTINCT warehouse_id)::int as total_warehouses,
                COUNT(DISTINCT NULLIF(origin_name, \'-\'))::int as total_origins
            ')
            ->first();
    }

    private function skuBreakdown(
        string $q,
        ?string $warehouseId,
        ?string $supplierId,
        ?string $inboundType,
        ?string $sourceWarehouseId,
        ?string $from,
        ?string $to,
    ): array {
        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $warehouseId, null, $supplierId, $inboundType, $sourceWarehouseId, false, $from, $to)->reorder(), 'inbound_rows')
            ->pluck('id')
            ->all();

        if (empty($documentIds)) {
            return [];
        }

        $query = DB::table('warehouse_inbound_details as wid')
            ->join('warehouse_inbounds as wi', 'wi.id', '=', 'wid.warehouse_inbound_id')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->whereIn('wid.warehouse_inbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p.name', 'ILIKE', "%{$q}%");
            });
        }

        $rows = $query
            ->selectRaw('
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COALESCE(SUM(wid.quantity), 0)::int as qty,
                COUNT(DISTINCT wid.warehouse_inbound_id)::int as document_count,
                MAX(wi.received_date) as last_received_at
            ')
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get();

        $documentQuery = DB::table('warehouse_inbound_details as wid')
            ->join('warehouse_inbounds as wi', 'wi.id', '=', 'wid.warehouse_inbound_id')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->whereIn('wid.warehouse_inbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $documentQuery->where(function ($sub) use ($q) {
                $sub->where('p.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p.name', 'ILIKE', "%{$q}%");
            });
        }

        $documents = $documentQuery
            ->selectRaw('
                wid.product_id,
                wi.id,
                wi.inbound_type,
                wi.invoice_number,
                wi.delivery_order_number,
                wi.outbound_reference_number,
                wi.batch_number,
                wi.received_date,
                w.name as warehouse_name
            ')
            ->orderBy('wi.received_date')
            ->get()
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'id' => $row->id,
                'reference' => $row->invoice_number ?: $row->delivery_order_number ?: $row->outbound_reference_number ?: $row->batch_number ?: 'Tanpa nomor',
                'type_label' => self::TYPE_LABELS[$row->inbound_type] ?? ($row->inbound_type ?: 'Lainnya'),
                'warehouse_name' => $row->warehouse_name,
                'date' => $row->received_date,
            ])
            ->groupBy('product_id')
            ->map(fn ($documents) => $documents->unique('id')->values()->all());

        return $rows
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'qty' => (int) $row->qty,
                'document_count' => (int) $row->document_count,
                'last_received_at' => $row->last_received_at,
                'documents' => collect($documents->get($row->product_id, []))
                    ->map(fn (array $document) => [...$document])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
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

    private function details(array $inboundIds): Collection
    {
        if (empty($inboundIds)) {
            return collect();
        }

        $latestQc = DB::table('warehouse_qc as wq')
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
            ->leftJoinSub($latestQc, 'latest_qc', 'latest_qc.item_id', '=', 'i.id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'latest_qc.item_condition_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->selectRaw('
                wid.id as detail_id,
                COUNT(i.id)::int as item_count,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'good\' THEN 1 ELSE 0 END), 0)::int as qty_good,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'bad\' THEN 1 ELSE 0 END), 0)::int as qty_bad,
                COALESCE(SUM(CASE WHEN i.id IS NOT NULL AND LOWER(COALESCE(ic.name, \'\')) NOT IN (\'good\', \'bad\') THEN 1 ELSE 0 END), 0)::int as qty_other,
                MAX(latest_qc.qc_at) as last_qc_at
            ')
            ->groupBy('wid.id')
            ->get()
            ->keyBy('detail_id');

        $conditionBreakdown = DB::table('warehouse_inbound_details as wid')
            ->leftJoin('items as i', 'i.warehouse_inbound_detail_id', '=', 'wid.id')
            ->leftJoinSub($latestQc, 'latest_qc', 'latest_qc.item_id', '=', 'i.id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'latest_qc.item_condition_id')
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
            ->get()
            ->groupBy('detail_id');

        return DB::table('warehouse_inbound_details as wid')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->selectRaw('
                wid.id as detail_id,
                wid.warehouse_inbound_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                wid.quantity,
                wid.expired_date,
                wid.price_idr,
                wid.exchange_currency,
                wid.exchange_rate,
                wid.exchange_amount,
                wid.note
            ')
            ->orderBy('p.name')
            ->get()
            ->map(function (object $row) use ($itemConditionSummary, $conditionBreakdown) {
                $summary = $itemConditionSummary->get($row->detail_id);
                $conditions = collect($conditionBreakdown->get($row->detail_id, []));

                $row->item_count = (int) ($summary->item_count ?? 0);
                $row->qty_good = (int) ($summary->qty_good ?? 0);
                $row->qty_bad = (int) ($summary->qty_bad ?? 0);
                $row->qty_other = (int) ($summary->qty_other ?? 0);
                $row->last_qc_at = $summary->last_qc_at ?? null;
                $row->condition_summary = $conditions->isNotEmpty()
                    ? $conditions->map(fn (object $condition) => ($condition->condition_name ?? 'Tanpa Kondisi').': '.(int) $condition->qty)->implode(', ')
                    : '-';

                return $row;
            })
            ->groupBy('warehouse_inbound_id');
    }
}
