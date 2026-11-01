<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\StokWmsExport;
use App\Http\Controllers\Controller;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class StokWmsController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'warehouse_id' => trim((string) $request->input('warehouse_id', '')),
            'q'            => trim((string) $request->input('q', '')),
            'min'          => (int) $request->input('min', 0),
        ];

        $rows = $this->buildFilteredQuery($filters)
            ->paginate(10)
            ->withQueryString();

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Stok Wms',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'       => $rows,
            'warehouses' => $warehouses,
            'filters'    => $filters,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        $filters = [
            'warehouse_id' => trim((string) $request->input('warehouse_id', '')),
            'q'            => trim((string) $request->input('q', '')),
            'min'          => (int) $request->input('min', 0),
        ];

        $rows = $this->buildFilteredQuery($filters)->get()->map(function ($r) {
            $r->qty_good              = (int) $r->qty_good;
            $r->qty_good_after_repair = (int) $r->qty_good_after_repair;
            $r->qty_bad               = (int) $r->qty_bad;
            $r->total_qty             = (int) $r->total_qty;

            return $r;
        });

        $filename = 'laporan-stok-wms-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StokWmsExport($rows, $filters),
            $filename
        );
    }

    private function buildFilteredQuery(array $filters)
    {
        $q           = $filters['q'] ?? '';
        $warehouseId = $filters['warehouse_id'] ?? '';
        $minStock    = (int) ($filters['min'] ?? 0);

        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');
        $hasReservations = Schema::hasTable('stock_reservations');

        $reservationQuery = $hasReservations
            ? app(StockReservationService::class)->remainingByLocation('warehouse')
            : DB::query()
                ->selectRaw('NULL::uuid as warehouse_id, NULL::uuid as product_id, 0::int as qty_reserved')
                ->whereRaw('1 = 0');

        $query = DB::table('items as i')
            ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->when($hasUnits, fn ($q2) => $q2->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->leftJoinSub($reservationQuery, 'sr', function ($join) {
                $join->on('sr.location_id', '=', 'wi.warehouse_id')
                    ->on('sr.product_id', '=', 'i.product_id');
            })
            ->whereNull('w.deleted_at')
            ->whereRaw("LOWER(i.status::text) = 'warehouse_stock'")
            ->when($warehouseId !== '', fn ($q2) => $q2->where('wi.warehouse_id', $warehouseId))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($w2) use ($q) {
                    $w2->where('p.code', 'ILIKE', "%{$q}%")
                       ->orWhere('p.name', 'ILIKE', "%{$q}%")
                       ->orWhere('w.name', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                wi.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name
            ')
            ->when(
                $hasUnits,
                fn ($q2) => $q2->selectRaw("COALESCE(u.name, '') as unit_name"),
                fn ($q2) => $q2->selectRaw("'' as unit_name")
            )
            ->selectRaw("
                COUNT(i.id)::int as qty_real,
                COALESCE(MAX(sr.qty_reserved), 0)::int as qty_reserved,
                GREATEST(COUNT(i.id) - COALESCE(MAX(sr.qty_reserved), 0), 0)::int as qty_available,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(ic.status::text, '')) = 'good'
                        THEN 1 ELSE 0
                    END
                )::int as qty_good,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(ic.status::text, '')) = 'good_after_repair'
                        THEN 1 ELSE 0
                    END
                )::int as qty_good_after_repair,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(ic.status::text, '')) = 'bad'
                        THEN 1 ELSE 0
                    END
                )::int as qty_bad,

                COUNT(i.id)::int as total_qty
            ")
            ->groupBy(
                'wi.warehouse_id',
                'w.name',
                'w.code',
                'p.id',
                'p.code',
                'p.name',
                ...($hasUnits ? ['u.name'] : [])
            )
            ->when($minStock > 0, function ($q2) use ($minStock) {
                $q2->havingRaw(
                    'GREATEST(COUNT(i.id) - COALESCE(MAX(sr.qty_reserved), 0), 0) >= ?',
                    [$minStock]
                );
            })
            ->orderBy('w.name')
            ->orderByDesc('total_qty')
            ->orderBy('p.name');

        return $query;
    }

    public function reservationDetails(Request $request, StockReservationService $reservations)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'uuid'],
            'product_id' => ['required', 'uuid'],
        ]);

        return response()->json([
            'rows' => $reservations->details('warehouse', $data['warehouse_id'], $data['product_id']),
        ]);
    }
}
