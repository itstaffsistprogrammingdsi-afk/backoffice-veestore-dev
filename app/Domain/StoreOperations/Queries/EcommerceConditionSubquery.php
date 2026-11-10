<?php

namespace App\Domain\StoreOperations\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EcommerceConditionSubquery
{
    public static function make(): Builder
    {
        $storeItems = DB::table('store_outbounds as store_outbound')
            ->join('store_outbound_details as store_detail', 'store_detail.store_outbound_id', '=', 'store_outbound.id')
            ->join('items as store_item', 'store_item.id', '=', 'store_detail.item_id')
            ->leftJoin('item_conditions as store_condition', 'store_condition.id', '=', 'store_item.current_condition_id')
            ->whereNotNull('store_outbound.ecommerce_order_id')
            ->selectRaw("
                store_outbound.ecommerce_order_id,
                store_item.product_id,
                COALESCE(store_condition.status::text, 'unknown') as condition_status
            ");

        $warehouseItems = DB::table('warehouse_outbounds as warehouse_outbound')
            ->join('warehouse_outbound_details as warehouse_detail', 'warehouse_detail.warehouse_outbound_id', '=', 'warehouse_outbound.id')
            ->join('items as warehouse_item', 'warehouse_item.id', '=', 'warehouse_detail.item_id')
            ->leftJoin('item_conditions as warehouse_condition', 'warehouse_condition.id', '=', 'warehouse_item.current_condition_id')
            ->whereNotNull('warehouse_outbound.order_id')
            ->selectRaw("
                warehouse_outbound.order_id as ecommerce_order_id,
                warehouse_item.product_id,
                COALESCE(warehouse_condition.status::text, 'unknown') as condition_status
            ");

        return DB::query()
            ->fromSub($storeItems->unionAll($warehouseItems), 'ecommerce_condition_items')
            ->groupBy('ecommerce_order_id', 'product_id')
            ->selectRaw("
                ecommerce_order_id,
                product_id,
                CASE
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good_after_repair') = COUNT(*) THEN 'good_after_repair'
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good_after_repair') > 0 THEN 'mixed'
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good') = COUNT(*) THEN 'good'
                    ELSE 'unknown'
                END as condition_status,
                CASE
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good_after_repair') = COUNT(*) THEN 'Good After Repair'
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good_after_repair') > 0 THEN 'Campuran'
                    WHEN COUNT(*) FILTER (WHERE condition_status = 'good') = COUNT(*) THEN 'Good'
                    ELSE 'Tidak Tercatat'
                END as condition_name
            ");
    }
}
