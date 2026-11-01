<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\StoreDeadstockExport;
use App\Exports\StoreSlowMovingExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StoreSlowstockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-store-slowstock')
            ->only(['index', 'exportSlow', 'exportDead']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $storeId = $request->query('store_id', $user->store_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $slow = collect();
        $dead = collect();

        if ($storeId) {
            $slow = $this->slowMoving($storeId, $startDate, $endDate);
            $dead = $this->deadStock($storeId, $startDate);
        }

        return view('content.monitoring.store-slowstock.index', [
            'stores'    => $stores,
            'storeId'   => $storeId,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'slow'      => $slow,
            'dead'      => $dead,
        ]);
    }

    public function exportSlow(Request $request)
    {
        $user = $request->user();

        $storeId = $request->query('store_id', $user->store_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if (!$storeId) {
            return back()->with('error', 'Store harus dipilih terlebih dahulu.');
        }

        $rows = $this->slowMoving($storeId, $startDate, $endDate);

        $storeName = DB::table('stores')
            ->where('id', $storeId)
            ->value('name') ?? '-';

        $filename = 'store-slow-moving-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StoreSlowMovingExport($rows, [
                'store_name' => $storeName,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ]),
            $filename
        );
    }

    public function exportDead(Request $request)
    {
        $user = $request->user();

        $storeId = $request->query('store_id', $user->store_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if (!$storeId) {
            return back()->with('error', 'Store harus dipilih terlebih dahulu.');
        }

        $rows = $this->deadStock($storeId, $startDate);

        $storeName = DB::table('stores')
            ->where('id', $storeId)
            ->value('name') ?? '-';

        $filename = 'store-deadstock-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StoreDeadstockExport($rows, [
                'store_name' => $storeName,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ]),
            $filename
        );
    }

    private function slowMoving(string $storeId, string $startDate, string $endDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $stockStatusCase = "
            CASE
                WHEN i.status::text = 'store_display' THEN 'Display'
                WHEN i.status::text = 'store_stock' THEN 'Stock'
                ELSE i.status::text
            END
        ";

        $itemConditionCase = "
            CASE
                WHEN ic.name = 'Good' THEN 'Good'
                ELSE 'Bad'
            END
        ";

        return DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereIn('i.status', ['store_display', 'store_stock']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('si.store_id', $storeId)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereNull('i.old_item_id')
                        ->whereBetween('inh.received_date', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereBetween('i.created_at', [$start, $end]);
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$itemConditionCase} as item_condition"),
                DB::raw("{$stockStatusCase} as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw('COUNT(si.id) as quantity'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($itemConditionCase),
                DB::raw($stockStatusCase),
                'rm.name',
                'rk.name',
                'lr.name',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function deadStock(string $storeId, string $startDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();

        $stockStatusCase = "
            CASE
                WHEN i.status::text = 'store_display' THEN 'Display'
                WHEN i.status::text = 'store_stock' THEN 'Stock'
                ELSE i.status::text
            END
        ";

        $itemConditionCase = "
            CASE
                WHEN ic.name = 'Good' THEN 'Good'
                ELSE 'Bad'
            END
        ";

        return DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereIn('i.status', ['store_display', 'store_stock']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('si.store_id', $storeId)
            ->where(function ($q) use ($start) {
                $q->where(function ($sub) use ($start) {
                    $sub->whereNull('i.old_item_id')
                        ->whereDate('inh.received_date', '<=', $start->toDateString());
                })->orWhere(function ($sub) use ($start) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereDate('i.created_at', '<=', $start->toDateString());
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$itemConditionCase} as item_condition"),
                DB::raw("{$stockStatusCase} as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw('COUNT(si.id) as quantity'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($itemConditionCase),
                DB::raw($stockStatusCase),
                'rm.name',
                'rk.name',
                'lr.name',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function normalizeDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }
}