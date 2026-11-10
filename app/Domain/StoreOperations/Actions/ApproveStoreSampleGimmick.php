<?php

namespace App\Domain\StoreOperations\Actions;

use App\Domain\StoreOperations\Support\SampleGimmickRequestStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveStoreSampleGimmick
{
    public function handle(
        string $requestId,
        ?string $note,
        ?string $actorId,
        ?string $actorName
    ): array {
        return DB::transaction(function () use ($requestId, $note, $actorId, $actorName): array {
            $row = DB::table('app_settings')
                ->where('key', SampleGimmickRequestStore::requestKey($requestId))
                ->lockForUpdate()
                ->first(['key', 'value']);

            if (! $row) {
                throw ValidationException::withMessages([
                    'request' => 'Pengajuan sample & gimmick tidak ditemukan.',
                ]);
            }

            $request = SampleGimmickRequestStore::decode($row->value);

            if (($request['status'] ?? null) !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Pengajuan ini sudah diproses dan tidak dapat disetujui lagi.',
                ]);
            }

            $type = (string) ($request['type'] ?? '');
            $storeId = (string) ($request['store_id'] ?? '');
            $items = is_array($request['items'] ?? null) ? $request['items'] : [];

            if ($type === 'sample') {
                $this->markStoreItems($items, $storeId, $request, $actorName, 'sample');
                $message = 'Item sample ditandai dan pengajuan disetujui.';
            } elseif ($type === 'gimmick') {
                $receiptItems = array_values(array_filter(
                    $items,
                    fn ($item): bool => is_array($item) && ! empty($item['cart_detail_id'])
                ));
                $scanItems = array_values(array_filter(
                    $items,
                    fn ($item): bool => is_array($item)
                        && empty($item['cart_detail_id'])
                        && ! empty($item['store_item_id'])
                ));

                if ($receiptItems !== []) {
                    $this->approveGimmickReceipt($receiptItems, $storeId);
                }

                if ($scanItems !== []) {
                    $this->markStoreItems($scanItems, $storeId, $request, $actorName, 'gimmick');
                }

                if ($receiptItems !== [] && $scanItems !== []) {
                    $message = 'Item gimmick dari nota dikecualikan dari omset dan item scan ditandai gimmick.';
                } elseif ($receiptItems !== []) {
                    $message = 'Item gimmick dikecualikan dari omset dan pengajuan disetujui.';
                } elseif ($scanItems !== []) {
                    $message = 'Item gimmick scan ditandai dan pengajuan disetujui.';
                } else {
                    throw ValidationException::withMessages([
                        'items' => 'Pengajuan gimmick tidak memiliki item yang valid.',
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'request' => 'Jenis pengajuan tidak dikenali.',
                ]);
            }

            $reviewedAt = now()->toIso8601String();
            $history = is_array($request['history'] ?? null) ? $request['history'] : [];
            $history[] = [
                'action' => 'approved',
                'at' => $reviewedAt,
                'by' => $actorId,
                'by_name' => $actorName,
                'note' => $note,
            ];

            $request['status'] = 'approved';
            $request['reviewed_by'] = $actorId;
            $request['reviewed_by_name'] = $actorName;
            $request['reviewed_at'] = $reviewedAt;
            $request['review_note'] = $note;
            $request['rejection_reason'] = null;
            $request['history'] = $history;

            DB::table('app_settings')
                ->where('key', $row->key)
                ->update([
                    'value' => SampleGimmickRequestStore::encode($request),
                    'updated_at' => now(),
                ]);

            $this->releaseReservations($request, $requestId);

            return [
                'request_number' => (string) ($request['request_number'] ?? $requestId),
                'type' => $type,
                'message' => $message,
                'item_count' => count($items),
            ];
        });
    }

    private function markStoreItems(array $items, string $storeId, array $request, ?string $actorName, string $markerType): void
    {
        $storeItemIds = $this->uniqueValues($items, 'store_item_id');

        if ($storeItemIds === []) {
            throw ValidationException::withMessages([
                'items' => 'Pengajuan tidak memiliki item toko yang valid.',
            ]);
        }

        $validIds = DB::table('store_items as store_item')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->whereIn('store_item.id', $storeItemIds)
            ->where('store_item.store_id', $storeId)
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->pluck('store_item.id')
            ->all();

        $invalidIds = array_values(array_diff($storeItemIds, $validIds));

        if ($invalidIds !== []) {
            throw ValidationException::withMessages([
                'items' => 'Sebagian item sudah tidak berada di stok toko. Minta toko mengajukan ulang.',
            ]);
        }

        $markedAt = now()->toIso8601String();

        foreach ($storeItemIds as $storeItemId) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => SampleGimmickRequestStore::SAMPLE_MARKER_PREFIX.$storeItemId],
                [
                    'value' => SampleGimmickRequestStore::encode([
                        'type' => $markerType,
                        'marked_at' => $markedAt,
                        'marked_by' => $actorName,
                        'request_id' => $request['id'] ?? null,
                        'request_number' => $request['request_number'] ?? null,
                    ]),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function approveGimmickReceipt(array $items, string $storeId): void
    {
        $cartDetailIds = $this->uniqueValues($items, 'cart_detail_id');

        if ($cartDetailIds === []) {
            throw ValidationException::withMessages([
                'items' => 'Pengajuan gimmick tidak memiliki item yang valid.',
            ]);
        }

        $validIds = DB::table('store_cart_details as cart_detail')
            ->join('store_carts as cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->whereIn('cart_detail.id', $cartDetailIds)
            ->where('cart.store_id', $storeId)
            ->pluck('cart_detail.id')
            ->all();

        $invalidIds = array_values(array_diff($cartDetailIds, $validIds));

        if ($invalidIds !== []) {
            throw ValidationException::withMessages([
                'items' => 'Sebagian item gimmick tidak lagi terhubung ke transaksi toko ini.',
            ]);
        }

        DB::table('store_cart_details')
            ->whereIn('id', $validIds)
            ->where(function ($query): void {
                $query->whereNull('void_status')
                    ->orWhere('void_status', '!=', 'voided');
            })
            ->update([
                'void_status' => 'gimmick',
                'updated_at' => now(),
            ]);
    }

    private function releaseReservations(array $request, string $requestId): void
    {
        $keys = SampleGimmickRequestStore::reservationKeys($request);

        if ($keys === []) {
            return;
        }

        DB::table('app_settings')
            ->whereIn('key', $keys)
            ->whereRaw("value->>'request_id' = ?", [$requestId])
            ->delete();
    }

    private function uniqueValues(array $items, string $key): array
    {
        return collect($items)
            ->pluck($key)
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
