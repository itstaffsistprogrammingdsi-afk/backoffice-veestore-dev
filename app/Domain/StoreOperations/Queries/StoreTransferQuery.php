<?php

namespace App\Domain\StoreOperations\Queries;

use Illuminate\Support\Facades\DB;

class StoreTransferQuery
{
    public const ORDER_PREFIX = 'TRF-';

    public const ACTIVE_STATUSES = ['requested', 'approved', 'shipped', 'partial_received'];

    public const STATUS_LABELS = [
        'requested' => 'Menunggu persetujuan',
        'approved' => 'Disetujui',
        'shipped' => 'Dalam perjalanan',
        'partial_received' => 'Diterima sebagian',
        'received' => 'Selesai',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    public function build(
        string $q,
        ?string $sourceStoreId,
        ?string $destinationStoreId,
        ?string $status,
        ?string $from,
        ?string $to,
        int $perPage = 10
    ): array {
        $base = $this->baseQuery($q, $sourceStoreId, $destinationStoreId, $status, $from, $to);

        $rows = (clone $base)
            ->orderByDesc('sto.created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (object $row): object => $this->transformRow($row));

        return [
            'rows' => $rows,
            'summary' => $this->summary($q, $sourceStoreId, $destinationStoreId, $from, $to),
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'statusOptions' => collect(self::STATUS_LABELS)
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'filters' => [
                'q' => $q,
                'source_store_id' => $sourceStoreId,
                'destination_store_id' => $destinationStoreId,
                'status' => $status,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage,
            ],
        ];
    }

    public function detail(string $orderId): ?array
    {
        $order = $this->baseQuery('', null, null, null, null, null)
            ->where('sto.id', $orderId)
            ->first();

        if (! $order) {
            return null;
        }

        $details = DB::table('store_order_details as sod')
            ->join('products as p', 'p.id', '=', 'sod.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('sod.store_order_id', $orderId)
            ->orderBy('p.name')
            ->get([
                'sod.id',
                'sod.product_id',
                'sod.product_price_id',
                'sod.product_price',
                'sod.requested_qty',
                'sod.approved_qty',
                'sod.received_qty',
                'sod.note',
                'p.name as product_name',
                'p.code as product_code',
                'u.name as unit_name',
            ])
            ->map(fn (object $detail): array => [
                'id' => (string) $detail->id,
                'product_id' => (string) $detail->product_id,
                'product_name' => (string) $detail->product_name,
                'product_code' => (string) $detail->product_code,
                'unit_name' => $detail->unit_name,
                'product_price' => (float) $detail->product_price,
                'requested_qty' => (int) $detail->requested_qty,
                'approved_qty' => (int) ($detail->approved_qty ?? 0),
                'received_qty' => (int) ($detail->received_qty ?? 0),
                'note' => $detail->note,
            ])
            ->values()
            ->all();

        $items = [];

        if ($order->transfer_outbound_id) {
            $items = DB::table('store_outbound_details as sod')
                ->join('items as i', 'i.id', '=', 'sod.item_id')
                ->join('products as p', 'p.id', '=', 'i.product_id')
                ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'sod.rfid_tag_id')
                ->where('sod.store_outbound_id', $order->transfer_outbound_id)
                ->orderBy('p.name')
                ->get([
                    'sod.id',
                    'p.name as product_name',
                    'p.code as product_code',
                    'i.status as item_status',
                    'rt.serial_number as rfid_serial',
                    'rt.value as rfid_value',
                ])
                ->map(fn (object $item): array => [
                    'id' => (string) $item->id,
                    'product_name' => (string) $item->product_name,
                    'product_code' => (string) $item->product_code,
                    'item_status' => $item->item_status,
                    'rfid_serial' => $item->rfid_serial ?: $item->rfid_value,
                ])
                ->values()
                ->all();
        }

        return [
            'order' => $this->transformRow($order),
            'details' => $details,
            'items' => $items,
        ];
    }

    private function baseQuery(
        string $q,
        ?string $sourceStoreId,
        ?string $destinationStoreId,
        ?string $status,
        ?string $from,
        ?string $to
    ) {
        return DB::table('store_orders as sto')
            ->join('stores as destination', 'destination.id', '=', 'sto.store_id')
            ->leftJoin('store_outbounds as transfer_outbound', function ($join): void {
                $join->on('transfer_outbound.shipping_number', '=', 'sto.shipping_number')
                    ->where('transfer_outbound.outbound_type', '=', 'transfer');
            })
            ->leftJoin('stores as source', 'source.id', '=', 'transfer_outbound.store_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'sto.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'sto.approved_by')
            ->leftJoin('store_order_details as detail', 'detail.store_order_id', '=', 'sto.id')
            ->whereRaw("COALESCE(sto.order_number, '') LIKE ?", [self::ORDER_PREFIX.'%'])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('sto.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('sto.shipping_number', 'ILIKE', "%{$q}%")
                        ->orWhere('source.name', 'ILIKE', "%{$q}%")
                        ->orWhere('source.code', 'ILIKE', "%{$q}%")
                        ->orWhere('destination.name', 'ILIKE', "%{$q}%")
                        ->orWhere('destination.code', 'ILIKE', "%{$q}%")
                        ->orWhereExists(function ($sub) use ($q): void {
                            $sub->selectRaw('1')
                                ->from('store_order_details as search_detail')
                                ->join('products as search_product', 'search_product.id', '=', 'search_detail.product_id')
                                ->whereColumn('search_detail.store_order_id', 'sto.id')
                                ->where(function ($productQuery) use ($q): void {
                                    $productQuery->where('search_product.name', 'ILIKE', "%{$q}%")
                                        ->orWhere('search_product.code', 'ILIKE', "%{$q}%");
                                });
                        });
                });
            })
            ->when($sourceStoreId, fn ($query) => $query->where('transfer_outbound.store_id', $sourceStoreId))
            ->when($destinationStoreId, fn ($query) => $query->where('sto.store_id', $destinationStoreId))
            ->when($status, fn ($query) => $query->where('sto.status', $status))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(sto.requested_at, sto.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(sto.requested_at, sto.created_at)::date <= ?', [$to]))
            ->selectRaw('
                sto.id,
                sto.order_number,
                sto.status,
                sto.date,
                sto.requested_at,
                sto.approved_at,
                sto.shipping_number,
                sto.created_at,
                sto.updated_at,
                destination.id as destination_store_id,
                destination.name as destination_store_name,
                destination.code as destination_store_code,
                source.id as source_store_id,
                source.name as source_store_name,
                source.code as source_store_code,
                creator.name as created_by_name,
                approver.name as approved_by_name,
                transfer_outbound.id as transfer_outbound_id,
                transfer_outbound.shipment_date,
                transfer_outbound.item_quantity,
                COUNT(DISTINCT detail.id)::int as total_products,
                COALESCE(SUM(detail.requested_qty), 0)::int as requested_qty,
                COALESCE(SUM(detail.approved_qty), 0)::int as approved_qty,
                COALESCE(SUM(detail.received_qty), 0)::int as received_qty
            ')
            ->groupBy(
                'sto.id', 'sto.order_number', 'sto.status', 'sto.date', 'sto.requested_at',
                'sto.approved_at', 'sto.shipping_number', 'sto.created_at', 'sto.updated_at',
                'destination.id', 'destination.name', 'destination.code',
                'source.id', 'source.name', 'source.code',
                'creator.name', 'approver.name',
                'transfer_outbound.id', 'transfer_outbound.shipment_date', 'transfer_outbound.item_quantity'
            );
    }

    private function summary(
        string $q,
        ?string $sourceStoreId,
        ?string $destinationStoreId,
        ?string $from,
        ?string $to
    ): object {
        $query = $this->baseQuery($q, $sourceStoreId, $destinationStoreId, null, $from, $to)->reorder();

        return DB::query()
            ->fromSub($query, 'transfer_rows')
            ->selectRaw("
                COUNT(*)::int as total_transfers,
                COUNT(*) FILTER (WHERE status = 'requested')::int as waiting_approval,
                COUNT(*) FILTER (WHERE status = 'approved')::int as ready_to_ship,
                COUNT(*) FILTER (WHERE status = 'shipped')::int as in_transit,
                COUNT(*) FILTER (WHERE status = 'partial_received')::int as partial_received,
                COUNT(*) FILTER (WHERE status = 'received')::int as completed,
                COUNT(*) FILTER (WHERE status IN ('rejected', 'cancelled'))::int as cancelled,
                COALESCE(SUM(requested_qty), 0)::int as requested_qty,
                COALESCE(SUM(approved_qty), 0)::int as approved_qty,
                COALESCE(SUM(received_qty), 0)::int as received_qty,
                COALESCE(SUM(GREATEST(approved_qty - received_qty, 0)), 0)::int as outstanding_qty
            ")
            ->first();
    }

    private function transformRow(object $row): object
    {
        $status = strtolower((string) $row->status);
        $requestedQty = (int) ($row->requested_qty ?? 0);
        $approvedQty = (int) ($row->approved_qty ?? 0);
        $receivedQty = (int) ($row->received_qty ?? 0);

        $row->status = $status;
        $row->status_label = self::STATUS_LABELS[$status] ?? ($status !== '' ? ucfirst($status) : 'Tidak diketahui');
        $row->total_products = (int) ($row->total_products ?? 0);
        $row->requested_qty = $requestedQty;
        $row->approved_qty = $approvedQty;
        $row->received_qty = $receivedQty;
        $row->outstanding_qty = max($approvedQty - $receivedQty, 0);
        $row->progress_percent = $approvedQty > 0
            ? min(100, (int) round(($receivedQty / $approvedQty) * 100))
            : 0;
        $row->can_approve = $status === 'requested';
        $row->can_ship = $status === 'approved';
        $row->can_receive = in_array($status, ['shipped', 'partial_received'], true);
        $row->can_reject = $status === 'requested';
        $row->can_cancel = $status === 'approved';

        return $row;
    }
}
