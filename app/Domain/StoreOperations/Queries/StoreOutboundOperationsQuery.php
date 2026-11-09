<?php

namespace App\Domain\StoreOperations\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreOutboundOperationsQuery
{
    private const TYPE_LABELS = [
        'store' => 'Store',
        'ecommerce' => 'Ecommerce',
        'return' => 'Retur',
        'transfer' => 'Transfer',
    ];

    public function build(string $q, ?string $storeId, ?string $channel, ?string $status, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->baseQuery($q, $storeId, $channel, $status, true, $from, $to)
            ->paginate(10)
            ->withQueryString();

        $details = $this->details($rows->getCollection()->pluck('id')->all());

        $rows->getCollection()->transform(function (object $row) use ($details) {
            $row->total_items = (int) $row->total_items;
            $row->total_sku = (int) $row->total_sku;
            $row->total_rfid = (int) $row->total_rfid;
            $row->item_quantity = (int) ($row->item_quantity ?? 0);
            $row->outbound_type_label = self::TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: 'Lainnya');
            $row->event_date = $row->shipment_date ?? $row->created_at;
            $row->requires_shipping = $row->outbound_type === 'ecommerce' || $row->source_label === 'Ecommerce';
            $row->has_shipping = $row->shipping_number !== '-';
            $row->status = $row->requires_shipping && ! $row->has_shipping ? 'missing_shipping' : 'completed';
            $row->status_label = $row->status === 'missing_shipping' ? 'Belum Ada Resi' : 'Selesai';
            $row->nota_print_count = (int) ($row->nota_print_count ?? 0);
            $row->nota_printed = $row->nota_print_count > 0;
            $row->nota_print_method = $row->outbound_type === 'ecommerce' ? 'email' : 'direct';
            $row->nota_print_method_label = $row->nota_print_method === 'email' ? 'Via Email' : 'Langsung';
            $row->product_details = collect($details->get($row->id, []))
                ->map(fn (object $detail) => [
                    'product_code' => $detail->product_code,
                    'product_name' => $detail->product_name,
                    'qty' => (int) $detail->qty,
                ])
                ->values()
                ->all();

            return $row;
        });

        $documentIds = DB::query()
            ->fromSub($this->baseQuery($q, $storeId, $channel, null, false, $from, $to)->reorder(), 'store_outbound_rows')
            ->pluck('id')
            ->all();

        $skuBreakdown = $this->skuBreakdown($documentIds, $q);

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $storeId, $channel, $from, $to),
            'movementSummary' => $skuBreakdown['summary'],
            'skuRows' => $skuBreakdown['rows'],
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'missing_shipping', 'label' => 'Belum Ada Resi (Ecommerce)'],
                ['value' => 'completed', 'label' => 'Selesai'],
            ],
        ];
    }

    private function baseQuery(string $q, ?string $storeId, ?string $channel, ?string $status, bool $applyStatus = true, ?string $from = null, ?string $to = null)
    {
        $hasCouriers = Schema::hasTable('couriers');
        $hasEcommerceCourier = Schema::hasTable('ecommerce_order_shipping_methods');

        $courierSelect = match (true) {
            $hasCouriers && $hasEcommerceCourier => "COALESCE(NULLIF(TRIM(c.name), ''), NULLIF(TRIM(sm.courier_name), ''), '') as courier_name",
            $hasCouriers => "COALESCE(c.name, '') as courier_name",
            $hasEcommerceCourier => "COALESCE(NULLIF(TRIM(sm.courier_name), ''), '') as courier_name",
            default => "'' as courier_name",
        };

        return DB::table('store_outbounds as so')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->leftJoin('store_outbound_details as sod', 'sod.store_outbound_id', '=', 'so.id')
            ->leftJoin('items as i', 'i.id', '=', 'sod.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('ecommerce_orders as eo', 'eo.id', '=', 'so.ecommerce_order_id')
            ->when($hasEcommerceCourier, fn ($query) => $query->leftJoin('ecommerce_order_shipping_methods as sm', 'sm.id', '=', 'eo.shipping_method_id'))
            ->leftJoin('payments as pay', 'pay.id', '=', 'so.payment_id')
            ->leftJoin('store_returns as sr', 'sr.id', '=', 'so.store_return_id')
            ->when($hasCouriers, fn ($query) => $query->leftJoin('couriers as c', 'c.id', '=', 'so.courier_id'))
            ->whereNull('s.deleted_at')
            ->where(function ($query): void {
                $query->whereRaw("COALESCE(so.outbound_type::text, '') <> 'transfer'")
                    ->orWhereNotNull('so.shipment_date');
            })
            ->when($storeId, fn ($query) => $query->where('so.store_id', $storeId))
            ->when($channel, fn ($query) => $query->where('so.outbound_type', $channel))
            ->when($applyStatus && $status === 'missing_shipping', fn ($query) => $query
                ->whereRaw("(so.ecommerce_order_id IS NOT NULL OR COALESCE(so.outbound_type::text, '') = 'ecommerce')")
                ->whereRaw("NULLIF(BTRIM(COALESCE(so.shipping_number, eo.shipping_number, '')), '') IS NULL"))
            ->when($applyStatus && $status === 'completed', fn ($query) => $query
                ->whereRaw("NOT ((so.ecommerce_order_id IS NOT NULL OR COALESCE(so.outbound_type::text, '') = 'ecommerce') AND NULLIF(BTRIM(COALESCE(so.shipping_number, eo.shipping_number, '')), '') IS NULL)"))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(so.shipment_date, so.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(so.shipment_date, so.created_at)::date <= ?', [$to]))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('so.shipping_number', 'ILIKE', "%{$q}%")
                        ->orWhere('eo.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('pay.transaction_number', 'ILIKE', "%{$q}%")
                        ->orWhere('sr.return_number', 'ILIKE', "%{$q}%")
                        ->orWhere('so.outbound_type', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('s.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                so.id,
                so.store_id,
                s.name as store_name,
                s.code as store_code,
                so.outbound_type,
                so.shipment_date,
                so.created_at,
                so.item_quantity,
                COALESCE(NULLIF(BTRIM(so.shipping_number), \'\'), NULLIF(BTRIM(eo.shipping_number), \'\'), \'-\') as shipping_number,
                COALESCE(eo.order_number, \'-\') as ecommerce_order_number,
                COALESCE(pay.transaction_number, \'-\') as payment_number,
                COALESCE(pay.print_count, 0)::int as nota_print_count,
                COALESCE(sr.return_number, \'-\') as store_return_number,
                CASE
                    WHEN so.ecommerce_order_id IS NOT NULL THEN \'Ecommerce\'
                    WHEN so.store_cart_id IS NOT NULL THEN \'Store Cart\'
                    WHEN so.store_return_id IS NOT NULL THEN \'Store Return\'
                    ELSE \'Lainnya\'
                END as source_label,
                COUNT(DISTINCT sod.id)::int as total_items,
                COUNT(DISTINCT i.product_id)::int as total_sku,
                COUNT(DISTINCT sod.rfid_tag_id)::int as total_rfid
            ')
            ->selectRaw($courierSelect)
            ->groupBy(
                'so.id', 'so.store_id', 's.name', 's.code', 'so.outbound_type', 'so.shipment_date',
                'so.created_at', 'so.item_quantity', 'so.shipping_number', 'eo.shipping_number', 'eo.order_number',
                'pay.transaction_number', 'pay.print_count', 'sr.return_number', 'so.ecommerce_order_id', 'so.store_cart_id', 'so.store_return_id',
                ...($hasCouriers ? ['c.name'] : []),
                ...($hasEcommerceCourier ? ['sm.courier_name'] : [])
            )
            ->orderByRaw('COALESCE(so.shipment_date, so.created_at) DESC')
            ->orderByDesc('total_items');
    }

    private function summary(string $q, ?string $storeId, ?string $channel, ?string $from = null, ?string $to = null): object
    {
        $query = $this->baseQuery($q, $storeId, $channel, null, false, $from, $to)->reorder();

        return DB::query()
            ->fromSub($query, 'store_outbound_rows')
            ->selectRaw('
                COUNT(*)::int as total_outbounds,
                COALESCE(SUM(total_items), 0)::int as total_qty,
                COALESCE(SUM(total_sku), 0)::int as total_sku,
                COALESCE(SUM(CASE WHEN outbound_type = \'store\' THEN total_items ELSE 0 END), 0)::int as store_qty,
                COALESCE(SUM(CASE WHEN outbound_type = \'ecommerce\' THEN total_items ELSE 0 END), 0)::int as ecommerce_qty,
                COUNT(*) FILTER (WHERE (outbound_type::text = \'ecommerce\' OR source_label = \'Ecommerce\') AND shipping_number = \'-\')::int as missing_shipping,
                COALESCE(SUM(total_rfid), 0)::int as total_rfid,
                COUNT(*) FILTER (WHERE source_label = \'Ecommerce\')::int as total_ecommerce,
                COUNT(*) FILTER (WHERE source_label = \'Store Return\')::int as total_returns
            ')
            ->first();
    }

    private function details(array $outboundIds)
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
            ->selectRaw('
                sod.store_outbound_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(sod.id)::int as qty,
                COUNT(DISTINCT sod.rfid_tag_id)::int as rfid_count,
                COALESCE(STRING_AGG(DISTINCT NULLIF(COALESCE(rt.serial_number::text, rt.value), \'\'), \', \'), \'-\') as rfid_summary
            ')
            ->when($hasUsers, fn ($query) => $query->selectRaw("COALESCE(STRING_AGG(DISTINCT NULLIF(u.name, ''), ', '), '-') as released_by_summary"), fn ($query) => $query->selectRaw("'-' as released_by_summary"))
            ->groupBy('sod.store_outbound_id', 'p.id', 'p.code', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->groupBy('store_outbound_id');
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

        $query = DB::table('store_outbound_details as sod')
            ->join('store_outbounds as so', 'so.id', '=', 'sod.store_outbound_id')
            ->join('items as i', 'i.id', '=', 'sod.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereIn('sod.store_outbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p.name', 'ILIKE', "%{$q}%");
            });
        }

        $summaryRow = (clone $query)
            ->selectRaw("
                COUNT(sod.id)::int as total_qty,
                COUNT(DISTINCT i.product_id)::int as total_sku,
                SUM(CASE WHEN so.outbound_type = 'store' THEN 1 ELSE 0 END)::int as store_qty,
                SUM(CASE WHEN so.outbound_type = 'ecommerce' THEN 1 ELSE 0 END)::int as ecommerce_qty,
                SUM(CASE WHEN so.outbound_type NOT IN ('store', 'ecommerce') THEN 1 ELSE 0 END)::int as other_qty
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
                COUNT(sod.id)::int as qty,
                COUNT(DISTINCT sod.store_outbound_id)::int as document_count,
                SUM(CASE WHEN so.outbound_type = 'store' THEN 1 ELSE 0 END)::int as store_qty,
                SUM(CASE WHEN so.outbound_type = 'ecommerce' THEN 1 ELSE 0 END)::int as ecommerce_qty,
                SUM(CASE WHEN so.outbound_type NOT IN ('store', 'ecommerce') THEN 1 ELSE 0 END)::int as other_qty
            ")
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get();

        $documentQuery = DB::table('store_outbound_details as sod')
            ->join('store_outbounds as so', 'so.id', '=', 'sod.store_outbound_id')
            ->join('items as i_doc', 'i_doc.id', '=', 'sod.item_id')
            ->join('products as p_doc', 'p_doc.id', '=', 'i_doc.product_id')
            ->join('stores as s_doc', 's_doc.id', '=', 'so.store_id')
            ->whereIn('sod.store_outbound_id', $documentIds);

        if ($this->qMatchesProduct($q)) {
            $documentQuery->where(function ($sub) use ($q) {
                $sub->where('p_doc.code', 'ILIKE', "%{$q}%")
                    ->orWhere('p_doc.name', 'ILIKE', "%{$q}%");
            });
        }

        $documents = $documentQuery
            ->selectRaw('
                i_doc.product_id,
                so.id,
                so.shipping_number,
                so.outbound_type,
                so.shipment_date,
                so.created_at,
                s_doc.name as store_name
            ')
            ->orderBy('so.created_at')
            ->get()
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'id' => $row->id,
                'reference' => $row->shipping_number ?: 'Tanpa resi',
                'type_label' => self::TYPE_LABELS[$row->outbound_type] ?? ($row->outbound_type ?: 'Lainnya'),
                'warehouse_name' => $row->store_name,
                'shipping_number' => $row->shipping_number,
                'date' => $row->shipment_date ?? $row->created_at,
            ])
            ->groupBy('product_id')
            ->map(fn ($documents) => $documents->unique('id')->values()->all());

        return [
            'summary' => $summary,
            'rows' => $rows
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
                ->all(),
        ];
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
