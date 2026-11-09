<?php

namespace App\Domain\StoreOperations\Support;

use App\Domain\StoreOperations\Queries\StoreTransferQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreTransferStock
{
    /**
     * @param  array<int, string>  $productIds
     * @return array<string, int>
     */
    public function availableByProduct(string $storeId, array $productIds = []): array
    {
        $query = DB::table('store_items as si')
            ->join('items as i', 'i.id', '=', 'si.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('si.store_id', $storeId)
            ->whereIn('i.status', ['store_stock', 'store_display'])
            ->whereNotNull('i.rfid_tag_id')
            ->whereNull('p.deleted_at')
            ->whereNotExists(fn ($sub) => $this->activeTransferAllocation($sub))
            ->groupBy('i.product_id')
            ->selectRaw('i.product_id, COUNT(DISTINCT i.id)::int as available_qty');

        if ($productIds !== []) {
            $query->whereIn('i.product_id', $productIds);
        }

        return $query->pluck('available_qty', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(string $storeId, string $search = '', int $limit = 200): array
    {
        $rows = DB::table('store_items as si')
            ->join('items as i', 'i.id', '=', 'si.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'p.category_id')
            ->where('si.store_id', $storeId)
            ->whereIn('i.status', ['store_stock', 'store_display'])
            ->whereNotNull('i.rfid_tag_id')
            ->whereNull('p.deleted_at')
            ->whereNotExists(fn ($sub) => $this->activeTransferAllocation($sub))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('p.name', 'ILIKE', "%{$search}%")
                        ->orWhere('p.code', 'ILIKE', "%{$search}%");
                });
            })
            ->groupBy('p.id', 'p.name', 'p.code', 'category.name')
            ->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.code as product_code,
                COALESCE(category.name, \'-\') as category_name,
                COUNT(DISTINCT i.id)::int as available_qty
            ')
            ->orderBy('p.name')
            ->limit($limit)
            ->get();

        $prices = $this->pricesForProducts($rows->pluck('product_id')->all());

        return $rows->map(function (object $row) use ($prices): array {
            $price = $prices->get($row->product_id);

            return [
                'product_id' => (string) $row->product_id,
                'product_name' => (string) $row->product_name,
                'product_code' => (string) $row->product_code,
                'category_name' => (string) $row->category_name,
                'available_qty' => (int) $row->available_qty,
                'product_price_id' => $price?->id,
                'product_price' => (float) ($price?->price ?? 0),
            ];
        })->all();
    }

    /**
     * @return Collection<int, object>
     */
    public function availableItems(string $storeId, string $productId, int $limit): Collection
    {
        return DB::table('store_items as si')
            ->join('items as i', 'i.id', '=', 'si.item_id')
            ->where('si.store_id', $storeId)
            ->where('i.product_id', $productId)
            ->whereIn('i.status', ['store_stock', 'store_display'])
            ->whereNotNull('i.rfid_tag_id')
            ->whereNotExists(fn ($sub) => $this->activeTransferAllocation($sub))
            ->groupBy('i.id', 'i.rfid_tag_id', 'i.created_at')
            ->orderBy('i.created_at')
            ->limit($limit)
            ->selectRaw('i.id, i.rfid_tag_id, MIN(si.category::text) as category')
            ->get();
    }

    public function priceForProduct(string $productId): ?object
    {
        return $this->pricesForProducts([$productId])->get($productId);
    }

    /**
     * @param  array<int, string>  $productIds
     * @return Collection<string, object>
     */
    private function pricesForProducts(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $today = now()->toDateString();

        return DB::table('product_prices')
            ->whereIn('product_id', $productIds)
            ->orderByRaw("CASE WHEN price_type::text = 'store' THEN 0 WHEN price_type::text = 'base' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN valid_from IS NULL OR valid_from <= ? THEN 0 ELSE 1 END', [$today])
            ->orderByRaw('CASE WHEN valid_to IS NULL OR valid_to >= ? THEN 0 ELSE 1 END', [$today])
            ->orderByDesc('created_at')
            ->get(['id', 'product_id', 'price', 'price_type', 'valid_from', 'valid_to'])
            ->groupBy('product_id')
            ->map(fn (Collection $rows) => $rows->first());
    }

    private function activeTransferAllocation($query): void
    {
        $query->selectRaw('1')
            ->from('store_outbound_details as allocated_detail')
            ->join('store_outbounds as allocated_outbound', 'allocated_outbound.id', '=', 'allocated_detail.store_outbound_id')
            ->join('store_orders as allocated_order', 'allocated_order.shipping_number', '=', 'allocated_outbound.shipping_number')
            ->whereColumn('allocated_detail.item_id', 'i.id')
            ->where('allocated_outbound.outbound_type', 'transfer')
            ->whereIn('allocated_order.status', StoreTransferQuery::ACTIVE_STATUSES);
    }
}
