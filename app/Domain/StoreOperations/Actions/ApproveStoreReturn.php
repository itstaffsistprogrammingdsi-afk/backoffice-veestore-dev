<?php

namespace App\Domain\StoreOperations\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApproveStoreReturn
{
    /**
     * @param  array<int, array{product_id?: string, qty?: int|string}>  $items
     * @return array{status: string, approved_now: int, remaining: int, return_number: string|null}
     */
    public function handle(
        string $returnId,
        array $items,
        ?string $warehouseId,
        ?string $note,
        ?string $actorId
    ): array {
        return DB::transaction(function () use ($returnId, $items, $warehouseId, $note, $actorId): array {
            $return = DB::table('store_returns')
                ->where('id', $returnId)
                ->lockForUpdate()
                ->first();

            if (! $return) {
                throw ValidationException::withMessages([
                    'items' => 'Data retur tidak ditemukan.',
                ]);
            }

            $status = strtolower((string) $return->status);

            if (! in_array($status, ['requested', 'pending_approval', 'partial_approved'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Retur ini sudah diproses dan tidak dapat disetujui lagi.',
                ]);
            }

            $approvedCount = DB::table('store_return_details')
                ->where('store_return_id', $returnId)
                ->where('status', 'approved')
                ->count();

            $destinationWarehouseId = $approvedCount > 0 && $return->warehouse_id
                ? (string) $return->warehouse_id
                : (string) ($warehouseId ?: $return->warehouse_id);

            if (! Str::isUuid($destinationWarehouseId)) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Pilih gudang tujuan retur terlebih dahulu.',
                ]);
            }

            $warehouseExists = DB::table('warehouses')
                ->where('id', $destinationWarehouseId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $warehouseExists) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Gudang tujuan tidak aktif atau sudah dihapus.',
                ]);
            }

            $quantities = [];

            foreach ($items as $item) {
                $productId = (string) ($item['product_id'] ?? '');
                $qty = (int) ($item['qty'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if (! Str::isUuid($productId)) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk yang dipilih tidak valid.',
                    ]);
                }

                $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
            }

            if ($quantities === []) {
                throw ValidationException::withMessages([
                    'items' => 'Isi qty minimal satu produk untuk disetujui.',
                ]);
            }

            $productNames = DB::table('products')
                ->whereIn('id', array_keys($quantities))
                ->pluck('name', 'id');

            $approvedNow = 0;

            foreach ($quantities as $productId => $qty) {
                $pendingIds = DB::table('store_return_details as d')
                    ->join('items as i', 'i.id', '=', 'd.item_id')
                    ->where('d.store_return_id', $returnId)
                    ->where('i.product_id', $productId)
                    ->where(function ($query): void {
                        $query->whereNull('d.status')->orWhere('d.status', 'in_review');
                    })
                    ->orderBy('d.created_at')
                    ->orderBy('d.id')
                    ->lockForUpdate()
                    ->pluck('d.id')
                    ->all();

                $remaining = count($pendingIds);

                if ($qty > $remaining) {
                    $productName = $productNames[$productId] ?? $productId;

                    throw ValidationException::withMessages([
                        'items' => "Qty retur '{$productName}' melebihi sisa yang menunggu approval ({$remaining}).",
                    ]);
                }

                $detailIds = array_slice($pendingIds, 0, $qty);

                if ($detailIds !== []) {
                    DB::table('store_return_details')
                        ->whereIn('id', $detailIds)
                        ->update([
                            'status' => 'approved',
                            'updated_at' => now(),
                        ]);
                }

                $approvedNow += count($detailIds);
            }

            $remainingTotal = DB::table('store_return_details')
                ->where('store_return_id', $returnId)
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', 'in_review');
                })
                ->count();

            $finalStatus = $remainingTotal === 0 ? 'approved' : 'partial_approved';
            $noteWarehouse = $note === null ? $return->note_warehouse : trim($note);

            DB::table('store_returns')
                ->where('id', $returnId)
                ->update([
                    'status' => $finalStatus,
                    'warehouse_id' => $destinationWarehouseId,
                    'approved_at' => now(),
                    'approved_by' => $actorId,
                    'note_warehouse' => $noteWarehouse !== '' ? $noteWarehouse : null,
                    'rejected_at' => null,
                    'updated_at' => now(),
                ]);

            return [
                'status' => $finalStatus,
                'approved_now' => $approvedNow,
                'remaining' => (int) $remainingTotal,
                'return_number' => $return->return_number,
            ];
        });
    }
}
