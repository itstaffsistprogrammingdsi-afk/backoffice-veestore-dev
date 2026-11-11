<?php

namespace App\Domain\StoreOperations\Actions;

use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipStoreTransfer
{
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

            $scanned = DB::table('store_outbound_details as sod')
                ->join('items as i', 'i.id', '=', 'sod.item_id')
                ->join('store_items as si', function ($join) use ($sourceStoreId): void {
                    $join->on('si.item_id', '=', 'i.id')
                        ->where('si.store_id', '=', $sourceStoreId);
                })
                ->where('sod.store_outbound_id', $outbound->id)
                ->whereNotNull('sod.rfid_tag_id')
                ->where('si.category', 'inventory')
                ->where('i.status', 'store_stock')
                ->get(['sod.item_id', 'i.product_id']);

            if ($scanned->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Barang belum discan di toko asal. Minta toko asal memindai semua barang sebelum pengiriman.',
                ]);
            }

            $detailProductIds = $locked->details
                ->pluck('product_id')
                ->map(fn ($productId): string => (string) $productId)
                ->all();

            $scannedByProduct = $scanned->groupBy(
                fn ($row): string => (string) $row->product_id
            );

            foreach ($scannedByProduct->keys() as $productId) {
                if (! in_array((string) $productId, $detailProductIds, true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Ada barang yang discan tetapi bukan bagian dari produk transfer ini.',
                    ]);
                }
            }

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

                $scannedQty = $scannedByProduct->get((string) $detail->product_id)?->count() ?? 0;

                if ($scannedQty !== $qty) {
                    throw ValidationException::withMessages([
                        'status' => "Scan '{$productName}' belum lengkap. Discan {$scannedQty} dari {$qty} pcs.",
                    ]);
                }

                $totalQty += $qty;
            }

            $itemIds = $scanned->pluck('item_id')
                ->map(fn ($itemId): string => (string) $itemId)
                ->values();

            if ($itemIds->unique()->count() !== $itemIds->count()) {
                throw ValidationException::withMessages([
                    'status' => 'Ada barang yang discan lebih dari sekali. Periksa kembali hasil scan toko asal.',
                ]);
            }

            $now = now();

            DB::table('items')
                ->whereIn('id', $itemIds->all())
                ->update([
                    'status' => 'in_delivery',
                    'updated_at' => $now,
                ]);

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
