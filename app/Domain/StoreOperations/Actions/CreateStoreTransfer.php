<?php

namespace App\Domain\StoreOperations\Actions;

use App\Domain\StoreOperations\Support\StoreTransferStock;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateStoreTransfer
{
    public function __construct(
        private readonly StoreTransferStock $stock
    ) {}

    /**
     * @param  array<int, array{product_id: string, qty: int|string, note?: string|null}>  $items
     */
    public function handle(
        string $sourceStoreId,
        string $destinationStoreId,
        array $items,
        ?string $actorId = null,
        ?string $note = null
    ): StoreOrder {
        if ($sourceStoreId === $destinationStoreId) {
            throw ValidationException::withMessages([
                'destination_store_id' => 'Toko asal dan toko tujuan harus berbeda.',
            ]);
        }

        $stores = DB::table('stores')
            ->whereIn('id', [$sourceStoreId, $destinationStoreId])
            ->whereNull('deleted_at')
            ->pluck('name', 'id');

        if (! $stores->has($sourceStoreId) || ! $stores->has($destinationStoreId)) {
            throw ValidationException::withMessages([
                'source_store_id' => 'Toko asal atau toko tujuan tidak ditemukan.',
            ]);
        }

        $normalized = [];

        foreach ($items as $item) {
            $productId = trim((string) ($item['product_id'] ?? ''));
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId === '' || ! Str::isUuid($productId)) {
                throw ValidationException::withMessages([
                    'items' => 'Produk transfer tidak valid.',
                ]);
            }

            if ($qty <= 0) {
                continue;
            }

            $normalized[$productId] = ($normalized[$productId] ?? 0) + $qty;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal satu produk dengan qty lebih dari nol.',
            ]);
        }

        $products = DB::table('products')
            ->whereIn('id', array_keys($normalized))
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        if ($products->count() !== count($normalized)) {
            throw ValidationException::withMessages([
                'items' => 'Sebagian produk transfer tidak ditemukan atau sudah diarsipkan.',
            ]);
        }

        $availability = $this->stock->availableByProduct($sourceStoreId, array_keys($normalized));

        foreach ($normalized as $productId => $qty) {
            $available = (int) ($availability[$productId] ?? 0);

            if ($qty > $available) {
                $productName = $products[$productId]->name ?? $productId;

                throw ValidationException::withMessages([
                    'items' => "Stok '{$productName}' di toko asal tidak cukup. Diminta {$qty} pcs, tersedia {$available} pcs.",
                ]);
            }
        }

        $prices = collect(array_keys($normalized))
            ->mapWithKeys(function (string $productId) use ($products): array {
                $price = $this->stock->priceForProduct($productId);

                if (! $price) {
                    throw ValidationException::withMessages([
                        'items' => 'Harga produk '.($products[$productId]->name ?? $productId).' belum tersedia.',
                    ]);
                }

                return [$productId => $price];
            });

        return DB::transaction(function () use (
            $sourceStoreId,
            $destinationStoreId,
            $normalized,
            $prices,
            $actorId,
            $note
        ): StoreOrder {
            $transferNumber = $this->nextTransferNumber();
            $orderId = (string) Str::uuid();
            $now = now();

            DB::table('store_orders')->insert([
                'id' => $orderId,
                'order_number' => $transferNumber,
                'store_id' => $destinationStoreId,
                'warehouse_id' => null,
                'requested_at' => $now,
                'approved_at' => null,
                'approved_by' => null,
                'status' => 'requested',
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
                'date' => $now->toDateString(),
                'shipping_number' => $transferNumber,
            ]);

            $details = [];

            foreach ($normalized as $productId => $qty) {
                $price = $prices[$productId];

                $details[] = [
                    'id' => (string) Str::uuid(),
                    'store_order_id' => $orderId,
                    'product_id' => $productId,
                    'product_price_id' => $price->id,
                    'product_price' => $price->price,
                    'requested_qty' => $qty,
                    'approved_qty' => 0,
                    'received_qty' => 0,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('store_order_details')->insert($details);

            DB::table('store_outbounds')->insert([
                'id' => (string) Str::uuid(),
                'store_id' => $sourceStoreId,
                'ecommerce_order_id' => null,
                'store_cart_id' => null,
                'courier_id' => null,
                'payment_id' => null,
                'store_return_id' => null,
                'outbound_type' => 'transfer',
                'shipment_date' => null,
                'item_quantity' => 0,
                'shipping_number' => $transferNumber,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return StoreOrder::query()->findOrFail($orderId);
        });
    }

    private function nextTransferNumber(): string
    {
        $prefix = 'TRF-'.now()->format('Ymd').'-';

        $latest = DB::table('store_orders')
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');

        $running = 1;

        if (is_string($latest) && $latest !== '') {
            $parts = explode('-', $latest);
            $last = end($parts);

            if (is_numeric($last)) {
                $running = ((int) $last) + 1;
            }
        }

        return $prefix.str_pad((string) $running, 3, '0', STR_PAD_LEFT);
    }
}
