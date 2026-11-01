<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EcommerceOrderStockAlertService
{
    private const ACTIVE_STATUSES = [
        'pending',
        'paid',
        'processed',
        'processing',
        'packing',
    ];

    public function count(): int
    {
        return (int) $this->apply(
            \App\Models\EcommerceOrder::query()
        )->distinct('ecommerce_orders.id')->count('ecommerce_orders.id');
    }

    public function issueOrderIds(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return $this->apply(
            \App\Models\EcommerceOrder::query()
                ->whereIn('ecommerce_orders.id', $orderIds)
        )
            ->distinct()
            ->pluck('ecommerce_orders.id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function allIssueOrderIds(): array
    {
        return $this->apply(\App\Models\EcommerceOrder::query())
            ->distinct()
            ->pluck('ecommerce_orders.id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function apply(Builder $query): Builder
    {
        $requirements = DB::table('ecommerce_order_details as detail')
            ->selectRaw('detail.ecommerce_order_id, detail.product_id, SUM(detail.quantity)::int AS required_qty')
            ->groupBy('detail.ecommerce_order_id', 'detail.product_id');

        $storeStocks = DB::table('items as item')
            ->join('store_items as store_item', 'store_item.item_id', '=', 'item.id')
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display', 'display_store'])
            ->selectRaw('store_item.store_id, item.product_id, COUNT(DISTINCT item.id)::int AS available_qty')
            ->groupBy('store_item.store_id', 'item.product_id');

        $warehouseStocks = DB::table('items as item')
            ->join('warehouse_items as warehouse_item', 'warehouse_item.item_id', '=', 'item.id')
            ->where(DB::raw('item.status::text'), 'warehouse_stock')
            ->selectRaw('warehouse_item.warehouse_id, item.product_id, COUNT(DISTINCT item.id)::int AS available_qty')
            ->groupBy('warehouse_item.warehouse_id', 'item.product_id');

        return $query
            ->whereIn(DB::raw('LOWER((ecommerce_orders.status)::text)'), self::ACTIVE_STATUSES)
            ->where(function (Builder $handlerQuery): void {
                $handlerQuery
                    ->whereNotNull('ecommerce_orders.handled_by_store_id')
                    ->orWhereNotNull('ecommerce_orders.handled_by_warehouse_id');
            })
            ->whereExists(function ($stockQuery) use ($requirements, $storeStocks, $warehouseStocks): void {
                $stockQuery
                    ->fromSub($requirements, 'requirement')
                    ->leftJoinSub($storeStocks, 'store_stock', function ($join): void {
                        $join
                            ->on('store_stock.store_id', '=', 'ecommerce_orders.handled_by_store_id')
                            ->on('store_stock.product_id', '=', 'requirement.product_id');
                    })
                    ->leftJoinSub($warehouseStocks, 'warehouse_stock', function ($join): void {
                        $join
                            ->on('warehouse_stock.warehouse_id', '=', 'ecommerce_orders.handled_by_warehouse_id')
                            ->on('warehouse_stock.product_id', '=', 'requirement.product_id');
                    })
                    ->whereColumn('requirement.ecommerce_order_id', 'ecommerce_orders.id')
                    ->whereRaw('
                        CASE
                            WHEN ecommerce_orders.handled_by_store_id IS NOT NULL
                                THEN COALESCE(store_stock.available_qty, 0)
                            ELSE COALESCE(warehouse_stock.available_qty, 0)
                        END < requirement.required_qty
                    ');
            });
    }
}
