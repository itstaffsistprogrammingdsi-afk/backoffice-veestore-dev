<?php

namespace App\Services;

use App\Domain\StoreOperations\Support\WarehouseStockAvailability;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StoreOrderReservationService
{
    public function __construct(
        private readonly WarehouseStockAvailability $availability
    ) {}

    public function reserve(StoreOrder $order): int
    {
        if (! Schema::hasTable('stock_reservations')) {
            return 0;
        }

        if (strtolower((string) $order->status) !== 'requested') {
            return 0;
        }

        $warehouseId = (string) $order->warehouse_id;

        if (! Str::isUuid($warehouseId)) {
            return 0;
        }

        $order->loadMissing('details');

        $requestedByProduct = $order->details
            ->groupBy(fn ($detail): string => (string) $detail->product_id)
            ->map(fn ($details): int => (int) $details->sum(fn ($detail): int => (int) $detail->requested_qty));

        if ($requestedByProduct->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($order, $warehouseId, $requestedByProduct): int {
            $productIds = $requestedByProduct->keys()->map(fn ($id): string => (string) $id)->sort()->values()->all();

            foreach ($productIds as $productId) {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                    "store-order-approval:{$warehouseId}:{$productId}",
                ]);
            }

            $existing = DB::table('stock_reservations')
                ->where('reference_type', 'store_order')
                ->where('reference_id', $order->getKey())
                ->where('status', 'active')
                ->groupBy('product_id')
                ->selectRaw('product_id, COALESCE(SUM(quantity), 0)::int as quantity')
                ->pluck('quantity', 'product_id');

            $stock = $this->availability->forProducts([$warehouseId], $productIds);
            $now = now();
            $rows = [];

            foreach ($productIds as $productId) {
                $requested = (int) ($requestedByProduct[$productId] ?? 0);
                $alreadyReserved = (int) ($existing[$productId] ?? 0);
                $remaining = max(0, $requested - $alreadyReserved);

                if ($remaining === 0) {
                    continue;
                }

                $available = (int) ($stock[$warehouseId][$productId]['qty_available'] ?? 0);
                $quantity = min($remaining, $available);

                if ($quantity <= 0) {
                    continue;
                }

                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'reference_type' => 'store_order',
                    'reference_id' => $order->getKey(),
                    'handler_type' => 'warehouse',
                    'handler_id' => $warehouseId,
                    'quantity' => $quantity,
                    'status' => 'active',
                    'reserved_at' => $now,
                    'expires_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows === []) {
                return 0;
            }

            DB::table('stock_reservations')->insert($rows);

            return array_sum(array_column($rows, 'quantity'));
        });
    }

    public function release(StoreOrder $order, string $reason): int
    {
        if (! Schema::hasTable('stock_reservations')) {
            return 0;
        }

        return DB::table('stock_reservations')
            ->where('reference_type', 'store_order')
            ->where('reference_id', $order->getKey())
            ->where('status', 'active')
            ->update([
                'status' => 'released',
                'released_at' => now(),
                'release_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
