<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\StoreStockOpnameReportExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class StoreStockOpnameReportController extends Controller
{
    public function index(Request $request)
    {
        $storeId  = $request->input('store_id');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $search   = trim((string) $request->input('search'));
        $perPage  = 25;

        $tables = $this->resolveOpnameTables();
        $errorMessage = null;

        if (!$tables) {
            $rows = $this->emptyPaginator($perPage, $request);
            $stores = $this->getStores();

            return view('content.monitoring.store-stock-opnames.index', [
                'rows'         => $rows,
                'stores'       => $stores,
                'storeId'      => $storeId,
                'dateFrom'     => $dateFrom,
                'dateTo'       => $dateTo,
                'search'       => $search,
                'errorMessage' => 'Tabel atau foreign key stok opname belum berhasil dipetakan. Sesuaikan kandidat nama tabel/kolom di controller.',
            ]);
        }

        $sessionTable            = $tables['session_table'];
        $sessionItemTable        = $tables['session_item_table'];
        $scanTable               = $tables['scan_table'];
        $expectedTable           = $tables['expected_table'];
        $sessionFkInItem         = $tables['session_fk_in_item'];
        $sessionItemFkInScan     = $tables['session_item_fk_in_scan'];
        $sessionItemFkInExpected = $tables['session_item_fk_in_expected'];
        $reportTimeExpression    = $this->sessionReportTimeExpression($sessionTable);

        // FIXED: field pasti dari tabel store_opname_session_items
        $reasonColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_reason')
            ? 'discrepancy_reason'
            : null;

        $noteColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_notes')
            ? 'discrepancy_notes'
            : null;

        $query = DB::table($sessionItemTable . ' as osi')
            ->join($sessionTable . ' as os', 'os.id', '=', "osi.$sessionFkInItem");

        if (Schema::hasTable('stores') && Schema::hasColumn($sessionTable, 'store_id')) {
            $query->leftJoin('stores as st', 'st.id', '=', 'os.store_id');
        }

        if (Schema::hasTable('locations') && Schema::hasColumn($sessionTable, 'location_id')) {
            $query->leftJoin('locations as loc', 'loc.id', '=', 'os.location_id');
        }

        if (Schema::hasTable('products') && Schema::hasColumn($sessionItemTable, 'product_id')) {
            $query->leftJoin('products as p', 'p.id', '=', 'osi.product_id');
        }

        if (Schema::hasTable('users') && Schema::hasColumn($sessionTable, 'approved_by')) {
            $query->leftJoin('users as approved_user', 'approved_user.id', '=', 'os.approved_by');
        }

        if ($storeId && Schema::hasColumn($sessionTable, 'store_id')) {
            $query->where('os.store_id', $storeId);
        }

        if ($dateFrom && $reportTimeExpression !== 'NULL') {
            $query->whereDate(DB::raw($reportTimeExpression), '>=', $dateFrom);
        }

        if ($dateTo && $reportTimeExpression !== 'NULL') {
            $query->whereDate(DB::raw($reportTimeExpression), '<=', $dateTo);
        }

        if ($search !== '') {
            $keyword = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($keyword, $sessionTable, $sessionItemTable, $reasonColumn, $noteColumn) {
                if (Schema::hasTable('products')) {
                    $q->whereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionTable, 'name')) {
                    $q->orWhereRaw("LOWER(COALESCE(os.name, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionTable, 'code')) {
                    $q->orWhereRaw("LOWER(COALESCE(os.code, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionItemTable, 'sku')) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.sku, '')) LIKE ?", [$keyword]);
                }

                if ($reasonColumn) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.$reasonColumn, '')) LIKE ?", [$keyword]);
                }

                if ($noteColumn) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.$noteColumn, '')) LIKE ?", [$keyword]);
                }
            });
        }

        $selects = ['osi.id'];

        $selects[] = Schema::hasTable('stores')
            ? DB::raw("COALESCE(st.name, '-') as store_name")
            : DB::raw("'-' as store_name");

        $selects[] = Schema::hasTable('locations')
            ? DB::raw("COALESCE(loc.name, '-') as location_name")
            : DB::raw("'-' as location_name");

        $selects[] = Schema::hasTable('products')
            ? DB::raw("COALESCE(p.name, '-') as product_name")
            : DB::raw("'-' as product_name");

        $selects[] = Schema::hasTable('products')
            ? DB::raw("COALESCE(p.code, '-') as product_code")
            : DB::raw("'-' as product_code");

        $selects[] = DB::raw("$reportTimeExpression as report_at");

        $selects[] = Schema::hasTable('users') && Schema::hasColumn($sessionTable, 'approved_by')
            ? DB::raw("COALESCE(approved_user.name, '-') as approved_by_name")
            : DB::raw("'-' as approved_by_name");

        if (Schema::hasColumn($sessionTable, 'name')) {
            $selects[] = 'os.name as opname_code';
        } elseif (Schema::hasColumn($sessionTable, 'code')) {
            $selects[] = 'os.code as opname_code';
        } else {
            $selects[] = DB::raw("'-' as opname_code");
        }

        if (Schema::hasColumn($sessionItemTable, 'system_stock')) {
            $selects[] = 'osi.system_stock';
        } else {
            $selects[] = DB::raw('0 as system_stock');
        }

        if (Schema::hasColumn($sessionItemTable, 'physical_stock')) {
            $selects[] = 'osi.physical_stock';
        } else {
            $selects[] = DB::raw('0 as physical_stock');
        }

        if ($reasonColumn) {
            $selects[] = "osi.$reasonColumn as difference_reason";
        } else {
            $selects[] = DB::raw("'-' as difference_reason");
        }

        if ($noteColumn) {
            $selects[] = "osi.$noteColumn as additional_note";
        } else {
            $selects[] = DB::raw("'-' as additional_note");
        }

        $rows = $query
            ->select($selects)
            ->orderByRaw($reportTimeExpression . ' DESC NULLS LAST')
            ->orderByDesc('osi.id')
            ->paginate($perPage)
            ->through(function ($row) use (
                $scanTable,
                $expectedTable,
                $sessionItemFkInScan,
                $sessionItemFkInExpected
            ) {
                $qtySystem = (int) ($row->system_stock ?? 0);
                $qtyScan   = (int) ($row->physical_stock ?? 0);
                $selisih   = $qtyScan - $qtySystem;

                if ($selisih === 0) {
                    $statusSummary = 'Balanced';
                } elseif ($selisih < 0) {
                    $statusSummary = 'Shortage';
                } else {
                    $statusSummary = 'Surplus';
                }

                $conditionNames = collect([]);
                $destroyedQty = 0;

                if (Schema::hasTable($scanTable) && Schema::hasColumn($scanTable, $sessionItemFkInScan)) {
                    if (Schema::hasColumn($scanTable, 'item_condition_id') && Schema::hasTable('item_conditions')) {
                        $conditionNames = DB::table($scanTable . ' as oss')
                            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'oss.item_condition_id')
                            ->where("oss.$sessionItemFkInScan", $row->id)
                            ->whereNotNull('oss.item_condition_id')
                            ->distinct()
                            ->pluck('ic.name')
                            ->filter()
                            ->values();

                        $destroyedScanQuery = DB::table($scanTable . ' as oss')
                            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'oss.item_condition_id')
                            ->where("oss.$sessionItemFkInScan", $row->id)
                            ->where(function ($q) {
                                $q->whereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%hancur total%'])
                                  ->orWhereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%destroy%'])
                                  ->orWhereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%rusak total%']);
                            });

                        $qtyColumn = $this->firstExistingColumn($scanTable, [
                            'qty',
                            'quantity',
                            'scan_qty',
                            'scanned_qty',
                        ]);

                        $destroyedQty = $qtyColumn
                            ? (int) $destroyedScanQuery->sum("oss.$qtyColumn")
                            : (int) $destroyedScanQuery->count();
                    } elseif (Schema::hasColumn($scanTable, 'item_condition_id')) {
                        $conditionNames = DB::table($scanTable)
                            ->where($sessionItemFkInScan, $row->id)
                            ->whereNotNull('item_condition_id')
                            ->distinct()
                            ->pluck('item_condition_id')
                            ->map(fn ($v) => (string) $v)
                            ->values();
                    }
                }

                $matchCount = 0;
                $missingCount = 0;
                $extraCount = 0;

                if (
                    Schema::hasTable($expectedTable) &&
                    Schema::hasColumn($expectedTable, $sessionItemFkInExpected) &&
                    Schema::hasColumn($expectedTable, 'scanned_at')
                ) {
                    $matchCount = DB::table($expectedTable)
                        ->where($sessionItemFkInExpected, $row->id)
                        ->whereNotNull('scanned_at')
                        ->count();

                    $missingCount = DB::table($expectedTable)
                        ->where($sessionItemFkInExpected, $row->id)
                        ->whereNull('scanned_at')
                        ->count();
                }

                if (
                    Schema::hasTable($scanTable) &&
                    Schema::hasColumn($scanTable, $sessionItemFkInScan) &&
                    Schema::hasColumn($scanTable, 'is_additional')
                ) {
                    $extraCount = DB::table($scanTable)
                        ->where($sessionItemFkInScan, $row->id)
                        ->where('is_additional', true)
                        ->count();
                }

                $row->report_time = !empty($row->report_at)
                    ? Carbon::parse($row->report_at)->format('d M Y H:i')
                    : '-';

                $row->qty_system = $qtySystem;
                $row->qty_scan = $qtyScan;
                $row->selisih = $selisih;
                $row->status_summary = $statusSummary;
                $row->condition_names = $conditionNames;
                $row->destroyed_qty = $destroyedQty;
                $row->difference_reason = !empty($row->difference_reason) ? $row->difference_reason : '-';
                $row->additional_note = !empty($row->additional_note) ? $row->additional_note : '-';
                $row->rfid_summary = [
                    'match'   => $matchCount,
                    'missing' => $missingCount,
                    'extra'   => $extraCount,
                ];
                $row->show_detail_button = ($missingCount > 0 || $extraCount > 0);

                return $row;
            })
            ->withQueryString();

        $stores = $this->getStores();

        return view('content.monitoring.store-stock-opnames.index', [
            'rows'         => $rows,
            'stores'       => $stores,
            'storeId'      => $storeId,
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
            'search'       => $search,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'laporan-stok-opname-store-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StoreStockOpnameReportExport($this->getExportRows($request)),
            $filename
        );
    }

    public function rfidDetail($sessionItemId)
    {
        $tables = $this->resolveOpnameTables();

        if (!$tables) {
            return response()->json([
                'product_name' => '-',
                'detail'       => [],
                'message'      => 'Mapping tabel stok opname tidak ditemukan.',
            ], 422);
        }

        $sessionItemTable        = $tables['session_item_table'];
        $scanTable               = $tables['scan_table'];
        $expectedTable           = $tables['expected_table'];
        $sessionItemFkInScan     = $tables['session_item_fk_in_scan'];
        $sessionItemFkInExpected = $tables['session_item_fk_in_expected'];

        $reasonColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_reason')
            ? 'discrepancy_reason'
            : null;

        $noteColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_notes')
            ? 'discrepancy_notes'
            : null;

        $itemQuery = DB::table($sessionItemTable . ' as osi');

        if (Schema::hasTable('products') && Schema::hasColumn($sessionItemTable, 'product_id')) {
            $itemQuery->leftJoin('products as p', 'p.id', '=', 'osi.product_id');
        }

        $selectRaw = "
            osi.id,
            COALESCE(" . (Schema::hasTable('products') ? 'p.name' : "'-'") . ", '-') as product_name,
            COALESCE(" . (Schema::hasTable('products') ? 'p.code' : "'-'") . ", '-') as product_code
        ";

        if ($reasonColumn) {
            $selectRaw .= ", COALESCE(osi.$reasonColumn, '-') as difference_reason";
        } else {
            $selectRaw .= ", '-' as difference_reason";
        }

        if ($noteColumn) {
            $selectRaw .= ", COALESCE(osi.$noteColumn, '-') as additional_note";
        } else {
            $selectRaw .= ", '-' as additional_note";
        }

        $sessionItem = $itemQuery
            ->where('osi.id', $sessionItemId)
            ->selectRaw($selectRaw)
            ->first();

        if (!$sessionItem) {
            return response()->json([
                'product_name' => '-',
                'detail'       => [],
                'message'      => 'Session item tidak ditemukan.',
            ], 404);
        }

        $missingIdentifierColumn = $this->firstExistingColumn($expectedTable, [
            'rfid_tag_value',
            'identifier',
            'rfid',
            'epc',
            'tag_code',
        ]);

        $extraIdentifierColumn = $this->firstExistingColumn($scanTable, [
            'identifier',
            'rfid_tag_value',
            'rfid',
            'epc',
            'tag_code',
        ]);

        $rfidMissing = collect([]);
        if (
            Schema::hasTable($expectedTable) &&
            Schema::hasColumn($expectedTable, $sessionItemFkInExpected) &&
            Schema::hasColumn($expectedTable, 'scanned_at')
        ) {
            $rfidMissing = DB::table($expectedTable)
                ->where($sessionItemFkInExpected, $sessionItemId)
                ->whereNull('scanned_at')
                ->selectRaw(
                    $missingIdentifierColumn
                        ? "$missingIdentifierColumn as rfid_value"
                        : "'-' as rfid_value"
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'rfid_value'       => $row->rfid_value ?? '-',
                        'scanned_location' => '-',
                    ];
                });
        }

        $rfidExtra = collect([]);
        if (
            Schema::hasTable($scanTable) &&
            Schema::hasColumn($scanTable, $sessionItemFkInScan) &&
            Schema::hasColumn($scanTable, 'is_additional')
        ) {
            $rfidExtra = DB::table($scanTable)
                ->where($sessionItemFkInScan, $sessionItemId)
                ->where('is_additional', true)
                ->selectRaw(
                    $extraIdentifierColumn
                        ? "$extraIdentifierColumn as rfid_value"
                        : "'-' as rfid_value"
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'rfid_value'       => $row->rfid_value ?? '-',
                        'scanned_location' => '-',
                    ];
                });
        }

        $destroyedQty = 0;
        if (Schema::hasTable($scanTable) && Schema::hasColumn($scanTable, $sessionItemFkInScan)) {
            if (Schema::hasColumn($scanTable, 'item_condition_id') && Schema::hasTable('item_conditions')) {
                $destroyedScanQuery = DB::table($scanTable . ' as oss')
                    ->leftJoin('item_conditions as ic', 'ic.id', '=', 'oss.item_condition_id')
                    ->where("oss.$sessionItemFkInScan", $sessionItemId)
                    ->where(function ($q) {
                        $q->whereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%hancur total%'])
                          ->orWhereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%destroy%'])
                          ->orWhereRaw("LOWER(COALESCE(ic.name, '')) LIKE ?", ['%rusak total%']);
                    });

                $qtyColumn = $this->firstExistingColumn($scanTable, [
                    'qty',
                    'quantity',
                    'scan_qty',
                    'scanned_qty',
                ]);

                $destroyedQty = $qtyColumn
                    ? (int) $destroyedScanQuery->sum("oss.$qtyColumn")
                    : (int) $destroyedScanQuery->count();
            }
        }

        $maxRows = max($rfidMissing->count(), $rfidExtra->count());
        $detail = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $detail[] = [
                'no'               => $i + 1,
                'rfid_missing'     => $rfidMissing[$i]['rfid_value'] ?? '-',
                'missing_location' => $rfidMissing[$i]['scanned_location'] ?? '-',
                'rfid_extra'       => $rfidExtra[$i]['rfid_value'] ?? '-',
                'extra_location'   => $rfidExtra[$i]['scanned_location'] ?? '-',
            ];
        }

        return response()->json([
            'product_name'      => ($sessionItem->product_name ?? '-') . ' (' . ($sessionItem->product_code ?? '-') . ')',
            'difference_reason' => $sessionItem->difference_reason ?? '-',
            'additional_note'   => $sessionItem->additional_note ?? '-',
            'destroyed_qty'     => $destroyedQty,
            'detail'            => $detail,
        ]);
    }

    private function resolveOpnameTables(): ?array
    {
        $sessionCandidates = [
            'store_opname_sessions',
            'opname_sessions',
            'stock_opname_sessions',
            'store_stock_opnames',
            'stock_opname_stores',
        ];

        $sessionItemCandidates = [
            'store_opname_session_items',
            'opname_session_items',
            'stock_opname_session_items',
            'store_stock_opname_items',
            'store_stock_opname_details',
            'stock_opname_store_details',
        ];

        $scanCandidates = [
            'store_opname_session_scans',
            'opname_session_scans',
            'stock_opname_session_scans',
            'store_stock_opname_scans',
            'stock_opname_store_scans',
        ];

        $expectedCandidates = [
            'store_opname_session_expected_items',
            'opname_session_expected_items',
            'stock_opname_session_expected_items',
            'store_stock_opname_expected_items',
            'stock_opname_store_expected_items',
        ];

        $sessionTable = collect($sessionCandidates)->first(fn ($table) => Schema::hasTable($table));
        $sessionItemTable = collect($sessionItemCandidates)->first(fn ($table) => Schema::hasTable($table));
        $scanTable = collect($scanCandidates)->first(fn ($table) => Schema::hasTable($table));
        $expectedTable = collect($expectedCandidates)->first(fn ($table) => Schema::hasTable($table));

        if (!$sessionTable || !$sessionItemTable || !$scanTable || !$expectedTable) {
            return null;
        }

        $sessionFkInItem = $this->detectForeignKey($sessionItemTable, [
            'store_opname_session_id',
            'opname_session_id',
            'stock_opname_session_id',
            'store_stock_opname_id',
            'stock_opname_store_id',
            'session_id',
            'opname_id',
        ]);

        $sessionItemFkInScan = $this->detectForeignKey($scanTable, [
            'store_opname_session_item_id',
            'opname_session_item_id',
            'stock_opname_session_item_id',
            'store_stock_opname_item_id',
            'stock_opname_store_item_id',
            'session_item_id',
            'item_id',
        ]);

        $sessionItemFkInExpected = $this->detectForeignKey($expectedTable, [
            'store_opname_session_item_id',
            'opname_session_item_id',
            'stock_opname_session_item_id',
            'store_stock_opname_item_id',
            'stock_opname_store_item_id',
            'session_item_id',
            'item_id',
        ]);

        if (!$sessionFkInItem || !$sessionItemFkInScan || !$sessionItemFkInExpected) {
            return null;
        }

        return [
            'session_table'               => $sessionTable,
            'session_item_table'          => $sessionItemTable,
            'scan_table'                  => $scanTable,
            'expected_table'              => $expectedTable,
            'session_fk_in_item'          => $sessionFkInItem,
            'session_item_fk_in_scan'     => $sessionItemFkInScan,
            'session_item_fk_in_expected' => $sessionItemFkInExpected,
        ];
    }

    private function getExportRows(Request $request)
    {
        $tables = $this->resolveOpnameTables();

        if (!$tables) {
            return collect([]);
        }

        $sessionTable = $tables['session_table'];
        $sessionItemTable = $tables['session_item_table'];
        $sessionFkInItem = $tables['session_fk_in_item'];
        $reportTimeExpression = $this->sessionReportTimeExpression($sessionTable);
        $storeId = $request->input('store_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = trim((string) $request->input('search'));
        $reasonColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_reason') ? 'discrepancy_reason' : null;
        $noteColumn = Schema::hasColumn($sessionItemTable, 'discrepancy_notes') ? 'discrepancy_notes' : null;

        $query = DB::table($sessionItemTable . ' as osi')
            ->join($sessionTable . ' as os', 'os.id', '=', "osi.$sessionFkInItem");

        if (Schema::hasTable('stores') && Schema::hasColumn($sessionTable, 'store_id')) {
            $query->leftJoin('stores as st', 'st.id', '=', 'os.store_id');
        }

        if (Schema::hasTable('products') && Schema::hasColumn($sessionItemTable, 'product_id')) {
            $query->leftJoin('products as p', 'p.id', '=', 'osi.product_id');
        }

        if (Schema::hasTable('users') && Schema::hasColumn($sessionTable, 'approved_by')) {
            $query->leftJoin('users as approved_user', 'approved_user.id', '=', 'os.approved_by');
        }

        if ($storeId && Schema::hasColumn($sessionTable, 'store_id')) {
            $query->where('os.store_id', $storeId);
        }

        if ($dateFrom && $reportTimeExpression !== 'NULL') {
            $query->whereDate(DB::raw($reportTimeExpression), '>=', $dateFrom);
        }

        if ($dateTo && $reportTimeExpression !== 'NULL') {
            $query->whereDate(DB::raw($reportTimeExpression), '<=', $dateTo);
        }

        if ($search !== '') {
            $keyword = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($keyword, $sessionTable, $sessionItemTable, $reasonColumn, $noteColumn) {
                if (Schema::hasTable('products')) {
                    $q->whereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionTable, 'name')) {
                    $q->orWhereRaw("LOWER(COALESCE(os.name, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionTable, 'code')) {
                    $q->orWhereRaw("LOWER(COALESCE(os.code, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasColumn($sessionItemTable, 'sku')) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.sku, '')) LIKE ?", [$keyword]);
                }

                if (Schema::hasTable('users') && Schema::hasColumn($sessionTable, 'approved_by')) {
                    $q->orWhereRaw("LOWER(COALESCE(approved_user.name, '')) LIKE ?", [$keyword]);
                }

                if ($reasonColumn) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.$reasonColumn, '')) LIKE ?", [$keyword]);
                }

                if ($noteColumn) {
                    $q->orWhereRaw("LOWER(COALESCE(osi.$noteColumn, '')) LIKE ?", [$keyword]);
                }
            });
        }

        $opnameCode = Schema::hasColumn($sessionTable, 'name')
            ? 'os.name as opname_code'
            : (Schema::hasColumn($sessionTable, 'code') ? 'os.code as opname_code' : "'-' as opname_code");
        $approvedBy = Schema::hasTable('users') && Schema::hasColumn($sessionTable, 'approved_by')
            ? "COALESCE(approved_user.name, '-') as approved_by_name"
            : "'-' as approved_by_name";
        $storeName = Schema::hasTable('stores') ? "COALESCE(st.name, '-') as store_name" : "'-' as store_name";
        $productName = Schema::hasTable('products') ? "COALESCE(p.name, '-') as product_name" : "'-' as product_name";
        $productCode = Schema::hasTable('products') ? "COALESCE(p.code, '-') as product_code" : "'-' as product_code";
        $systemStock = Schema::hasColumn($sessionItemTable, 'system_stock') ? 'osi.system_stock' : '0 as system_stock';
        $physicalStock = Schema::hasColumn($sessionItemTable, 'physical_stock') ? 'osi.physical_stock' : '0 as physical_stock';
        $differenceReason = $reasonColumn ? "osi.$reasonColumn as difference_reason" : "'-' as difference_reason";
        $additionalNote = $noteColumn ? "osi.$noteColumn as additional_note" : "'-' as additional_note";

        return $query
            ->selectRaw(implode(', ', [
                $reportTimeExpression . ' as report_at',
                $storeName,
                $opnameCode,
                $approvedBy,
                $productCode,
                $productName,
                $systemStock,
                $physicalStock,
                $differenceReason,
                $additionalNote,
            ]))
            ->orderByRaw($reportTimeExpression . ' DESC NULLS LAST')
            ->orderByDesc('osi.id')
            ->get()
            ->map(function ($row) {
                $row->system_stock = (int) ($row->system_stock ?? 0);
                $row->physical_stock = (int) ($row->physical_stock ?? 0);
                $row->difference = $row->physical_stock - $row->system_stock;
                $row->status_summary = $row->difference === 0
                    ? 'Balanced'
                    : ($row->difference < 0 ? 'Shortage' : 'Surplus');

                return $row;
            });
    }

    private function sessionReportTimeExpression(string $sessionTable): string
    {
        $columns = collect(['approved_at', 'finished_at', 'scheduled_at', 'created_at'])
            ->filter(fn ($column) => Schema::hasColumn($sessionTable, $column))
            ->map(fn ($column) => "os.$column")
            ->values();

        return $columns->isNotEmpty()
            ? 'COALESCE(' . $columns->implode(', ') . ')'
            : 'NULL';
    }

    private function detectForeignKey(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function getStores()
    {
        if (!Schema::hasTable('stores')) {
            return collect([]);
        }

        $query = DB::table('stores')->select('id', 'name');

        if (Schema::hasColumn('stores', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('name')->get();
    }

    private function emptyPaginator(int $perPage, Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect([]),
            0,
            $perPage,
            LengthAwarePaginator::resolveCurrentPage(),
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
