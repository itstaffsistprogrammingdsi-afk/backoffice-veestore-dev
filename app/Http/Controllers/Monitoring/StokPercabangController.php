<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\StokPercabangExport;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StokPercabangController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'store_id' => ['nullable', 'uuid'],
            'q' => ['nullable', 'string', 'max:100'],
            'min' => ['nullable', 'integer', 'min:0'],
        ]);

        $filters = [
            'store_id' => trim((string) $request->input('store_id', '')),
            'q' => trim((string) $request->input('q', '')),
            'min' => (int) $request->input('min', 0),
        ];

        $rows = $this->buildFilteredQuery($filters)
            ->paginate(10)
            ->withQueryString();

        $summary = $this->summary($filters);

        $stores = Store::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Stok per Cabang',
            'description' => 'Posisi stok fisik per cabang. Stok tersedia = Good + Good After Repair - reservasi aktif; barang Bad ditampilkan terpisah dan tidak dihitung tersedia. Hanya status store_stock dan store_display yang masuk. Klik angka Qty Reservasi untuk melihat order dan pihak yang menahan stok.',
            'data' => [
                'rows' => $rows,
                'stores' => $stores,
                'filters' => $filters,
                'summary' => $summary,
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'export' => route('monitoring.stok-percabang.export'),
                'reservations' => route('monitoring.stok-percabang.reservations'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'store_id' => ['nullable', 'uuid'],
            'q' => ['nullable', 'string', 'max:100'],
            'min' => ['nullable', 'integer', 'min:0'],
        ]);

        $filters = [
            'store_id' => trim((string) $request->input('store_id', '')),
            'q' => trim((string) $request->input('q', '')),
            'min' => (int) $request->input('min', 0),
        ];

        $rows = $this->buildFilteredQuery($filters)->get()->map(function ($r) {
            $r->qty_store = (int) $r->qty_store;
            $r->qty_display = (int) $r->qty_display;
            $r->qty_real = (int) $r->qty_real;
            $r->qty_reserved = (int) $r->qty_reserved;
            $r->qty_available = (int) $r->qty_available;
            $r->qty_good = (int) $r->qty_good;
            $r->qty_good_after_repair = (int) $r->qty_good_after_repair;
            $r->qty_bad = (int) $r->qty_bad;
            $r->total_qty = (int) $r->total_qty;

            return $r;
        });

        $filename = 'laporan-stok-per-cabang-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new StokPercabangExport($rows, $filters),
            $filename
        );
    }

    private function summary(array $filters): array
    {
        $summary = DB::query()
            ->fromSub($this->buildFilteredQuery($filters), 'stock_summary')
            ->selectRaw('
                COALESCE(SUM(qty_real), 0)::int as total_qty_real,
                COALESCE(SUM(qty_reserved), 0)::int as total_qty_reserved,
                COALESCE(SUM(qty_available), 0)::int as total_qty_available,
                COALESCE(SUM(qty_good), 0)::int as total_good,
                COALESCE(SUM(qty_good_after_repair), 0)::int as total_good_after_repair,
                COALESCE(SUM(qty_bad), 0)::int as total_bad,
                COALESCE(SUM(qty_store), 0)::int as total_store_stock,
                COALESCE(SUM(qty_display), 0)::int as total_store_display,
                COUNT(*)::int as total_rows,
                COALESCE(SUM(total_qty), 0)::int as total_qty
            ')
            ->first();

        return (array) $summary;
    }

    private function buildFilteredQuery(array $filters)
    {
        $storeId = $filters['store_id'] ?? '';
        $q = $filters['q'] ?? '';
        $minStock = (int) ($filters['min'] ?? 0);

        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');
        $hasReservations = Schema::hasTable('stock_reservations');

        $reservationQuery = $hasReservations
            ? app(StockReservationService::class)->remainingByLocation('store')
            : DB::query()
                ->selectRaw('NULL::uuid as store_id, NULL::uuid as product_id, 0::int as qty_reserved')
                ->whereRaw('1 = 0');

        $query = DB::table('items as i')
            ->join('store_items as si', 'si.item_id', '=', 'i.id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as c', 'c.id', '=', 'i.current_condition_id')
            ->when($hasUnits, fn ($q2) => $q2->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->leftJoinSub($reservationQuery, 'sr', function ($join) {
                $join->on('sr.location_id', '=', 'si.store_id')
                    ->on('sr.product_id', '=', 'i.product_id');
            })
            ->whereNull('s.deleted_at')
            ->whereIn(DB::raw('LOWER(i.status::text)'), ['store_stock', 'store_display'])
            ->when($storeId !== '', fn ($q2) => $q2->where('si.store_id', $storeId))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($w) use ($q) {
                    $w->where('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                si.store_id,
                s.name as store_name,
                s.code as store_code,
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
                SUM(CASE WHEN LOWER(i.status::text) = 'store_stock' THEN 1 ELSE 0 END)::int as qty_store,

                SUM(CASE WHEN LOWER(i.status::text) = 'store_display' THEN 1 ELSE 0 END)::int as qty_display,

                COUNT(i.id)::int as qty_real,
                COALESCE(MAX(sr.qty_reserved), 0)::int as qty_reserved,
                GREATEST(
                    SUM(
                        CASE
                            WHEN LOWER(COALESCE(c.status::text, '')) IN ('good', 'good_after_repair')
                            THEN 1 ELSE 0
                        END
                    ) - COALESCE(MAX(sr.qty_reserved), 0),
                    0
                )::int as qty_available,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(c.status::text, '')) = 'good'
                        THEN 1 ELSE 0
                    END
                )::int as qty_good,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(c.status::text, '')) = 'good_after_repair'
                        THEN 1 ELSE 0
                    END
                )::int as qty_good_after_repair,

                SUM(
                    CASE
                        WHEN LOWER(COALESCE(c.status::text, '')) = 'bad'
                        THEN 1 ELSE 0
                    END
                )::int as qty_bad,

                COUNT(i.id)::int as total_qty
            ")
            ->groupBy(
                'si.store_id',
                's.name',
                's.code',
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
            ->orderBy('s.name')
            ->orderByDesc('total_qty')
            ->orderBy('p.name');

        return $query;
    }

    public function reservationDetails(Request $request, StockReservationService $reservations)
    {
        $data = $request->validate([
            'store_id' => ['required', 'uuid'],
            'product_id' => ['required', 'uuid'],
        ]);

        return response()->json([
            'rows' => $reservations->details('store', $data['store_id'], $data['product_id']),
        ]);
    }
}
