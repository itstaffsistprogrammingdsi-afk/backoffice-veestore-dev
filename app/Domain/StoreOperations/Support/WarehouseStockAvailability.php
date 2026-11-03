<?php

namespace App\Domain\StoreOperations\Support;

use App\Services\StockReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseStockAvailability
{
    public function __construct(
        private readonly StockReservationService $reservations
    ) {}

    /**
     * @param  array<int, string>  $warehouseIds
     * @param  array<int, string>  $productIds
     * @return array<string, array<string, array<string, int>>>
     */
    public function forProducts(array $warehouseIds, array $productIds): array
    {
        $warehouseIds = $this->uuidValues($warehouseIds);
        $productIds = $this->uuidValues($productIds);

        if ($warehouseIds === [] || $productIds === []) {
            return [];
        }

        $rows = DB::table('items as i')
            ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->whereIn('wi.warehouse_id', $warehouseIds)
            ->whereIn('i.product_id', $productIds)
            ->where('i.status', 'warehouse_stock')
            ->groupBy('wi.warehouse_id', 'i.product_id')
            ->select(['wi.warehouse_id', 'i.product_id'])
            ->selectRaw("
                SUM(CASE WHEN LOWER(COALESCE(ic.status::text, ic.name, '')) = 'good' THEN 1 ELSE 0 END)::int as qty_good,
                SUM(CASE WHEN LOWER(COALESCE(ic.status::text, ic.name, '')) = 'good_after_repair' THEN 1 ELSE 0 END)::int as qty_good_after_repair,
                SUM(CASE WHEN LOWER(COALESCE(ic.status::text, ic.name, '')) = 'bad' THEN 1 ELSE 0 END)::int as qty_bad,
                SUM(CASE WHEN LOWER(COALESCE(ic.status::text, ic.name, '')) IN ('good', 'good_after_repair') THEN 1 ELSE 0 END)::int as qty_on_hand
            ")
            ->get();

        $reserved = $this->reservations->remainingByLocation('warehouse')
            ->get()
            ->filter(fn ($row): bool => in_array((string) $row->location_id, $warehouseIds, true)
                && in_array((string) $row->product_id, $productIds, true))
            ->keyBy(fn ($row): string => $row->location_id.'|'.$row->product_id);

        $availability = [];

        foreach ($rows as $row) {
            $warehouseId = (string) $row->warehouse_id;
            $productId = (string) $row->product_id;
            $onHand = (int) $row->qty_on_hand;
            $reservedQty = (int) ($reserved[$warehouseId.'|'.$productId]->qty_reserved ?? 0);

            $availability[$warehouseId][$productId] = [
                'qty_good' => (int) $row->qty_good,
                'qty_good_after_repair' => (int) $row->qty_good_after_repair,
                'qty_bad' => (int) $row->qty_bad,
                'qty_on_hand' => $onHand,
                'qty_reserved' => $reservedQty,
                'qty_available' => max(0, $onHand - $reservedQty),
            ];
        }

        return $availability;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function uuidValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '' && Str::isUuid($value))
            ->unique()
            ->values()
            ->all();
    }
}
