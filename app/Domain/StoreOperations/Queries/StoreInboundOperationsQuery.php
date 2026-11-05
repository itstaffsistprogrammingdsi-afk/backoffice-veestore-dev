<?php

namespace App\Domain\StoreOperations\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreInboundOperationsQuery
{
    private const QC_LABELS = [
        'pending' => 'Menunggu QC',
        'partial_passed' => 'Lolos Sebagian',
        'all_passed' => 'Semua Lolos QC',
        'all_failed' => 'Semua Gagal QC',
    ];

    public function build(string $q, ?string $storeId, ?string $warehouseId, ?string $status, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->baseQuery($q, $storeId, $warehouseId, $status, true, $from, $to)
            ->paginate(10)
            ->withQueryString();

        $details = $this->details($rows->getCollection()->pluck('id')->all());

        $rows->getCollection()->transform(function (object $row) use ($details) {
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->qc_checked_items = (int) $row->qc_checked_items;
            $row->qc_pending_items = (int) $row->qc_pending_items;
            $row->qty_good = (int) $row->qty_good;
            $row->qty_bad = (int) $row->qty_bad;
            $row->quantity_item = (int) ($row->quantity_item ?? 0);
            $row->grand_total = (float) ($row->grand_total ?? 0);
            $row->status_label = self::QC_LABELS[$row->qc_status] ?? ($row->qc_status ?: 'Menunggu QC');
            $row->product_details = collect($details->get($row->id, []))
                ->map(fn (object $detail) => [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'qty' => (int) $detail->qty,
                    'qc_checked' => (int) $detail->qc_checked,
                    'qc_pending' => (int) $detail->qc_pending,
                    'qty_good' => (int) $detail->qty_good,
                    'qty_bad' => (int) $detail->qty_bad,
                ])
                ->values()
                ->all();

            return $row;
        });

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $storeId, $warehouseId, $from, $to),
            'skuRows' => $this->skuBreakdown($q, $storeId, $warehouseId, $from, $to),
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'pending', 'label' => 'Menunggu QC'],
                ['value' => 'partial_passed', 'label' => 'Lolos Sebagian'],
                ['value' => 'all_passed', 'label' => 'Semua Lolos QC'],
                ['value' => 'all_failed', 'label' => 'Semua Gagal QC'],
            ],
        ];
    }

    private function baseQuery(string $q, ?string $storeId, ?string $warehouseId, ?string $status, bool $applyStatus = true, ?string $from = null, ?string $to = null)
    {
        $hasUsers = Schema::hasTable('users');

        return DB::table('store_inbounds as si')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'si.warehouse_source_id')
            ->leftJoin('store_orders as sto', 'sto.id', '=', 'si.order_id')
            ->leftJoin('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->leftJoin('items as i', 'i.id', '=', 'sid.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->when($hasUsers, fn ($query) => $query->leftJoin('users as u_received', 'u_received.id', '=', 'si.received_by'))
            ->whereNull('s.deleted_at')
            ->when($storeId, fn ($query) => $query->where('si.store_id', $storeId))
            ->when($warehouseId, fn ($query) => $query->where('si.warehouse_source_id', $warehouseId))
            ->when($applyStatus && $status, fn ($query) => $query->where('si.qc_status', $status))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(si.received_date, si.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(si.received_date, si.created_at)::date <= ?', [$to]))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('si.invoice_number', 'ILIKE', "%{$q}%")
                        ->orWhere('si.delivery_order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('si.note', 'ILIKE', "%{$q}%")
                        ->orWhereRaw('si.order_id::text ILIKE ?', ["%{$q}%"])
                        ->orWhere('sto.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('s.code', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%");
                });
            })
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
                COALESCE(sto.order_number, \'-\') as store_order_number,
                si.qc_status,
                COUNT(DISTINCT sid.id)::int as total_items,
                COUNT(DISTINCT i.product_id)::int as total_sku,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0)::int as qc_checked_items,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 0 ELSE 1 END), 0)::int as qc_pending_items,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'good\' THEN 1 ELSE 0 END), 0)::int as qty_good,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'bad\' THEN 1 ELSE 0 END), 0)::int as qty_bad
            ')
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(u_received.name, '') as received_by_name"), fn ($query) => $query->selectRaw("'' as received_by_name"))
            ->groupBy(
                'si.id', 'si.store_id', 's.name', 's.code', 'si.warehouse_source_id', 'w.name', 'w.code',
                'si.invoice_number', 'si.delivery_order_number', 'si.quantity_item', 'si.grand_total',
                'si.received_date', 'si.created_at', 'si.note', 'si.order_id', 'sto.order_number', 'si.qc_status',
                ...($hasUsers ? ['u_received.name'] : [])
            )
            ->orderByRaw('COALESCE(si.received_date, si.created_at) DESC')
            ->orderByDesc('total_items');
    }

    private function summary(string $q, ?string $storeId, ?string $warehouseId, ?string $from = null, ?string $to = null): object
    {
        $query = $this->baseQuery($q, $storeId, $warehouseId, null, false, $from, $to)->reorder();

        $summary = DB::query()
            ->fromSub($query, 'store_inbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_inbounds,
                COUNT(*) FILTER (WHERE qc_status IS NULL OR qc_status = \'pending\')::int as pending_qc,
                COUNT(*) FILTER (WHERE received_date IS NOT NULL AND received_date::date = CURRENT_DATE)::int as received_today,
                COALESCE(SUM(total_items), 0)::int as total_qty,
                COALESCE(SUM(total_sku), 0)::int as total_sku,
                COALESCE(SUM(quantity_item), 0)::int as total_header_qty,
                COALESCE(SUM(qty_good), 0)::int as total_good,
                COALESCE(SUM(qty_bad), 0)::int as total_bad,
                COALESCE(SUM(grand_total), 0)::numeric as total_value
            ')
            ->first();

        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $storeId, $warehouseId, null, false, $from, $to)->reorder(), 'store_inbound_rows')
            ->pluck('id')
            ->all();

        $summary->total_sku = empty($documentIds)
            ? 0
            : (int) DB::table('store_inbound_details as sid')
                ->join('items as i', 'i.id', '=', 'sid.item_id')
                ->whereIn('sid.store_inbound_id', $documentIds)
                ->distinct('i.product_id')
                ->count('i.product_id');

        return $summary;
    }

    private function details(array $inboundIds)
    {
        if (empty($inboundIds)) {
            return collect();
        }

        return DB::table('store_inbound_details as sid')
            ->join('items as i', 'i.id', '=', 'sid.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('sid.store_inbound_id', $inboundIds)
            ->selectRaw('
                sid.store_inbound_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(sid.id)::int as qty,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 1 ELSE 0 END), 0)::int as qc_checked,
                COALESCE(SUM(CASE WHEN sid.has_been_qc THEN 0 ELSE 1 END), 0)::int as qc_pending,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'good\' THEN 1 ELSE 0 END), 0)::int as qty_good,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(ic.name, \'\')) = \'bad\' THEN 1 ELSE 0 END), 0)::int as qty_bad
            ')
            ->groupBy('sid.store_inbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->groupBy('store_inbound_id');
    }

    private function skuBreakdown(string $q, ?string $storeId, ?string $warehouseId, ?string $from = null, ?string $to = null): array
    {
        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $storeId, $warehouseId, null, false, $from, $to)->reorder(), 'store_inbound_rows')
            ->pluck('id')
            ->all();

        if (empty($documentIds)) {
            return [];
        }

        $query = DB::table('store_inbound_details as sid')
            ->join('store_inbounds as si', 'si.id', '=', 'sid.store_inbound_id')
            ->join('items as i', 'i.id', '=', 'sid.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'si.warehouse_source_id')
            ->whereIn('sid.store_inbound_id', $documentIds);

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
                COUNT(sid.id)::int as qty,
                COUNT(DISTINCT sid.store_inbound_id)::int as document_count,
                MAX(si.received_date) as last_received_at
            ')
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get();

        $documentQuery = DB::table('store_inbound_details as sid')
            ->join('store_inbounds as si', 'si.id', '=', 'sid.store_inbound_id')
            ->join('items as i_doc', 'i_doc.id', '=', 'sid.item_id')
            ->join('products as p_doc', 'p_doc.id', '=', 'i_doc.product_id')
            ->join('stores as s_doc', 's_doc.id', '=', 'si.store_id')
            ->leftJoin('warehouses as w_doc', 'w_doc.id', '=', 'si.warehouse_source_id')
            ->whereIn('sid.store_inbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $documentQuery->where(function ($sub) use ($q) {
                $sub->where('p_doc.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p_doc.name', 'ILIKE', "%{$q}%");
            });
        }

        $documents = $documentQuery
            ->selectRaw('
                i_doc.product_id,
                si.id,
                si.invoice_number,
                si.delivery_order_number,
                si.received_date,
                s_doc.name as store_name,
                COALESCE(w_doc.name, \'-\') as warehouse_source_name
            ')
            ->orderBy('si.received_date')
            ->get()
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'id' => $row->id,
                'reference' => $row->invoice_number ?: $row->delivery_order_number ?: 'Tanpa nomor',
                'type_label' => 'Store Inbound',
                'warehouse_name' => $row->warehouse_source_name,
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
}
