<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FifoFefoPriorityReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $filtered = $this->filteredQuery($filters);

        $rows = (clone $filtered)
            ->orderByRaw('expired_date ASC NULLS LAST')
            ->orderBy('received_at')
            ->orderBy('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = (clone $filtered)
            ->selectRaw("
                COUNT(*)::int as total_items,
                COUNT(*) FILTER (WHERE scope_type = 'Warehouse')::int as warehouse_items,
                COUNT(*) FILTER (WHERE scope_type = 'Store')::int as store_items,
                COUNT(*) FILTER (WHERE days_to_expiry IS NOT NULL AND days_to_expiry < 0)::int as expired_items,
                COUNT(*) FILTER (WHERE days_to_expiry BETWEEN 0 AND 30)::int as expiring_items,
                COUNT(*) FILTER (WHERE expired_date IS NULL)::int as no_expiry_items,
                COUNT(DISTINCT product_id)::int as affected_products,
                COUNT(DISTINCT location_id)::int as affected_locations
            ")
            ->first();

        $stores = DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $warehouses = DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $products = DB::table('products')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $categories = DB::table('product_categories')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Prioritas FIFO & FEFO',
            'description' => 'Urutan pengeluaran barang berdasarkan waktu masuk (FIFO) dan tanggal kedaluwarsa terdekat (FEFO), per gudang dan toko.',
            'data' => [
                'rows' => $rows,
                'summary' => (array) $summary,
                'stores' => $stores,
                'warehouses' => $warehouses,
                'products' => $products,
                'categories' => $categories,
                'expiryStatuses' => [
                    ['value' => 'expired', 'label' => 'Sudah kedaluwarsa'],
                    ['value' => 'critical', 'label' => 'Kedaluwarsa <= 30 hari'],
                    ['value' => 'warning', 'label' => 'Kedaluwarsa 31-90 hari'],
                    ['value' => 'safe', 'label' => 'Lebih dari 90 hari'],
                    ['value' => 'no_expiry', 'label' => 'Tanpa tanggal kedaluwarsa'],
                ],
                'filters' => $filters,
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        $union = $this->warehouseItemsQuery()->unionAll($this->storeItemsQuery());

        $ranked = DB::query()
            ->fromSub($union, 'stock')
            ->selectRaw("
                stock.*,
                ROW_NUMBER() OVER (
                    PARTITION BY location_key, product_id
                    ORDER BY expired_date ASC NULLS LAST, received_at ASC
                )::int as fefo_rank,
                ROW_NUMBER() OVER (
                    PARTITION BY location_key, product_id
                    ORDER BY received_at ASC
                )::int as fifo_rank
            ");

        return DB::query()
            ->fromSub($ranked, 'ranked_stock')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('product_code', 'ILIKE', $like)
                        ->orWhere('product_name', 'ILIKE', $like)
                        ->orWhere('rfid_value', 'ILIKE', $like)
                        ->orWhere('location_name', 'ILIKE', $like)
                        ->orWhere('condition_name', 'ILIKE', $like);
                });
            })
            ->when($filters['scope'] === 'warehouse', fn ($query) => $query->where('scope_type', 'Warehouse'))
            ->when($filters['scope'] === 'store', fn ($query) => $query->where('scope_type', 'Store'))
            ->when($filters['warehouse_id'] !== '', function ($query) use ($filters): void {
                $query->where('scope_type', 'Warehouse')->where('location_id', $filters['warehouse_id']);
            })
            ->when($filters['store_id'] !== '', function ($query) use ($filters): void {
                $query->where('scope_type', 'Store')->where('location_id', $filters['store_id']);
            })
            ->when($filters['product_id'] !== '', fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when($filters['expiry_status'] === 'expired', fn ($query) => $query->whereNotNull('days_to_expiry')->where('days_to_expiry', '<', 0))
            ->when($filters['expiry_status'] === 'critical', fn ($query) => $query->whereBetween('days_to_expiry', [0, 30]))
            ->when($filters['expiry_status'] === 'warning', fn ($query) => $query->whereBetween('days_to_expiry', [31, 90]))
            ->when($filters['expiry_status'] === 'safe', fn ($query) => $query->where('days_to_expiry', '>', 90))
            ->when($filters['expiry_status'] === 'no_expiry', fn ($query) => $query->whereNull('expired_date'));
    }

    private function warehouseItemsQuery()
    {
        return DB::table('warehouse_items as allocation')
            ->join('warehouses as location', 'location.id', '=', 'allocation.warehouse_id')
            ->join('items as item', 'item.id', '=', 'allocation.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('item_conditions as condition', 'condition.id', '=', 'item.current_condition_id')
            ->leftJoin('rfid_tags as rfid', 'rfid.id', '=', 'item.rfid_tag_id')
            ->whereRaw("item.status::text = 'warehouse_stock'")
            ->selectRaw("
                'Warehouse' AS scope_type,
                ('warehouse:' || location.id::text) AS location_key,
                location.id AS location_id,
                location.name AS location_name,
                product.id AS product_id,
                COALESCE(product.code, '-') AS product_code,
                COALESCE(product.name, '-') AS product_name,
                product.category_id AS category_id,
                COALESCE(category.name, '-') AS category_name,
                COALESCE(condition.name, '-') AS condition_name,
                COALESCE(rfid.value, item.id::text) AS rfid_value,
                item.status::text AS item_status,
                COALESCE(allocation.created_at, item.created_at) AS received_at,
                item.expired_date AS expired_date,
                CASE
                    WHEN item.expired_date IS NULL THEN NULL
                    ELSE (item.expired_date - CURRENT_DATE)::int
                END AS days_to_expiry
            ");
    }

    private function storeItemsQuery()
    {
        return DB::table('store_items as allocation')
            ->join('stores as location', 'location.id', '=', 'allocation.store_id')
            ->join('items as item', 'item.id', '=', 'allocation.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('item_conditions as condition', 'condition.id', '=', 'item.current_condition_id')
            ->leftJoin('rfid_tags as rfid', 'rfid.id', '=', 'item.rfid_tag_id')
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->selectRaw("
                'Store' AS scope_type,
                ('store:' || location.id::text) AS location_key,
                location.id AS location_id,
                location.name AS location_name,
                product.id AS product_id,
                COALESCE(product.code, '-') AS product_code,
                COALESCE(product.name, '-') AS product_name,
                product.category_id AS category_id,
                COALESCE(category.name, '-') AS category_name,
                COALESCE(condition.name, '-') AS condition_name,
                COALESCE(rfid.value, item.id::text) AS rfid_value,
                item.status::text AS item_status,
                COALESCE(allocation.created_at, item.created_at) AS received_at,
                item.expired_date AS expired_date,
                CASE
                    WHEN item.expired_date IS NULL THEN NULL
                    ELSE (item.expired_date - CURRENT_DATE)::int
                END AS days_to_expiry
            ");
    }

    private function filters(Request $request): array
    {
        $scope = trim((string) $request->query('scope', ''));
        $expiryStatus = trim((string) $request->query('expiry_status', ''));
        $perPage = (int) ($request->query('per_page', 25) ?: 25);

        if (! in_array($scope, ['warehouse', 'store'], true)) {
            $scope = '';
        }

        if (! in_array($expiryStatus, ['expired', 'critical', 'warning', 'safe', 'no_expiry'], true)) {
            $expiryStatus = '';
        }

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'scope' => $scope,
            'store_id' => trim((string) $request->query('store_id', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'product_id' => trim((string) $request->query('product_id', '')),
            'category_id' => trim((string) $request->query('category_id', '')),
            'expiry_status' => $expiryStatus,
            'per_page' => $perPage,
        ];
    }
}
