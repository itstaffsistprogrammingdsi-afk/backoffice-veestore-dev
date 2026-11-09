<?php

namespace App\Domain\StoreOperations\Actions;

use App\Domain\StoreOperations\Support\StoreTransferStock;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipStoreTransfer
{
    public function __construct(
        private readonly StoreTransferStock $stock
    ) {}

    public function handle(StoreOrder $order, ?string $actorId = null): StoreOrder
    {
        return DB::transaction(function () use ($order, $actorId): StoreOrder {
            $locked = StoreOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower((string) $locked->status) !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Transfer harus disetujui terlebih dahulu sebelum dikirim.',
                ]);
            }

            $locked->load('details');

            if ($locked->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Transfer tidak memiliki detail produk.',
                ]);
            }

            $outbound = DB::table('store_outbounds')
                ->where('shipping_number', $locked->shipping_number)
                ->where('outbound_type', 'transfer')
                ->lockForUpdate()
                ->first();

            if (! $outbound) {
                throw ValidationException::withMessages([
                    'status' => 'Dokumen pengiriman transfer tidak ditemukan.',
                ]);
            }

            $sourceStoreId = (string) $outbound->store_id;
            $now = now();
            $detailRows = [];
            $itemRows = [];
            $totalQty = 0;

            foreach ($locked->details as $detail) {
                $qty = (int) ($detail->approved_qty ?: $detail->requested_qty);
                $productName = DB::table('products')->where('id', $detail->product_id)->value('name')
                    ?? (string) $detail->product_id;

                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        'status' => "Qty transfer untuk produk '{$productName}' belum diisi.",
                    ]);
                }

                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                    "store-transfer:{$sourceStoreId}:{$detail->product_id}",
                ]);

                $items = $this->stock->availableItems($sourceStoreId, (string) $detail->product_id, $qty);

                if ($items->count() < $qty) {
                    throw ValidationException::withMessages([
                        'status' => "Stok '{$productName}' di toko asal tidak cukup. Dibutuhkan {$qty} pcs, tersedia {$items->count()} pcs.",
                    ]);
                }

                foreach ($items as $item) {
                    $itemRows[] = (string) $item->id;

                    $detailRows[] = [
                        'id' => (string) Str::uuid(),
                        'store_outbound_id' => (string) $outbound->id,
                        'item_id' => (string) $item->id,
                        'rfid_tag_id' => $item->rfid_tag_id,
                        'rfid_releaser_user_id' => $actorId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $totalQty += $qty;

                $detail->forceFill([
                    'approved_qty' => $qty,
                ])->save();
            }

            DB::table('store_outbound_details')->insert($detailRows);

            if ($itemRows !== []) {
                DB::table('items')
                    ->whereIn('id', array_values(array_unique($itemRows)))
                    ->update([
                        'status' => 'in_delivery',
                        'updated_at' => $now,
                    ]);
            }

            DB::table('store_outbounds')
                ->where('id', $outbound->id)
                ->update([
                    'shipment_date' => $now,
                    'item_quantity' => $totalQty,
                    'updated_at' => $now,
                ]);

            $locked->forceFill([
                'status' => 'shipped',
                'approved_at' => $locked->approved_at ?? $now,
                'approved_by' => $locked->approved_by ?? $actorId,
            ])->save();

            return $locked->fresh(['details']);
        });
    }
}
