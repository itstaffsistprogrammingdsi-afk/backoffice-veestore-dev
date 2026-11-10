<?php

namespace App\Domain\StoreOperations\Queries;

use App\Domain\StoreOperations\Support\SaleConditionMetrics;
use Illuminate\Support\Facades\DB;

class StoreSampleGimmickReportQuery
{
    public function build(array $filters): array
    {
        return [
            'gimmickReceiptRows' => $this->receiptGimmickRows($filters),
            'sampleRows' => $this->stockMarkerRows($filters, 'sample'),
            'gimmickStockRows' => $this->stockMarkerRows($filters, 'gimmick'),
            'summary' => $this->summary($filters),
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ];
    }

    public function exportData(array $filters): array
    {
        $filters['per_page'] = 100000;

        return [
            'gimmickReceiptRows' => $this->receiptGimmickRows($filters)->getCollection(),
            'sampleRows' => $this->stockMarkerRows($filters, 'sample')->getCollection(),
            'gimmickStockRows' => $this->stockMarkerRows($filters, 'gimmick')->getCollection(),
            'summary' => $this->summary($filters),
        ];
    }

    private function receiptGimmickRows(array $filters)
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as payment', 'payment.id', '=', 'svp.payment_id')
            ->whereNull('payment.deleted_at')
            ->where(function ($query): void {
                $query->whereNotNull('payment.completed_at')
                    ->orWhereIn(DB::raw('LOWER(payment.status::text)'), ['paid', 'settlement', 'capture', 'success', 'completed']);
            })
            ->selectRaw('
                svp.visit_id,
                MIN(COALESCE(payment.completed_at, payment.created_at)) as paid_at,
                MIN(payment.transaction_number) as transaction_number
            ')
            ->groupBy('svp.visit_id');

        $trxDateSql = 'COALESCE(paid_visit.paid_at, cart.created_at)::date';
        $priceSql = "COALESCE(NULLIF(detail.product_price_amount, 0), ({$this->priceSql('item.product_id', $trxDateSql, 'store')}))";
        $conditionStatusSql = "COALESCE(item_condition.status::text, 'unknown')";
        $clearancePercentageSql = SaleConditionMetrics::clearancePercentageSql(
            $conditionStatusSql,
            $priceSql,
            'detail.discount_amount'
        );

        return DB::table('store_cart_details as detail')
            ->join('store_carts as cart', 'cart.id', '=', 'detail.cart_id')
            ->join('store_customer_visits as visit', 'visit.id', '=', 'cart.visit_id')
            ->join('stores as store', 'store.id', '=', 'cart.store_id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('item_conditions as item_condition', 'item_condition.id', '=', 'item.current_condition_id')
            ->leftJoinSub($paidVisits, 'paid_visit', function ($join): void {
                $join->on('paid_visit.visit_id', '=', 'cart.visit_id');
            })
            ->whereRaw(SaleConditionMetrics::gimmickSaleLineSql('detail'))
            ->whereNull('store.deleted_at')
            ->whereDate(DB::raw($trxDateSql), '>=', $filters['date_from'])
            ->whereDate(DB::raw($trxDateSql), '<=', $filters['date_to'])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('cart.store_id', $storeId))
            ->when($filters['condition_status'] !== 'all', fn ($query) => $query->whereRaw("{$conditionStatusSql} = ?", [$filters['condition_status']]))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like)
                        ->orWhereRaw("COALESCE(paid_visit.transaction_number, '') ILIKE ?", [$like]);
                });
            })
            ->selectRaw("
                {$trxDateSql} as trx_date,
                COALESCE(paid_visit.paid_at, cart.created_at) as trx_datetime,
                COALESCE(paid_visit.transaction_number, cart.id::text) as transaction_number,
                store.id as store_id,
                store.name as store_name,
                store.code as store_code,
                product.id as product_id,
                product.code as product_code,
                product.name as product_name,
                1::int as qty,
                {$conditionStatusSql} as condition_status,
                {$this->conditionNameSql('item_condition')} as condition_name,
                ({$clearancePercentageSql})::numeric(18,2) as clearance_percentage,
                ({$priceSql})::numeric(18,2) as gross_value,
                COALESCE(detail.discount_amount, 0)::numeric(18,2) as discount_value,
                GREATEST(({$priceSql}) - COALESCE(detail.discount_amount, 0), 0)::numeric(18,2) as net_value,
                ({$priceSql})::numeric(18,2) as gimmick_value,
                detail.void_status::text as void_status
            ")
            ->orderByDesc('trx_datetime')
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    private function stockMarkerRows(array $filters, string $type)
    {
        $markerKeySql = "'store_sample_gimmick:' || store_item.id::text";
        $markedAtSql = "COALESCE(NULLIF(marker.value->>'marked_at', '')::timestamptz, store_item.created_at)";
        $priceSql = $this->priceSql('product.id', "{$markedAtSql}::date", 'store');
        $basePriceSql = $this->priceSql('product.id', "{$markedAtSql}::date", 'base');
        $conditionStatusSql = "COALESCE(item_condition.status::text, 'unknown')";

        return DB::table('store_items as store_item')
            ->join('stores as store', 'store.id', '=', 'store_item.store_id')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->join('app_settings as marker', 'marker.key', '=', DB::raw($markerKeySql))
            ->leftJoin('item_conditions as item_condition', 'item_condition.id', '=', 'item.current_condition_id')
            ->whereNull('store.deleted_at')
            ->whereNull('product.deleted_at')
            ->whereRaw("marker.value->>'type' = ?", [$type])
            ->whereDate(DB::raw($markedAtSql), '>=', $filters['date_from'])
            ->whereDate(DB::raw($markedAtSql), '<=', $filters['date_to'])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('store_item.store_id', $storeId))
            ->when($filters['condition_status'] !== 'all', fn ($query) => $query->whereRaw("{$conditionStatusSql} = ?", [$filters['condition_status']]))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like)
                        ->orWhereRaw("COALESCE(marker.value->>'request_number', '') ILIKE ?", [$like]);
                });
            })
            ->selectRaw("
                {$markedAtSql} as marked_at,
                marker.value->>'type' as marker_type,
                marker.value->>'marked_by' as marked_by,
                marker.value->>'request_number' as request_number,
                store.id as store_id,
                store.name as store_name,
                store.code as store_code,
                product.id as product_id,
                product.code as product_code,
                product.name as product_name,
                1::int as qty,
                {$conditionStatusSql} as condition_status,
                {$this->conditionNameSql('item_condition')} as condition_name,
                ({$priceSql})::numeric(18,2) as stock_value,
                ({$basePriceSql})::numeric(18,2) as base_value
            ")
            ->orderByDesc('marked_at')
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    private function summary(array $filters): array
    {
        $receipt = $this->receiptSummary($filters);
        $sample = $this->stockSummary($filters, 'sample');
        $gimmick = $this->stockSummary($filters, 'gimmick');

        return [
            'total_gimmick_receipts' => (int) ($receipt->total_gimmick_receipts ?? 0),
            'total_gimmick_value' => (float) ($receipt->total_gimmick_value ?? 0),
            'total_gimmick_items' => (int) ($receipt->total_gimmick_items ?? 0),
            'total_sample_items' => (int) ($sample->total_items ?? 0),
            'total_sample_value' => (float) ($sample->total_value ?? 0),
            'total_gimmick_stock_items' => (int) ($gimmick->total_items ?? 0),
            'total_gimmick_stock_value' => (float) ($gimmick->total_value ?? 0),
            'total_clearance_gimmick_value' => (float) ($receipt->total_clearance_discount ?? 0),
        ];
    }

    private function receiptSummary(array $filters)
    {
        return DB::table('store_cart_details as detail')
            ->join('store_carts as cart', 'cart.id', '=', 'detail.cart_id')
            ->join('stores as store', 'store.id', '=', 'cart.store_id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('item_conditions as item_condition', 'item_condition.id', '=', 'item.current_condition_id')
            ->leftJoinSub(
                DB::table('store_visit_payments as svp')
                    ->join('payments as payment', 'payment.id', '=', 'svp.payment_id')
                    ->whereNull('payment.deleted_at')
                    ->selectRaw('svp.visit_id, MIN(COALESCE(payment.completed_at, payment.created_at)) as paid_at')
                    ->groupBy('svp.visit_id'),
                'paid_visit',
                function ($join): void {
                    $join->on('paid_visit.visit_id', '=', 'cart.visit_id');
                }
            )
            ->whereRaw(SaleConditionMetrics::gimmickSaleLineSql('detail'))
            ->whereNull('store.deleted_at')
            ->whereDate(DB::raw('COALESCE(paid_visit.paid_at, cart.created_at)'), '>=', $filters['date_from'])
            ->whereDate(DB::raw('COALESCE(paid_visit.paid_at, cart.created_at)'), '<=', $filters['date_to'])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('cart.store_id', $storeId))
            ->when($filters['condition_status'] !== 'all', fn ($query) => $query->whereRaw("COALESCE(item_condition.status::text, 'unknown') = ?", [$filters['condition_status']]))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like);
                });
            })
            ->selectRaw('
                COUNT(*)::int as total_gimmick_receipts,
                COUNT(DISTINCT cart.id)::int as total_gimmick_transactions,
                COALESCE(SUM(1), 0)::int as total_gimmick_items,
                COALESCE(SUM(COALESCE(detail.product_price_amount, 0)), 0)::numeric as total_gimmick_value,
                COALESCE(SUM(CASE WHEN item_condition.status::text = \'good_after_repair\' THEN detail.discount_amount ELSE 0 END), 0)::numeric as total_clearance_discount
            ')
            ->first();
    }

    private function stockSummary(array $filters, string $type)
    {
        $markerKeySql = "'store_sample_gimmick:' || store_item.id::text";
        $markedAtSql = "COALESCE(NULLIF(marker.value->>'marked_at', '')::timestamptz, store_item.created_at)";
        $priceSql = $this->priceSql('product.id', "{$markedAtSql}::date", 'store');

        return DB::table('store_items as store_item')
            ->join('stores as store', 'store.id', '=', 'store_item.store_id')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->join('app_settings as marker', 'marker.key', '=', DB::raw($markerKeySql))
            ->leftJoin('item_conditions as item_condition', 'item_condition.id', '=', 'item.current_condition_id')
            ->whereNull('store.deleted_at')
            ->whereNull('product.deleted_at')
            ->whereRaw("marker.value->>'type' = ?", [$type])
            ->whereDate(DB::raw($markedAtSql), '>=', $filters['date_from'])
            ->whereDate(DB::raw($markedAtSql), '<=', $filters['date_to'])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('store_item.store_id', $storeId))
            ->when($filters['condition_status'] !== 'all', fn ($query) => $query->whereRaw("COALESCE(item_condition.status::text, 'unknown') = ?", [$filters['condition_status']]))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like);
                });
            })
            ->selectRaw("
                COUNT(*)::int as total_items,
                COALESCE(SUM(({$priceSql})), 0)::numeric as total_value
            ")
            ->first();
    }

    private function conditionNameSql(string $conditionAlias): string
    {
        return SaleConditionMetrics::conditionNameSql($conditionAlias);
    }

    private function priceSql(string $productIdColumn, string $dateSql, string $priceType): string
    {
        return "
            COALESCE(
                (
                    SELECT price.price
                    FROM product_prices price
                    WHERE price.product_id = {$productIdColumn}
                      AND price.price_type::text = '{$priceType}'
                      AND price.valid_from <= {$dateSql}
                      AND (price.valid_to IS NULL OR price.valid_to >= {$dateSql})
                    ORDER BY price.valid_from DESC, price.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";
    }
}
