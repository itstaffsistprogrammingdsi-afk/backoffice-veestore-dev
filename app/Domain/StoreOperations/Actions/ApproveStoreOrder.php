<?php

namespace App\Domain\StoreOperations\Actions;

use App\Domain\StoreOperations\Support\WarehouseStockAvailability;
use App\Services\StoreOrderReservationService;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApproveStoreOrder
{
    public function __construct(
        private readonly WarehouseStockAvailability $availability,
        private readonly StoreOrderReservationService $reservations
    ) {}

    /**
     * @param  array<int, array{detail_id?: string, warehouse_id?: string, qty?: int|string}>  $allocations
     * @return array{order_ids: array<int, string>, order_numbers: array<int, string>, total_allocated: int, status: string}
     */
    public function handle(StoreOrder $order, array $allocations, ?string $actorId = null): array
    {
        return DB::transaction(function () use ($order, $allocations, $actorId): array {
            $lockedOrder = StoreOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower((string) $lockedOrder->status) !== 'requested') {
                throw ValidationException::withMessages([
                    'allocations' => 'Order ini sudah diproses. Hanya order berstatus menunggu approval yang dapat disetujui.',
                ]);
            }

            $lockedOrder->load('details');

            /*
             * Reservasi yang dibuat saat order masuk dilepas di dalam transaksi.
             * Jika approval gagal, rollback mengembalikan reservasi tersebut.
             */
            $this->reservations->release($lockedOrder, 'Released before approval allocation');

            $details = $lockedOrder->details->keyBy(fn ($detail): string => (string) $detail->id);

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Order tidak memiliki detail produk.',
                ]);
            }

            $warehouseNames = DB::table('warehouses')
                ->whereNull('deleted_at')
                ->pluck('name', 'id');

            $productNames = DB::table('products')
                ->whereIn('id', $details->pluck('product_id')->filter()->unique()->values()->all())
                ->pluck('name', 'id');

            $normalized = [];

            foreach ($allocations as $allocation) {
                $detailId = (string) ($allocation['detail_id'] ?? '');
                $warehouseId = (string) ($allocation['warehouse_id'] ?? '');
                $qty = (int) ($allocation['qty'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if (! $details->has($detailId)) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Detail order yang dipilih tidak valid.',
                    ]);
                }

                if (! $warehouseNames->has($warehouseId)) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Gudang yang dipilih tidak aktif atau sudah dihapus.',
                    ]);
                }

                $normalized[$detailId][$warehouseId] = ($normalized[$detailId][$warehouseId] ?? 0) + $qty;
            }

            if ($normalized === []) {
                throw ValidationException::withMessages([
                    'allocations' => 'Pilih minimal satu gudang dan isi qty yang disetujui.',
                ]);
            }

            foreach ($details as $detail) {
                $requested = (int) ($detail->requested_qty ?? 0);
                $allocated = (int) array_sum($normalized[(string) $detail->id] ?? []);

                if ($allocated > $requested) {
                    $productName = $productNames[$detail->product_id] ?? $detail->product_id;

                    throw ValidationException::withMessages([
                        'allocations' => "Qty untuk produk '{$productName}' melebihi qty yang diminta ({$requested}).",
                    ]);
                }
            }

            $need = [];

            foreach ($details as $detail) {
                foreach ($normalized[(string) $detail->id] ?? [] as $warehouseId => $qty) {
                    $productId = (string) $detail->product_id;
                    $need[$warehouseId][$productId] = ($need[$warehouseId][$productId] ?? 0) + (int) $qty;
                }
            }

            $this->lockStockLines($need);

            $stock = $this->availability->forProducts(
                array_keys($need),
                $details->pluck('product_id')->filter()->unique()->values()->all()
            );

            foreach ($need as $warehouseId => $products) {
                foreach ($products as $productId => $qty) {
                    $available = (int) ($stock[$warehouseId][$productId]['qty_available'] ?? 0);

                    if ($qty > $available) {
                        $productName = $productNames[$productId] ?? $productId;
                        $warehouseName = $warehouseNames[$warehouseId] ?? $warehouseId;

                        throw ValidationException::withMessages([
                            'allocations' => "Stok '{$productName}' di gudang '{$warehouseName}' tidak cukup. Dibutuhkan {$qty} pcs, tersedia {$available} pcs.",
                        ]);
                    }
                }
            }

            $grouped = [];

            foreach ($details as $detail) {
                foreach ($normalized[(string) $detail->id] ?? [] as $warehouseId => $qty) {
                    $grouped[$warehouseId][] = [
                        'detail_id' => (string) $detail->id,
                        'product_id' => (string) $detail->product_id,
                        'product_price_id' => $detail->product_price_id,
                        'product_price' => $detail->product_price,
                        'requested_qty' => (int) ($detail->requested_qty ?? 0),
                        'approved_qty' => (int) $qty,
                        'received_qty' => 0,
                        'note' => $detail->note,
                    ];
                }
            }

            $primaryWarehouseId = (string) array_key_first($grouped);
            $hasPartial = false;

            foreach ($details as $detail) {
                $requested = (int) ($detail->requested_qty ?? 0);
                $allocated = (int) array_sum($normalized[(string) $detail->id] ?? []);

                if ($allocated < $requested) {
                    $hasPartial = true;
                    break;
                }
            }

            $finalStatus = $hasPartial ? 'partial_approved' : 'approved';

            $lockedOrder->forceFill([
                'warehouse_id' => $primaryWarehouseId,
                'status' => $finalStatus,
                'approved_at' => now(),
                'approved_by' => $actorId,
            ])->save();

            foreach ($details as $detail) {
                $primaryQty = (int) ($normalized[(string) $detail->id][$primaryWarehouseId] ?? 0);

                $detail->forceFill([
                    'approved_qty' => $primaryQty,
                ])->save();
            }

            $orderIds = [(string) $lockedOrder->id];
            $orderNumbers = [(string) $lockedOrder->order_number];
            $reservationRows = $this->reservationRows(
                (string) $lockedOrder->id,
                $primaryWarehouseId,
                $grouped[$primaryWarehouseId]
            );

            foreach ($grouped as $warehouseId => $items) {
                if ($warehouseId === $primaryWarehouseId) {
                    continue;
                }

                $childOrderId = (string) Str::uuid();
                $childOrderNumber = $this->generateOrderNumber();

                DB::table('store_orders')->insert([
                    'id' => $childOrderId,
                    'order_number' => $childOrderNumber,
                    'store_id' => $lockedOrder->store_id,
                    'warehouse_id' => $warehouseId,
                    'requested_at' => $lockedOrder->requested_at,
                    'approved_at' => now(),
                    'approved_by' => $actorId,
                    'status' => 'approved',
                    'created_by' => $lockedOrder->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'date' => $lockedOrder->getRawOriginal('date'),
                    'shipping_number' => $lockedOrder->shipping_number,
                ]);

                $detailRows = [];

                foreach ($items as $item) {
                    $detailRows[] = [
                        'id' => (string) Str::uuid(),
                        'store_order_id' => $childOrderId,
                        'product_id' => $item['product_id'],
                        'product_price_id' => $item['product_price_id'],
                        'product_price' => $item['product_price'],
                        'requested_qty' => (int) $item['approved_qty'],
                        'approved_qty' => (int) $item['approved_qty'],
                        'received_qty' => 0,
                        'note' => $item['note'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::table('store_order_details')->insert($detailRows);

                $reservationRows = array_merge(
                    $reservationRows,
                    $this->reservationRows($childOrderId, $warehouseId, $items)
                );

                $orderIds[] = $childOrderId;
                $orderNumbers[] = $childOrderNumber;
            }

            if ($reservationRows !== [] && Schema::hasTable('stock_reservations')) {
                DB::table('stock_reservations')->insert($reservationRows);
            }

            return [
                'order_ids' => $orderIds,
                'order_numbers' => $orderNumbers,
                'total_allocated' => array_sum(array_map(fn (array $items): int => (int) array_sum(array_column($items, 'approved_qty')), $grouped)),
                'status' => $finalStatus,
            ];
        });
    }

    /**
     * @param  array<string, array<string, int>>  $need
     */
    private function lockStockLines(array $need): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        ksort($need);

        foreach ($need as $warehouseId => $products) {
            ksort($products);

            foreach (array_keys($products) as $productId) {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                    "store-order-approval:{$warehouseId}:{$productId}",
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function reservationRows(string $orderId, string $warehouseId, array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (string) $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['approved_qty'];
        }

        $rows = [];

        foreach ($quantities as $productId => $quantity) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'reference_type' => 'store_order',
                'reference_id' => $orderId,
                'handler_type' => 'warehouse',
                'handler_id' => $warehouseId,
                'quantity' => (int) $quantity,
                'status' => 'active',
                'reserved_at' => now(),
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'POS-'.now()->format('Ymd').'-';

        $latestOrderNumber = DB::table('store_orders')
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');

        $runningNumber = 1;

        if (is_string($latestOrderNumber) && $latestOrderNumber !== '') {
            $parts = explode('-', $latestOrderNumber);
            $lastPart = end($parts);

            if (is_numeric($lastPart)) {
                $runningNumber = ((int) $lastPart) + 1;
            }
        }

        return $prefix.str_pad((string) $runningNumber, 3, '0', STR_PAD_LEFT);
    }
}
