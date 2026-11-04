<?php

namespace App\Domain\WarehouseOperations\Queries;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreTransferReconciliationQuery
{
    public const STATUS_LABELS = [
        'not_recorded' => 'Belum tercatat',
        'partial' => 'Tercatat sebagian',
        'recorded' => 'Sudah tercatat',
    ];

    public const OVERDUE_DAYS = 3;

    public function build(?string $storeId = null, ?string $warehouseId = null, ?string $status = null, int $limit = 25): array
    {
        if (! $this->supported()) {
            return $this->emptyData();
        }

        return [
            'summary' => $this->summary($storeId, $warehouseId),
            'rows' => $this->rows($storeId, $warehouseId, $status, $limit),
            'stores' => $this->storeBreakdown($storeId, $warehouseId),
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'not_recorded', 'label' => self::STATUS_LABELS['not_recorded']],
                ['value' => 'partial', 'label' => self::STATUS_LABELS['partial']],
                ['value' => 'recorded', 'label' => self::STATUS_LABELS['recorded']],
            ],
        ];
    }

    public function summary(?string $storeId = null, ?string $warehouseId = null): array
    {
        if (! $this->supported()) {
            return $this->emptySummary();
        }

        $query = DB::query()->fromSub($this->baseQuery($storeId, $warehouseId), 'transfer');

        $row = $query->selectRaw('
            COUNT(*)::int as total_transfers,
            COUNT(*) FILTER (WHERE transfer.status = \'not_recorded\')::int as not_recorded_count,
            COUNT(*) FILTER (WHERE transfer.status = \'partial\')::int as partial_count,
            COUNT(*) FILTER (WHERE transfer.status = \'recorded\')::int as recorded_count,
            COUNT(DISTINCT transfer.store_id) FILTER (WHERE transfer.status <> \'recorded\')::int as affected_store_count,
            COALESCE(SUM(transfer.outstanding_qty) FILTER (WHERE transfer.status <> \'recorded\'), 0)::int as outstanding_qty,
            COALESCE(MAX(transfer.age_days) FILTER (WHERE transfer.status <> \'recorded\'), 0)::int as oldest_days,
            COUNT(*) FILTER (WHERE transfer.status <> \'recorded\' AND transfer.age_days >= ?)::int as overdue_count
        ', [self::OVERDUE_DAYS])->first();

        $summary = [
            'total_transfers' => (int) ($row->total_transfers ?? 0),
            'not_recorded_count' => (int) ($row->not_recorded_count ?? 0),
            'partial_count' => (int) ($row->partial_count ?? 0),
            'recorded_count' => (int) ($row->recorded_count ?? 0),
            'affected_store_count' => (int) ($row->affected_store_count ?? 0),
            'outstanding_qty' => (int) ($row->outstanding_qty ?? 0),
            'oldest_days' => (int) ($row->oldest_days ?? 0),
            'overdue_count' => (int) ($row->overdue_count ?? 0),
        ];

        $summary['unresolved_count'] = $summary['not_recorded_count'] + $summary['partial_count'];

        return $summary;
    }

    public function rows(?string $storeId = null, ?string $warehouseId = null, ?string $status = null, int $limit = 25): array
    {
        if (! $this->supported()) {
            return [];
        }

        $query = DB::query()
            ->fromSub($this->baseQuery($storeId, $warehouseId), 'transfer')
            ->select('transfer.*');

        $this->applyStatusFilter($query, $status);

        return $query
            ->orderByRaw("CASE transfer.status WHEN 'not_recorded' THEN 0 WHEN 'partial' THEN 1 ELSE 2 END")
            ->orderByDesc('transfer.age_days')
            ->orderBy('transfer.released_at')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (object $row) => $this->mapRow($row))
            ->all();
    }

    public function storeBreakdown(?string $storeId = null, ?string $warehouseId = null): array
    {
        if (! $this->supported()) {
            return [];
        }

        return DB::query()
            ->fromSub($this->baseQuery($storeId, $warehouseId), 'transfer')
            ->where('transfer.status', '<>', 'recorded')
            ->selectRaw('
                transfer.store_id,
                transfer.store_name,
                transfer.store_code,
                COUNT(*)::int as transfer_count,
                COUNT(*) FILTER (WHERE transfer.status = \'not_recorded\')::int as not_recorded_count,
                COALESCE(SUM(transfer.outstanding_qty), 0)::int as outstanding_qty,
                COALESCE(MAX(transfer.age_days), 0)::int as oldest_days
            ')
            ->groupBy('transfer.store_id', 'transfer.store_name', 'transfer.store_code')
            ->orderByDesc('outstanding_qty')
            ->orderByDesc('oldest_days')
            ->limit(10)
            ->get()
            ->map(fn (object $row) => [
                'store_id' => (string) $row->store_id,
                'store_name' => (string) $row->store_name,
                'store_code' => $row->store_code !== null ? (string) $row->store_code : null,
                'transfer_count' => (int) $row->transfer_count,
                'not_recorded_count' => (int) $row->not_recorded_count,
                'outstanding_qty' => (int) $row->outstanding_qty,
                'oldest_days' => (int) $row->oldest_days,
            ])
            ->all();
    }

    private function baseQuery(?string $storeId, ?string $warehouseId): Builder
    {
        $inbound = DB::table('store_inbounds as si')
            ->leftJoin('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->whereNotNull('si.order_id')
            ->selectRaw('
                si.order_id,
                COUNT(sid.id) FILTER (WHERE si.received_date IS NOT NULL)::int as received_qty,
                MIN(si.received_date) as received_at,
                MIN(si.id::text) FILTER (WHERE si.received_date IS NOT NULL) as store_inbound_id
            ')
            ->groupBy('si.order_id');

        $outbound = DB::table('warehouse_outbound_details as wod')
            ->selectRaw('wod.warehouse_outbound_id, COUNT(wod.id)::int as outbound_qty')
            ->groupBy('wod.warehouse_outbound_id');

        $inDelivery = DB::table('warehouse_outbound_details as wod')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->whereRaw("LOWER(i.status::text) = 'in_delivery'")
            ->selectRaw('wod.warehouse_outbound_id, COUNT(wod.id)::int as in_delivery_qty')
            ->groupBy('wod.warehouse_outbound_id');

        return DB::table('warehouse_outbounds as wo')
            ->join('store_orders as so', 'so.id', '=', 'wo.order_id')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoinSub($inbound, 'inbound', 'inbound.order_id', '=', 'so.id')
            ->leftJoinSub($outbound, 'outbound', 'outbound.warehouse_outbound_id', '=', 'wo.id')
            ->leftJoinSub($inDelivery, 'delivery', 'delivery.warehouse_outbound_id', '=', 'wo.id')
            ->whereNull('s.deleted_at')
            ->whereNull('w.deleted_at')
            ->whereNotNull('wo.released_at')
            ->whereRaw("(LOWER(wo.outbound_type::text) = 'store' OR LOWER(wo.order_ref::text) = 'store')")
            ->when($storeId, fn (Builder $query) => $query->where('so.store_id', $storeId))
            ->when($warehouseId, fn (Builder $query) => $query->where('wo.warehouse_id', $warehouseId))
            ->selectRaw('
                wo.id,
                wo.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                wo.order_id as store_order_id,
                so.order_number,
                so.store_id,
                s.name as store_name,
                s.code as store_code,
                wo.invoice_number,
                wo.delivery_order_number,
                wo.shipping_number,
                wo.released_at,
                wo.shipment_date,
                wo.created_at,
                COALESCE(outbound.outbound_qty, 0)::int as outbound_qty,
                COALESCE(inbound.received_qty, 0)::int as received_qty,
                COALESCE(delivery.in_delivery_qty, 0)::int as in_delivery_qty,
                inbound.received_at,
                inbound.store_inbound_id,
                GREATEST(0, COALESCE(outbound.outbound_qty, 0) - COALESCE(inbound.received_qty, 0))::int as outstanding_qty,
                GREATEST(0, FLOOR(EXTRACT(EPOCH FROM (NOW() - COALESCE(wo.released_at, wo.shipment_date, wo.created_at))) / 86400))::int as age_days,
                CASE
                    WHEN COALESCE(inbound.received_qty, 0) <= 0 THEN \'not_recorded\'
                    WHEN COALESCE(inbound.received_qty, 0) >= COALESCE(outbound.outbound_qty, 0)
                        AND COALESCE(delivery.in_delivery_qty, 0) = 0 THEN \'recorded\'
                    ELSE \'partial\'
                END as status
            ');
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status === 'unresolved') {
            $query->whereIn('transfer.status', ['not_recorded', 'partial']);

            return;
        }

        if (in_array($status, array_keys(self::STATUS_LABELS), true)) {
            $query->where('transfer.status', $status);
        }
    }

    private function mapRow(object $row): array
    {
        $status = (string) $row->status;

        return [
            'id' => (string) $row->id,
            'store_order_id' => (string) $row->store_order_id,
            'order_number' => (string) ($row->order_number ?? '-'),
            'store_id' => (string) $row->store_id,
            'store_name' => (string) $row->store_name,
            'store_code' => $row->store_code !== null ? (string) $row->store_code : null,
            'warehouse_id' => (string) $row->warehouse_id,
            'warehouse_name' => (string) $row->warehouse_name,
            'warehouse_code' => $row->warehouse_code !== null ? (string) $row->warehouse_code : null,
            'invoice_number' => $row->invoice_number !== null ? (string) $row->invoice_number : null,
            'delivery_order_number' => $row->delivery_order_number !== null ? (string) $row->delivery_order_number : null,
            'shipping_number' => $row->shipping_number !== null ? (string) $row->shipping_number : null,
            'store_inbound_id' => $row->store_inbound_id !== null ? (string) $row->store_inbound_id : null,
            'released_at' => $row->released_at,
            'shipment_date' => $row->shipment_date,
            'received_at' => $row->received_at,
            'outbound_qty' => (int) $row->outbound_qty,
            'received_qty' => (int) $row->received_qty,
            'in_delivery_qty' => (int) $row->in_delivery_qty,
            'outstanding_qty' => (int) $row->outstanding_qty,
            'age_days' => (int) $row->age_days,
            'is_overdue' => (int) $row->age_days >= self::OVERDUE_DAYS,
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? $status,
            'event_date' => $row->released_at ?? $row->shipment_date ?? $row->created_at,
        ];
    }

    private function supported(): bool
    {
        foreach ([
            'warehouse_outbounds',
            'warehouse_outbound_details',
            'store_orders',
            'stores',
            'warehouses',
            'store_inbounds',
            'store_inbound_details',
            'items',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function emptySummary(): array
    {
        return [
            'total_transfers' => 0,
            'not_recorded_count' => 0,
            'partial_count' => 0,
            'recorded_count' => 0,
            'unresolved_count' => 0,
            'affected_store_count' => 0,
            'outstanding_qty' => 0,
            'oldest_days' => 0,
            'overdue_count' => 0,
        ];
    }

    private function emptyData(): array
    {
        return [
            'summary' => $this->emptySummary(),
            'rows' => [],
            'stores' => [],
            'statusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'not_recorded', 'label' => self::STATUS_LABELS['not_recorded']],
                ['value' => 'partial', 'label' => self::STATUS_LABELS['partial']],
                ['value' => 'recorded', 'label' => self::STATUS_LABELS['recorded']],
            ],
        ];
    }
}
