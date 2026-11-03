<?php

namespace App\Http\Controllers\Reports;

use App\Exports\SupplierProductExport;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SupplierProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view supplier-product')
            ->only(['index', 'export']);
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $reportQuery = $this->buildReportQuery($filters);

        $rows = (clone $reportQuery)
            ->orderBy('supplier_name')
            ->orderBy('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'report')
            ->selectRaw('COUNT(*)::bigint as total_relations')
            ->selectRaw('COUNT(DISTINCT supplier_id)::bigint as total_suppliers')
            ->selectRaw('COUNT(DISTINCT product_name)::bigint as total_products')
            ->selectRaw('COALESCE(SUM(qty_inbound), 0)::bigint as total_qty_inbound')
            ->selectRaw('COALESCE(SUM(stok_now), 0)::bigint as total_stock')
            ->selectRaw('COUNT(*) FILTER (WHERE stok_now > 0)::bigint as products_with_stock')
            ->selectRaw('COUNT(*) FILTER (WHERE stok_now <= 0)::bigint as products_without_stock')
            ->first();

        $supplierChartRows = DB::query()
            ->fromSub(clone $reportQuery, 'report')
            ->select('supplier_name as label')
            ->selectRaw('COUNT(*)::bigint as total_products')
            ->selectRaw('COALESCE(SUM(qty_inbound), 0)::bigint as total_qty')
            ->groupBy('supplier_name')
            ->orderByDesc('total_products')
            ->limit(10)
            ->get();

        $productChartRows = DB::query()
            ->fromSub(clone $reportQuery, 'report')
            ->select('product_name as label')
            ->selectRaw('COUNT(DISTINCT supplier_id)::bigint as total_suppliers')
            ->selectRaw('COALESCE(SUM(qty_inbound), 0)::bigint as total_qty')
            ->groupBy('product_name')
            ->orderByDesc('total_suppliers')
            ->limit(10)
            ->get();

        $countryChartRows = DB::query()
            ->fromSub(clone $reportQuery, 'report')
            ->selectRaw("COALESCE(NULLIF(TRIM(supplier_country), ''), 'Tidak diketahui') as label")
            ->selectRaw('COUNT(DISTINCT supplier_id)::bigint as total')
            ->groupByRaw("COALESCE(NULLIF(TRIM(supplier_country), ''), 'Tidak diketahui')")
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $stockChartRows = collect([
            (object) [
                'label' => 'Masih Ada Stok',
                'total' => (int) ($summary->products_with_stock ?? 0),
            ],
            (object) [
                'label' => 'Stok Habis',
                'total' => (int) ($summary->products_without_stock ?? 0),
            ],
        ]);

        $trendRows = $this->buildInboundBaseQuery($filters)
            ->selectRaw("TO_CHAR(wi.received_date, 'YYYY-MM') as period")
            ->selectRaw('SUM(wid.quantity)::bigint as total_qty')
            ->selectRaw('COUNT(DISTINCT wi.id)::bigint as total_inbounds')
            ->groupByRaw("TO_CHAR(wi.received_date, 'YYYY-MM')")
            ->orderByDesc('period')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();

        $topSupplier = $supplierChartRows->first();
        $topProduct = $productChartRows->first();
        $topCountry = $countryChartRows->first();

        $suppliersForProduct = collect();
        if ($filters['product_id'] || $filters['keyword'] !== '') {
            $suppliersForProduct = $this->buildSuppliersForProductQuery($filters)
                ->orderBy('supplier_name')
                ->get();
        }

        $suppliers = DB::table('suppliers')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $products = DB::table('products')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/SupplierProducts', [
            'rows' => $rows,
            'suppliers' => $suppliers,
            'products' => $products,
            'summary' => $summary,
            'supplierChartRows' => $supplierChartRows,
            'productChartRows' => $productChartRows,
            'countryChartRows' => $countryChartRows,
            'stockChartRows' => $stockChartRows,
            'trendRows' => $trendRows,
            'topSupplier' => $topSupplier,
            'topProduct' => $topProduct,
            'topCountry' => $topCountry,
            'suppliersForProduct' => $suppliersForProduct,
            'filters' => [
                'supplier_id' => $filters['supplier_id'],
                'product_id' => $filters['product_id'],
                'keyword' => $filters['keyword'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'has_stock' => $filters['has_stock'],
                'per_page' => $filters['per_page'],
            ],
            'urls' => [
                'index' => route('reports.supplier-products.index'),
                'export' => route('reports.supplier-products.export'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $filename = 'supplier-products-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new SupplierProductExport($this->buildReportQuery($filters)),
            $filename,
        );
    }

    private function validatedFilters(Request $request): array
    {
        $supplierId = $request->string('supplier_id')->trim()->toString();
        $productId = $request->string('product_id')->trim()->toString();
        $keyword = $request->string('q')->trim()->toString();
        $dateFrom = $request->string('date_from')->trim()->toString();
        $dateTo = $request->string('date_to')->trim()->toString();
        $perPage = (int) $request->input('per_page', 25);

        if ($supplierId !== '' && ! Str::isUuid($supplierId)) {
            $supplierId = '';
        }

        if ($productId !== '' && ! Str::isUuid($productId)) {
            $productId = '';
        }

        if (! $this->isValidDate($dateFrom)) {
            $dateFrom = '';
        }

        if (! $this->isValidDate($dateTo)) {
            $dateTo = '';
        }

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        return [
            'supplier_id' => $supplierId !== '' ? $supplierId : null,
            'product_id' => $productId !== '' ? $productId : null,
            'keyword' => mb_substr($keyword, 0, 150),
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'has_stock' => $request->boolean('has_stock'),
            'per_page' => $perPage,
        ];
    }

    private function buildReportQuery(array $filters): Builder
    {
        $stockNowByProduct = DB::table('items as it')
            ->select('it.product_id')
            ->selectRaw('COUNT(*)::bigint as stok_now')
            ->whereIn(DB::raw('it.status::text'), ['warehouse_stock'])
            ->groupBy('it.product_id');

        $inboundBySupplierProduct = $this->buildInboundBaseQuery($filters)
            ->select([
                's.id as supplier_id',
                's.name as supplier_name',
                's.country as supplier_country',
                's.phone as supplier_phone',
                'p.id as product_id',
                'p.name as product_name',
                'p.code as product_code',
            ])
            ->selectRaw('SUM(wid.quantity)::bigint as qty_inbound')
            ->selectRaw('MAX(wi.received_date) as last_inbound_date')
            ->selectRaw('COUNT(DISTINCT wid.id)::bigint as inbound_rows')
            ->groupBy(
                's.id',
                's.name',
                's.country',
                's.phone',
                'p.id',
                'p.name',
                'p.code'
            );

        $query = DB::query()
            ->fromSub($inboundBySupplierProduct, 'ib')
            ->leftJoinSub($stockNowByProduct, 'stk', function ($join): void {
                $join->on('stk.product_id', '=', 'ib.product_id');
            })
            ->select([
                'ib.supplier_id',
                'ib.supplier_name',
                'ib.supplier_country',
                'ib.supplier_phone',
                'ib.product_name',
            ])
            ->selectRaw(
                "NULLIF(string_agg(DISTINCT NULLIF(ib.product_code, ''), ', '), '') as product_codes"
            )
            ->selectRaw('SUM(ib.qty_inbound)::bigint as qty_inbound')
            ->selectRaw('MAX(ib.last_inbound_date) as last_inbound_date')
            ->selectRaw('SUM(ib.inbound_rows)::bigint as inbound_rows')
            ->selectRaw('COALESCE(SUM(stk.stok_now), 0)::bigint as stok_now')
            ->groupBy(
                'ib.supplier_id',
                'ib.supplier_name',
                'ib.supplier_country',
                'ib.supplier_phone',
                'ib.product_name'
            );

        if ($filters['has_stock']) {
            $query->havingRaw('COALESCE(SUM(stk.stok_now), 0) > 0');
        }

        return $query;
    }

    private function buildInboundBaseQuery(
        array $filters,
        bool $applyProductId = true
    ): Builder {
        $query = DB::table('warehouse_inbound_details as wid')
            ->join(
                'warehouse_inbounds as wi',
                'wi.id',
                '=',
                'wid.warehouse_inbound_id'
            )
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->join('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->whereNull('s.deleted_at');

        if ($filters['supplier_id']) {
            $query->where('s.id', $filters['supplier_id']);
        }

        if ($applyProductId && $filters['product_id']) {
            $pickedName = DB::table('products')
                ->where('id', $filters['product_id'])
                ->value('name');

            if ($pickedName) {
                $query->where('p.name', $pickedName);
            }
        }

        if ($filters['date_from']) {
            $query->whereDate('wi.received_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('wi.received_date', '<=', $filters['date_to']);
        }

        if ($filters['keyword'] !== '') {
            $keyword = $filters['keyword'];

            $query->where(function ($where) use ($keyword): void {
                $where->where('p.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('p.code', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.country', 'ILIKE', "%{$keyword}%");
            });
        }

        return $query;
    }

    private function buildSuppliersForProductQuery(array $filters): Builder
    {
        $query = DB::table('warehouse_inbound_details as wid')
            ->join(
                'warehouse_inbounds as wi',
                'wi.id',
                '=',
                'wid.warehouse_inbound_id'
            )
            ->join('products as p', 'p.id', '=', 'wid.product_id')
            ->join('suppliers as s', 's.id', '=', 'wi.supplier_id')
            ->whereNull('s.deleted_at')
            ->select([
                'p.name as product_name',
                's.id as supplier_id',
                's.name as supplier_name',
            ])
            ->selectRaw('SUM(wid.quantity)::bigint as total_qty')
            ->selectRaw('MAX(wi.received_date) as last_inbound')
            ->groupBy('p.name', 's.id', 's.name');

        if ($filters['product_id']) {
            $targetName = DB::table('products')
                ->where('id', $filters['product_id'])
                ->value('name');

            if ($targetName) {
                $query->where('p.name', $targetName);
            }
        } elseif ($filters['keyword'] !== '') {
            $query->where('p.name', 'ILIKE', "%{$filters['keyword']}%");
        }

        if ($filters['date_from']) {
            $query->whereDate('wi.received_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('wi.received_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return true;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
