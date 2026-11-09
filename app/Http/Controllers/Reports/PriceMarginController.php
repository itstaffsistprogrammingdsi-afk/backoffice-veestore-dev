<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PriceMarginController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->baseQuery($filters);

        $rows = (clone $query)
            ->orderBy('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn ($row) => $this->transformRow($row, $filters['min_margin']));

        $summary = DB::query()
            ->fromSub(clone $query, 'price_rows')
            ->selectRaw("
                COUNT(*)::int as total_products,
                COUNT(*) FILTER (WHERE base_price IS NULL OR store_price IS NULL OR ecommerce_price IS NULL OR base_price <= 0)::int as missing_price,
                COUNT(*) FILTER (
                    WHERE base_price > 0
                      AND (store_price < base_price OR ecommerce_price < base_price)
                )::int as below_base,
                COUNT(*) FILTER (
                    WHERE base_price > 0
                      AND store_price IS NOT NULL
                      AND ecommerce_price IS NOT NULL
                      AND (
                        ((store_price - base_price) / base_price * 100) < ?
                        OR ((ecommerce_price - base_price) / base_price * 100) < ?
                      )
                )::int as below_min_margin
            ", [$filters['min_margin'], $filters['min_margin']])
            ->first();

        $categories = DB::table('product_categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = DB::table('brands')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Reports/PriceMargin', [
            'rows' => $rows,
            'summary' => (array) $summary,
            'categories' => $categories,
            'brands' => $brands,
            'statusOptions' => [
                ['value' => 'below_base', 'label' => 'Harga jual di bawah base'],
                ['value' => 'below_min_margin', 'label' => 'Margin di bawah batas'],
                ['value' => 'missing_price', 'label' => 'Harga belum lengkap'],
                ['value' => 'ok', 'label' => 'Margin aman'],
            ],
            'filters' => $filters,
            'urls' => ['index' => route('reports.price-margin.index')],
        ]);
    }

    private function baseQuery(array $filters)
    {
        $rankedPrices = DB::table('product_prices')
            ->whereIn('price_type', ['base', 'store', 'ecommerce'])
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', now()->toDateString());
            })
            ->select('product_id', 'price_type', 'price')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY product_id, price_type ORDER BY valid_from DESC, created_at DESC) AS row_rank');

        $prices = DB::query()
            ->fromSub($rankedPrices, 'ranked_prices')
            ->where('row_rank', 1)
            ->select('product_id')
            ->selectRaw("MAX(CASE WHEN price_type = 'base' THEN price END)::numeric as base_price")
            ->selectRaw("MAX(CASE WHEN price_type = 'store' THEN price END)::numeric as store_price")
            ->selectRaw("MAX(CASE WHEN price_type = 'ecommerce' THEN price END)::numeric as ecommerce_price")
            ->groupBy('product_id');

        $query = DB::table('products as product')
            ->leftJoinSub($prices, 'price', fn ($join) => $join->on('price.product_id', '=', 'product.id'))
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('brands as brand', 'brand.id', '=', 'product.brand_id')
            ->whereNull('product.deleted_at')
            ->selectRaw("
                product.id,
                product.name as product_name,
                product.code as product_code,
                category.name as category_name,
                brand.name as brand_name,
                price.base_price,
                price.store_price,
                price.ecommerce_price
            ")
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('category.name', 'ILIKE', $like)
                        ->orWhere('brand.name', 'ILIKE', $like);
                });
            })
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('product.category_id', $filters['category_id']))
            ->when($filters['brand_id'] !== '', fn ($query) => $query->where('product.brand_id', $filters['brand_id']))
            ->when($filters['status'] === 'below_base', fn ($query) => $query->whereRaw('price.base_price > 0 AND (price.store_price < price.base_price OR price.ecommerce_price < price.base_price)'))
            ->when($filters['status'] === 'below_min_margin', fn ($query) => $query->whereRaw("price.base_price > 0 AND price.store_price IS NOT NULL AND price.ecommerce_price IS NOT NULL AND (((price.store_price - price.base_price) / price.base_price * 100) < ? OR ((price.ecommerce_price - price.base_price) / price.base_price * 100) < ?)", [$filters['min_margin'], $filters['min_margin']]))
            ->when($filters['status'] === 'missing_price', fn ($query) => $query->whereRaw('price.base_price IS NULL OR price.store_price IS NULL OR price.ecommerce_price IS NULL OR price.base_price <= 0'))
            ->when($filters['status'] === 'ok', fn ($query) => $query->whereRaw("price.base_price > 0 AND price.store_price >= price.base_price AND price.ecommerce_price >= price.base_price AND ((price.store_price - price.base_price) / price.base_price * 100) >= ? AND ((price.ecommerce_price - price.base_price) / price.base_price * 100) >= ?", [$filters['min_margin'], $filters['min_margin']]));

        return $query;
    }

    private function transformRow(object $row, float $minMargin): array
    {
        $base = (float) ($row->base_price ?? 0);
        $store = (float) ($row->store_price ?? 0);
        $ecommerce = (float) ($row->ecommerce_price ?? 0);

        $storeMargin = $base > 0 && $row->store_price !== null
            ? round((($store - $base) / $base) * 100, 2)
            : null;
        $ecommerceMargin = $base > 0 && $row->ecommerce_price !== null
            ? round((($ecommerce - $base) / $base) * 100, 2)
            : null;

        $status = 'ok';

        if ($base <= 0 || $row->store_price === null || $row->ecommerce_price === null) {
            $status = 'missing_price';
        } elseif ($store < $base || $ecommerce < $base) {
            $status = 'below_base';
        } elseif (($storeMargin !== null && $storeMargin < $minMargin) || ($ecommerceMargin !== null && $ecommerceMargin < $minMargin)) {
            $status = 'below_min_margin';
        }

        return [
            ...(array) $row,
            'store_margin_percent' => $storeMargin,
            'ecommerce_margin_percent' => $ecommerceMargin,
            'margin_status' => $status,
            'margin_status_label' => match ($status) {
                'missing_price' => 'Harga belum lengkap',
                'below_base' => 'Di bawah harga base',
                'below_min_margin' => 'Margin di bawah batas',
                default => 'Margin aman',
            },
        ];
    }

    private function filters(Request $request): array
    {
        $perPage = (int) ($request->query('per_page', 25) ?: 25);
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, ['below_base', 'below_min_margin', 'missing_price', 'ok'], true)) {
            $status = '';
        }

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'category_id' => trim((string) $request->query('category_id', '')),
            'brand_id' => trim((string) $request->query('brand_id', '')),
            'status' => $status,
            'per_page' => $perPage,
            'min_margin' => (float) config('backoffice.price_guard.min_margin_percent', 20),
        ];
    }
}
