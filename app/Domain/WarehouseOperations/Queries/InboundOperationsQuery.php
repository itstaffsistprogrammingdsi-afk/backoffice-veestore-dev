<?php

namespace App\Domain\WarehouseOperations\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InboundOperationsQuery
{
    private const TYPE_LABELS = [
        'supplier' => 'Supplier',
        'store_return' => 'Retur Store',
        'warehouse_transfer' => 'Transfer Warehouse',
    ];

    public function build(string $q, ?string $warehouseId, ?string $status, ?string $supplierId, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->baseQuery($q, $warehouseId, $status, $supplierId, true, $from, $to)
            ->paginate(10)
            ->withQueryString();

        $details = $this->details($rows->getCollection()->pluck('id')->all());

        $rows->getCollection()->transform(function (object $row) use ($details) {
            $row->total_sku = (int) $row->total_sku;
            $row->total_qty = (int) $row->total_qty;
            $row->status = $row->received_date ? 'received' : 'pending';
            $row->status_label = $row->status === 'received' ? 'Diterima' : 'Menunggu penerimaan';
            $row->inbound_type_label = self::TYPE_LABELS[$row->inbound_type] ?? ($row->inbound_type ?: 'Lainnya');
            $row->product_details = collect($details->get($row->id, []))
                ->map(fn (object $detail) => [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'quantity' => (int) $detail->quantity,
                    'expired_date' => $detail->expired_date,
                ])
                ->values()
                ->all();

            return $row;
        });

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $warehouseId, $supplierId, $from, $to),
            'skuRows' => $this->skuBreakdown($q, $warehouseId, $supplierId, $from, $to),
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'pending', 'label' => 'Menunggu penerimaan'],
                ['value' => 'received', 'label' => 'Diterima'],
            ],
        ];
    }

    private function baseQuery(string $q, ?string $warehouseId, ?string $status, ?string $supplierId, bool $applyStatus = true, ?string $from = null, ?string $to = null)
    {
        return DB::table('warehouse_inbounds as wi')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->leftJoin('warehouses as sw', 'sw.id', '=', 'wi.source_warehouse_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->leftJoin('store_returns as sr', 'sr.id', '=', 'wi.store_return_id')
            ->leftJoin('stores as return_store', 'return_store.id', '=', 'sr.store_id')
            ->leftJoin('warehouse_inbound_details as wid', 'wid.warehouse_inbound_id', '=', 'wi.id')
            ->leftJoin('products as p', 'p.id', '=', 'wid.product_id')
            ->whereNull('w.deleted_at')
            ->when($warehouseId, fn ($query) => $query->where('wi.warehouse_id', $warehouseId))
            ->when($supplierId, fn ($query) => $query->where('wi.supplier_id', $supplierId))
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
                wi.inbound_type,
                wi.invoice_number,
                wi.delivery_order_number,
                wi.batch_number,
                wi.outbound_reference_number,
                wi.quantity_item,
                wi.grand_total,
                wi.received_date,
                wi.created_at,
                COALESCE(s.name, \'-\') as supplier_name,
                COALESCE(sw.name, \'-\') as source_warehouse_name,
                COALESCE(sr.return_number, \'-\') as store_return_number,
                COALESCE(return_store.name, \'-\') as return_store_name,
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
            ->groupBy(
                'wi.id',
                'wi.warehouse_id',
                'w.name',
                'w.code',
                'wi.inbound_type',
                'wi.invoice_number',
                'wi.delivery_order_number',
                'wi.batch_number',
                'wi.outbound_reference_number',
                'wi.quantity_item',
                'wi.grand_total',
                'wi.received_date',
                'wi.created_at',
                's.name',
                'sw.name',
                'sr.return_number',
                'return_store.name'
            )
            ->orderByRaw('COALESCE(wi.received_date, wi.created_at) DESC')
            ->orderByDesc('total_qty');
    }

    private function summary(string $q, ?string $warehouseId, ?string $supplierId, ?string $from = null, ?string $to = null): object
    {
        $query = $this->baseQuery($q, $warehouseId, null, $supplierId, false, $from, $to)->reorder();

        $summary = DB::query()
            ->fromSub($query, 'inbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_inbounds,
                COUNT(*) FILTER (WHERE received_date IS NULL)::int as pending_inbounds,
                COUNT(*) FILTER (WHERE received_date IS NOT NULL AND received_date::date = CURRENT_DATE)::int as received_today,
                COALESCE(SUM(total_qty), 0)::int as total_qty
            ')
            ->first();

        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $warehouseId, null, $supplierId, false, $from, $to)->reorder(), 'inbound_rows')
            ->pluck('id')
            ->all();

        $summary->total_sku = empty($documentIds)
            ? 0
            : (int) DB::table('warehouse_inbound_details')
                ->whereIn('warehouse_inbound_id', $documentIds)
                ->distinct('product_id')
                ->count('product_id');

        return $summary;
    }

    private function skuBreakdown(string $q, ?string $warehouseId, ?string $supplierId, ?string $from = null, ?string $to = null): array
    {
        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $warehouseId, null, $supplierId, false, $from, $to)->reorder(), 'inbound_rows')
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

        return DB::table('warehouse_inbound_details as wid')
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->whereIn('wid.warehouse_inbound_id', $inboundIds)
            ->selectRaw('
                wid.warehouse_inbound_id,
                p.code as product_code,
                p.name as product_name,
                wid.quantity,
                wid.expired_date
            ')
            ->orderBy('p.name')
            ->get()
            ->groupBy('warehouse_inbound_id');
    }
}
