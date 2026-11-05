<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\DeadstockExport;
use App\Exports\SlowMovingExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SlowDeadstockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-slow-deadstock')
            ->only(['index', 'exportSlow', 'exportDead']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->query('warehouse_id', $user->warehouse_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $slow = collect();
        $dead = collect();

        if ($warehouseId) {
            $slow = $this->slowMoving($warehouseId, $startDate, $endDate);
            $dead = $this->deadStock($warehouseId, $startDate);
        }

        return Inertia::render('Monitoring/Report', [
            'title' => 'Slow Deadstock',
            'description' => 'Laporan monitoring.',
            'data' => [
            'warehouses' => $warehouses,
            'filters'    => [
                'warehouse_id' => $warehouseId,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
            ],
            'slow'       => $slow,
            'dead'       => $dead,
        ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'exports' => [
                    'slow' => route('monitoring.slow-deadstock.export-slow'),
                    'dead' => route('monitoring.slow-deadstock.export-dead'),
                ],
            ],
        ]);
    }

    public function exportSlow(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->query('warehouse_id', $user->warehouse_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if (!$warehouseId) {
            return back()->with('error', 'Gudang harus dipilih terlebih dahulu.');
        }

        $rows = $this->slowMoving($warehouseId, $startDate, $endDate);

        $warehouseName = DB::table('warehouses')
            ->where('id', $warehouseId)
            ->value('name') ?? '-';

        $filename = 'slow-moving-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new SlowMovingExport($rows, [
                'warehouse_name' => $warehouseName,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
            ]),
            $filename
        );
    }

    public function exportDead(Request $request)
    {
        $user = $request->user();

        $warehouseId = $request->query('warehouse_id', $user->warehouse_id ?? null);

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if (!$warehouseId) {
            return back()->with('error', 'Gudang harus dipilih terlebih dahulu.');
        }

        $rows = $this->deadStock($warehouseId, $startDate);

        $warehouseName = DB::table('warehouses')
            ->where('id', $warehouseId)
            ->value('name') ?? '-';

        $filename = 'deadstock-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new DeadstockExport($rows, [
                'warehouse_name' => $warehouseName,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
            ]),
            $filename
        );
    }

    private function slowMoving(string $warehouseId, string $startDate, string $endDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $selectColumns = [
            'p.id as product_id',
            'p.code as product_code',
            'p.name as product_name',
            DB::raw("(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END) as status"),
            'rm.name as room_name',
            'rk.name as rack_name',
            'lr.name as layer_name',
        ];

        $groupingColumns = [
            'p.id',
            'p.code',
            'p.name',
            DB::raw("(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END)"),
            'rm.name',
            'rk.name',
            'lr.name',
        ];

        return DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->whereIn('i.status', ['warehouse_stock', 'warehouse_processing']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('wi.warehouse_id', $warehouseId)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereNull('i.old_item_id')
                        ->whereBetween('inh.received_date', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereBetween('i.created_at', [$start, $end]);
                });
            })
            ->select(array_merge($selectColumns, [
                DB::raw('COUNT(wi.id) as quantity'),
            ]))
            ->groupBy($groupingColumns)
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function deadStock(string $warehouseId, string $startDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();

        $selectColumns = [
            'p.id as product_id',
            'p.code as product_code',
            'p.name as product_name',
            DB::raw("(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END) as status"),
            'rm.name as room_name',
            'rk.name as rack_name',
            'lr.name as layer_name',
        ];

        $groupingColumns = [
            'p.id',
            'p.code',
            'p.name',
            DB::raw("(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END)"),
            'rm.name',
            'rk.name',
            'lr.name',
        ];

        return DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->whereIn('i.status', ['warehouse_stock', 'warehouse_processing']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('wi.warehouse_id', $warehouseId)
            ->where(function ($q) use ($start) {
                $q->where(function ($sub) use ($start) {
                    $sub->whereNull('i.old_item_id')
                        ->whereDate('inh.received_date', '<=', $start->toDateString());
                })->orWhere(function ($sub) use ($start) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereDate('i.created_at', '<=', $start->toDateString());
                });
            })
            ->select(array_merge($selectColumns, [
                DB::raw('COUNT(wi.id) as quantity'),
            ]))
            ->groupBy($groupingColumns)
            ->orderBy('p.name')
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
