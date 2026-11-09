<?php

namespace App\Domain\StoreOperations\Actions;

use App\Models\StoreOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReceiveStoreTransfer
{
    /**
     * @param  array<string, int|string>  $receivedQty
     * @return array{received: int, status: string}
     */
    public function handle(StoreOrder $order, array $receivedQty = [], ?string $actorId = null): array
    {
        return DB::transaction(function () use ($order, $receivedQty): array {
            $locked = StoreOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(strtolower((string) $locked->status), ['shipped', 'partial_received'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Transfer belum dikirim atau sudah selesai diterima.',
                ]);
            }

            $outbound = DB::table('store_outbounds')
                ->where('shipping_number', $locked->shipping_number)
                ->where('outbound_type', 'transfer')
                ->first();

            if (! $outbound) {
                throw ValidationException::withMessages([
                    'status' => 'Dokumen pengiriman transfer tidak ditemukan.',
                ]);
            }

            $sourceStore = DB::table('stores')->where('id', $outbound->store_id)->first(['id', 'name', 'code']);
            $destinationStore = DB::table('stores')->where('id', $locked->store_id)->first(['id', 'name', 'code']);

            if (! $sourceStore || ! $destinationStore) {
                throw ValidationException::withMessages([
                    'status' => 'Toko asal atau toko tujuan tidak ditemukan.',
                ]);
            }

            $destinationLocationId = $this->destinationLocationId((string) $destinationStore->id);

            if (! $destinationLocationId) {
                throw ValidationException::withMessages([
                    'status' => 'Toko tujuan belum memiliki lokasi penyimpanan. Lengkapi tata letak toko terlebih dahulu.',
                ]);
            }

            $locked->load('details');
            $now = now();
            $movedTotal = 0;

            foreach ($locked->details as $detail) {
                $approved = (int) ($detail->approved_qty ?: $detail->requested_qty);
                $alreadyReceived = (int) ($detail->received_qty ?? 0);
                $remaining = max(0, $approved - $alreadyReceived);

                if ($remaining === 0) {
                    continue;
                }

                $input = array_key_exists((string) $detail->id, $receivedQty)
                    ? (int) $receivedQty[(string) $detail->id]
                    : $remaining;

                $qty = max(0, min($input, $remaining));

                if ($qty === 0) {
                    continue;
                }

                $items = DB::table('store_outbound_details as sod')
                    ->join('items as i', 'i.id', '=', 'sod.item_id')
                    ->where('sod.store_outbound_id', $outbound->id)
                    ->where('i.product_id', $detail->product_id)
                    ->where('i.status', 'in_delivery')
                    ->whereExists(function ($query) use ($sourceStore): void {
                        $query->selectRaw('1')
                            ->from('store_items as source_item')
                            ->whereColumn('source_item.item_id', 'i.id')
                            ->where('source_item.store_id', $sourceStore->id);
                    })
                    ->orderBy('i.created_at')
                    ->limit($qty)
                    ->get(['i.id', 'i.rfid_tag_id']);

                if ($items->count() < $qty) {
                    throw ValidationException::withMessages([
                        'status' => 'Qty barang yang masih dalam perjalanan tidak mencukupi untuk penerimaan ini.',
                    ]);
                }

                foreach ($items as $item) {
                    $category = DB::table('store_items')
                        ->where('item_id', $item->id)
                        ->where('store_id', $sourceStore->id)
                        ->value('category');

                    DB::table('store_items')
                        ->where('item_id', $item->id)
                        ->where('store_id', $sourceStore->id)
                        ->update([
                            'store_id' => $destinationStore->id,
                        ]);

                    DB::table('items')
                        ->where('id', $item->id)
                        ->update([
                            'status' => $category === 'display' ? 'store_display' : 'store_stock',
                            'current_location_id' => $destinationLocationId,
                            'updated_at' => $now,
                        ]);

                    DB::table('item_location_histories')->insert([
                        'id' => (string) Str::uuid(),
                        'item_id' => $item->id,
                        'location_id' => $destinationLocationId,
                        'created_at' => $now,
                        'note' => 'Transfer '.$locked->order_number.' dari '.$sourceStore->name.' ke '.$destinationStore->name,
                    ]);

                    $movedTotal++;
                }

                $detail->forceFill([
                    'received_qty' => $alreadyReceived + $qty,
                ])->save();
            }

            $locked->load('details');

            $allReceived = $locked->details->every(function ($detail): bool {
                $approved = (int) ($detail->approved_qty ?: $detail->requested_qty);

                return $approved === 0 || (int) ($detail->received_qty ?? 0) >= $approved;
            });

            $status = $allReceived ? 'received' : 'partial_received';

            $locked->forceFill([
                'status' => $status,
            ])->save();

            return [
                'received' => $movedTotal,
                'status' => $status,
            ];
        });
    }

    private function destinationLocationId(string $storeId): ?string
    {
        $root = DB::table('store_locations as sl')
            ->join('locations as l', 'l.id', '=', 'sl.location_id')
            ->where('sl.store_id', $storeId)
            ->whereNull('l.deleted_at')
            ->where('l.type', 'store')
            ->orderBy('l.created_at')
            ->value('l.id');

        if ($root) {
            return (string) $root;
        }

        $fallback = DB::table('store_locations as sl')
            ->join('locations as l', 'l.id', '=', 'sl.location_id')
            ->where('sl.store_id', $storeId)
            ->whereNull('l.deleted_at')
            ->orderBy('l.created_at')
            ->value('l.id');

        return $fallback ? (string) $fallback : null;
    }
}
