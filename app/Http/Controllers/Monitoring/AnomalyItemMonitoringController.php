<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnomalyItemMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->buildAnomalyQuery($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = $this->getSummary($filters);
        $demographicData = $this->getDemographicData($filters);

        $locations = DB::table('locations')
            ->when(Schema::hasColumn('locations', 'deleted_at'), function ($q) {
                $q->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $conditions = DB::table('item_conditions')
            ->when(Schema::hasColumn('item_conditions', 'deleted_at'), function ($q) {
                $q->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Anomali Item',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows' => $rows,
            'summary' => $summary,
            'demographicSummary' => $demographicData['summary'],
            'productDemographicRows' => $demographicData['products'],
            'areaDemographicRows' => $demographicData['areas'],
            'conditionDemographicRows' => $demographicData['conditions'],
            'anomalyDemographicRows' => $demographicData['anomalies'],
            'riskDemographicRows' => $demographicData['risks'],
            'locations' => $locations,
            'conditions' => $conditions,

            'q' => $filters['q'],
            'locationId' => $filters['location_id'],
            'conditionId' => $filters['condition_id'],
            'anomalyType' => $filters['anomaly_type'],
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'minMoveCount' => $filters['min_move_count'],
            'minDamageCount' => $filters['min_damage_count'],
            'perPage' => $filters['per_page'],
            'lastUpdate' => now()->format('d-m-Y H:i:s'),
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function locationHistoryJson(string $item)
    {
        $itemData = DB::table('items as i')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('locations as current_location', 'current_location.id', '=', 'i.current_location_id')
            ->leftJoin('item_conditions as current_condition', 'current_condition.id', '=', 'i.current_condition_id')
            ->leftJoin('warehouse_locations as current_wl', 'current_wl.location_id', '=', 'current_location.id')
            ->leftJoin('warehouses as current_warehouse', 'current_warehouse.id', '=', 'current_wl.warehouse_id')
            ->leftJoin('store_locations as current_sl', 'current_sl.location_id', '=', 'current_location.id')
            ->leftJoin('stores as current_store', 'current_store.id', '=', 'current_sl.store_id')
            ->select([
                'i.id',
                'i.product_id',
                'i.current_location_id',
                'i.current_condition_id',
                'i.rfid_tag_id',
                'i.status',
                'i.created_at',
                'p.name as product_name',
                'p.code as product_code',
                'current_location.name as current_location_name',
                'current_location.code as current_location_code',
                'current_condition.name as current_condition_name',
                'current_warehouse.name as current_warehouse_name',
                'current_warehouse.code as current_warehouse_code',
                'current_store.name as current_store_name',
                'current_store.code as current_store_code',
                DB::raw("CASE WHEN current_wl.location_id IS NOT NULL THEN 'Gudang WMS' WHEN current_sl.location_id IS NOT NULL THEN 'Store' ELSE 'Lokasi Lain' END as current_area_label"),
                DB::raw("COALESCE(current_warehouse.name, current_store.name, current_location.name, '-') as current_area_name"),
            ])
            ->where('i.id', $item);

        $this->applySoftDelete($itemData, 'items', 'i');
        $this->applySoftDelete($itemData, 'products', 'p');
        $this->applySoftDelete($itemData, 'locations', 'current_location');
        $this->applySoftDelete($itemData, 'item_conditions', 'current_condition');
        $this->applySoftDelete($itemData, 'warehouses', 'current_warehouse');
        $this->applySoftDelete($itemData, 'stores', 'current_store');

        $itemData = $itemData->first();

        abort_if(!$itemData, 404);

        $locationHistories = DB::table('item_location_histories as ilh')
            ->leftJoin('locations as l', 'l.id', '=', 'ilh.location_id')
            ->leftJoin('warehouse_locations as wl', 'wl.location_id', '=', 'l.id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'wl.warehouse_id')
            ->leftJoin('store_locations as sl', 'sl.location_id', '=', 'l.id')
            ->leftJoin('stores as s', 's.id', '=', 'sl.store_id')
            ->select([
                'ilh.id',
                'ilh.item_id',
                'ilh.location_id',
                'ilh.created_at',
                'ilh.note',
                'l.name as location_name',
                'l.code as location_code',
                'l.type as location_type',
                'w.id as warehouse_id',
                'w.name as warehouse_name',
                'w.code as warehouse_code',
                's.id as store_id',
                's.name as store_name',
                's.code as store_code',
                DB::raw("CASE WHEN wl.location_id IS NOT NULL THEN 'Gudang WMS' WHEN sl.location_id IS NOT NULL THEN 'Store' ELSE 'Lokasi Lain' END as area_label"),
                DB::raw("COALESCE(w.name, s.name, l.name, '-') as area_name"),
            ])
            ->where('ilh.item_id', $item)
            ->orderBy('ilh.created_at')
            ->orderBy('ilh.id')
            ->get()
            ->map(function ($row) {
                $row->created_at_label = $row->created_at
                    ? date('d-m-Y H:i', strtotime($row->created_at))
                    : '-';

                return $row;
            });

        $conditionHistories = DB::table('item_condition_histories as ich')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'ich.item_condition_id')
            ->leftJoin('users as u', 'u.id', '=', 'ich.changed_by')
            ->select([
                'ich.id',
                'ich.item_id',
                'ich.item_condition_id',
                'ich.changed_by',
                'ich.changed_at',
                'ich.reference_type',
                'ich.reference_id',
                'ich.created_at',
                'ic.name as condition_name',
                'ic.status as condition_status',
                'u.name as changed_by_name',
                'u.email as changed_by_email',
            ])
            ->where('ich.item_id', $item)
            ->orderByRaw('COALESCE(ich.changed_at, ich.created_at) asc')
            ->orderBy('ich.id')
            ->get()
            ->map(function ($row) {
                $date = $row->changed_at ?: $row->created_at;
                $row->date_label = $date ? date('d-m-Y H:i', strtotime($date)) : '-';

                return $row;
            });

        return response()->json([
            'item' => $itemData,
            'location_histories' => $locationHistories,
            'condition_histories' => $conditionHistories,
            'summary' => [
                'total_location_history' => $locationHistories->count(),
                'total_condition_history' => $conditionHistories->count(),
                'distinct_location' => $locationHistories->pluck('location_id')->filter()->unique()->count(),
                'distinct_condition' => $conditionHistories->pluck('item_condition_id')->filter()->unique()->count(),
                'total_wms_history' => $locationHistories->where('area_label', 'Gudang WMS')->count(),
                'total_store_history' => $locationHistories->where('area_label', 'Store')->count(),
            ],
        ]);
    }

    private function getFilters(Request $request): array
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $minMoveCount = (int) ($request->query('min_move_count', 3) ?: 3);
        $minDamageCount = (int) ($request->query('min_damage_count', 2) ?: 2);

        return [
            'q' => trim((string) $request->query('q', '')),
            'location_id' => $this->nullableString($request->query('location_id')),
            'condition_id' => $this->nullableString($request->query('condition_id')),
            'anomaly_type' => $this->nullableString($request->query('anomaly_type')),
            'start_date' => $this->normalizeDate($request->query('start_date')) ?: now()->subDays(30)->format('Y-m-d'),
            'end_date' => $this->normalizeDate($request->query('end_date')) ?: now()->format('Y-m-d'),
            'min_move_count' => max(1, $minMoveCount),
            'min_damage_count' => max(1, $minDamageCount),
            'per_page' => $perPage,
        ];
    }

    private function buildAnomalyQuery(array $filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $minMoveCount = (int) $filters['min_move_count'];
        $minDamageCount = (int) $filters['min_damage_count'];

        $latestLocationSub = DB::table('item_location_histories as ilh_latest')
            ->select([
                'ilh_latest.item_id',
                DB::raw('MAX(ilh_latest.created_at) as latest_location_changed_at'),
            ])
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('ilh_latest.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('ilh_latest.created_at', '<=', $endDate);
            })
            ->groupBy('ilh_latest.item_id');

        $locationStatsSub = DB::table('item_location_histories as ilh')
            ->select([
                'ilh.item_id',
                DB::raw('COUNT(*)::int as location_move_count'),
                DB::raw('COUNT(DISTINCT ilh.location_id)::int as distinct_location_count'),
                DB::raw('MIN(ilh.created_at) as first_location_move_at'),
                DB::raw('MAX(ilh.created_at) as last_location_move_at'),
            ])
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('ilh.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('ilh.created_at', '<=', $endDate);
            })
            ->groupBy('ilh.item_id');

        $latestLocationDetailSub = DB::table('item_location_histories as ilhd')
            ->joinSub($latestLocationSub, 'll', function ($join) {
                $join->on('ll.item_id', '=', 'ilhd.item_id')
                    ->on('ll.latest_location_changed_at', '=', 'ilhd.created_at');
            })
            ->select([
                'ilhd.item_id',
                'ilhd.location_id as latest_history_location_id',
                'ilhd.note as latest_location_note',
                'ilhd.created_at as latest_location_changed_at',
            ]);

        $conditionStatsSub = DB::table('item_condition_histories as ich')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'ich.item_condition_id')
            ->select([
                'ich.item_id',
                DB::raw('COUNT(*)::int as condition_change_count'),
                DB::raw("\n                    COUNT(\n                        CASE\n                            WHEN LOWER(COALESCE(ic.name, '')) LIKE '%rusak%'\n                              OR LOWER(COALESCE(ic.name, '')) LIKE '%damage%'\n                              OR LOWER(COALESCE(ic.name, '')) LIKE '%broken%'\n                              OR LOWER(COALESCE(ic.name, '')) LIKE '%repair%'\n                            THEN 1\n                        END\n                    )::int as damage_condition_count\n                "),
                DB::raw('MIN(ich.created_at) as first_condition_changed_at'),
                DB::raw('MAX(ich.created_at) as last_condition_changed_at'),
            ])
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate(DB::raw('COALESCE(ich.changed_at, ich.created_at)'), '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate(DB::raw('COALESCE(ich.changed_at, ich.created_at)'), '<=', $endDate);
            })
            ->groupBy('ich.item_id');

        $repairThenDamageSub = DB::table('item_condition_histories as damaged')
            ->leftJoin('item_conditions as damaged_condition', 'damaged_condition.id', '=', 'damaged.item_condition_id')
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(damaged_condition.name, '')) LIKE '%rusak%'")
                    ->orWhereRaw("LOWER(COALESCE(damaged_condition.name, '')) LIKE '%damage%'")
                    ->orWhereRaw("LOWER(COALESCE(damaged_condition.name, '')) LIKE '%broken%'");
            })
            ->whereExists(function ($exists) {
                $exists->select(DB::raw(1))
                    ->from('item_condition_histories as repaired')
                    ->leftJoin('item_conditions as repaired_condition', 'repaired_condition.id', '=', 'repaired.item_condition_id')
                    ->whereColumn('repaired.item_id', 'damaged.item_id')
                    ->whereRaw('COALESCE(repaired.changed_at, repaired.created_at) < COALESCE(damaged.changed_at, damaged.created_at)')
                    ->where(function ($q) {
                        $q->whereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%baik%'")
                            ->orWhereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%normal%'")
                            ->orWhereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%repair%'")
                            ->orWhereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%repaired%'")
                            ->orWhereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%fixed%'")
                            ->orWhereRaw("LOWER(COALESCE(repaired_condition.name, '')) LIKE '%selesai%'");
                    });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate(DB::raw('COALESCE(damaged.changed_at, damaged.created_at)'), '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate(DB::raw('COALESCE(damaged.changed_at, damaged.created_at)'), '<=', $endDate);
            })
            ->select([
                'damaged.item_id',
                DB::raw('COUNT(*)::int as repaired_then_damaged_count'),
                DB::raw('MAX(COALESCE(damaged.changed_at, damaged.created_at)) as last_repaired_then_damaged_at'),
            ])
            ->groupBy('damaged.item_id');

        $query = DB::table('items as i')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('locations as current_location', 'current_location.id', '=', 'i.current_location_id')
            ->leftJoin('item_conditions as current_condition', 'current_condition.id', '=', 'i.current_condition_id')
            ->leftJoinSub($locationStatsSub, 'ls', function ($join) {
                $join->on('ls.item_id', '=', 'i.id');
            })
            ->leftJoinSub($latestLocationDetailSub, 'lld', function ($join) {
                $join->on('lld.item_id', '=', 'i.id');
            })
            ->leftJoin('locations as latest_history_location', 'latest_history_location.id', '=', 'lld.latest_history_location_id')
            ->leftJoin('warehouse_locations as current_wl', 'current_wl.location_id', '=', 'current_location.id')
            ->leftJoin('warehouses as current_warehouse', 'current_warehouse.id', '=', 'current_wl.warehouse_id')
            ->leftJoin('store_locations as current_sl', 'current_sl.location_id', '=', 'current_location.id')
            ->leftJoin('stores as current_store', 'current_store.id', '=', 'current_sl.store_id')
            ->leftJoin('warehouse_locations as latest_wl', 'latest_wl.location_id', '=', 'latest_history_location.id')
            ->leftJoin('warehouses as latest_warehouse', 'latest_warehouse.id', '=', 'latest_wl.warehouse_id')
            ->leftJoin('store_locations as latest_sl', 'latest_sl.location_id', '=', 'latest_history_location.id')
            ->leftJoin('stores as latest_store', 'latest_store.id', '=', 'latest_sl.store_id')
            ->leftJoinSub($conditionStatsSub, 'cs', function ($join) {
                $join->on('cs.item_id', '=', 'i.id');
            })
            ->leftJoinSub($repairThenDamageSub, 'rtd', function ($join) {
                $join->on('rtd.item_id', '=', 'i.id');
            })
            ->select([
                'i.id',
                'i.product_id',
                'i.current_location_id',
                'i.current_condition_id',
                'i.rfid_tag_id',
                'i.status',
                'i.created_at',
                'p.name as product_name',
                'p.code as product_code',
                'current_location.name as current_location_name',
                'current_location.code as current_location_code',
                'current_condition.name as current_condition_name',
                'lld.latest_history_location_id',
                'latest_history_location.name as latest_history_location_name',
                'latest_history_location.code as latest_history_location_code',
                'current_warehouse.name as current_warehouse_name',
                'current_warehouse.code as current_warehouse_code',
                'current_store.name as current_store_name',
                'current_store.code as current_store_code',
                'latest_warehouse.name as latest_warehouse_name',
                'latest_warehouse.code as latest_warehouse_code',
                'latest_store.name as latest_store_name',
                'latest_store.code as latest_store_code',
                DB::raw("CASE WHEN current_wl.location_id IS NOT NULL THEN 'Gudang WMS' WHEN current_sl.location_id IS NOT NULL THEN 'Store' ELSE 'Lokasi Lain' END as current_area_label"),
                DB::raw("COALESCE(current_warehouse.name, current_store.name, current_location.name, '-') as current_area_name"),
                DB::raw("CASE WHEN latest_wl.location_id IS NOT NULL THEN 'Gudang WMS' WHEN latest_sl.location_id IS NOT NULL THEN 'Store' WHEN latest_history_location.id IS NULL THEN '-' ELSE 'Lokasi Lain' END as latest_area_label"),
                DB::raw("COALESCE(latest_warehouse.name, latest_store.name, latest_history_location.name, '-') as latest_area_name"),
                'lld.latest_location_note',
                'lld.latest_location_changed_at',
                DB::raw('COALESCE(ls.location_move_count, 0)::int as location_move_count'),
                DB::raw('COALESCE(ls.distinct_location_count, 0)::int as distinct_location_count'),
                DB::raw('ls.first_location_move_at'),
                DB::raw('ls.last_location_move_at'),
                DB::raw('COALESCE(cs.condition_change_count, 0)::int as condition_change_count'),
                DB::raw('COALESCE(cs.damage_condition_count, 0)::int as damage_condition_count'),
                DB::raw('cs.first_condition_changed_at'),
                DB::raw('cs.last_condition_changed_at'),
                DB::raw('COALESCE(rtd.repaired_then_damaged_count, 0)::int as repaired_then_damaged_count'),
                DB::raw('rtd.last_repaired_then_damaged_at'),
                DB::raw("\n                    CASE\n                        WHEN COALESCE(ls.location_move_count, 0) >= {$minMoveCount}\n                             AND COALESCE(cs.damage_condition_count, 0) >= {$minDamageCount}\n                        THEN 'Lokasi sering pindah & kondisi sering rusak'\n\n                        WHEN COALESCE(rtd.repaired_then_damaged_count, 0) > 0\n                        THEN 'Sudah diperbaiki lalu rusak lagi'\n\n                        WHEN COALESCE(ls.location_move_count, 0) >= {$minMoveCount}\n                        THEN 'Sering pindah lokasi'\n\n                        WHEN i.current_location_id IS DISTINCT FROM lld.latest_history_location_id\n                             AND lld.latest_history_location_id IS NOT NULL\n                        THEN 'Indikasi salah naruh / lokasi tidak sinkron'\n\n                        WHEN COALESCE(cs.damage_condition_count, 0) >= {$minDamageCount}\n                        THEN 'Kondisi sering rusak'\n\n                        ELSE 'Normal'\n                    END as anomaly_label\n                "),
                DB::raw("\n                    CASE\n                        WHEN COALESCE(ls.location_move_count, 0) >= {$minMoveCount}\n                             AND COALESCE(cs.damage_condition_count, 0) >= {$minDamageCount}\n                        THEN 5\n\n                        WHEN COALESCE(rtd.repaired_then_damaged_count, 0) > 0\n                        THEN 5\n\n                        WHEN i.current_location_id IS DISTINCT FROM lld.latest_history_location_id\n                             AND lld.latest_history_location_id IS NOT NULL\n                        THEN 4\n\n                        WHEN COALESCE(ls.location_move_count, 0) >= {$minMoveCount}\n                        THEN 3\n\n                        WHEN COALESCE(cs.damage_condition_count, 0) >= {$minDamageCount}\n                        THEN 3\n\n                        ELSE 1\n                    END as risk_score\n                "),
            ]);

        $this->applySoftDelete($query, 'items', 'i');
        $this->applySoftDelete($query, 'products', 'p');
        $this->applySoftDelete($query, 'locations', 'current_location');
        $this->applySoftDelete($query, 'item_conditions', 'current_condition');
        $this->applySoftDelete($query, 'warehouses', 'current_warehouse');
        $this->applySoftDelete($query, 'stores', 'current_store');
        $this->applySoftDelete($query, 'warehouses', 'latest_warehouse');
        $this->applySoftDelete($query, 'stores', 'latest_store');

        $this->applyMainFilters($query, $filters, $minMoveCount, $minDamageCount);

        // Tampilkan hanya data anomali. Data normal tidak ditampilkan di monitoring.
        $this->applyOnlyAnomalyFilter($query, $minMoveCount, $minDamageCount);

        return $query
            ->orderByDesc('risk_score')
            ->orderByDesc(DB::raw('COALESCE(ls.location_move_count, 0)'))
            ->orderByDesc(DB::raw('COALESCE(cs.damage_condition_count, 0)'))
            ->orderBy('p.name');
    }

    private function getDemographicData(array $filters): array
    {
        $summary = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("COUNT(*)::int AS total_anomaly_item")
            ->selectRaw("COUNT(DISTINCT x.product_id)::int AS total_product_affected")
            ->selectRaw("COUNT(DISTINCT x.current_location_id)::int AS total_location_affected")
            ->selectRaw("COALESCE(SUM(x.location_move_count), 0)::int AS total_location_move")
            ->selectRaw("COALESCE(SUM(x.damage_condition_count), 0)::int AS total_damage_event")
            ->selectRaw("COALESCE(ROUND(AVG(x.risk_score)::numeric, 2), 0) AS average_risk_score")
            ->first();

        $products = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("COALESCE(x.product_name, 'Produk Tidak Diketahui') AS label")
            ->selectRaw("COALESCE(x.product_code, '-') AS code")
            ->selectRaw("COUNT(*)::int AS total_item")
            ->selectRaw("COUNT(CASE WHEN x.risk_score >= 5 THEN 1 END)::int AS high_risk_item")
            ->selectRaw("COALESCE(SUM(x.location_move_count), 0)::int AS total_move")
            ->selectRaw("COALESCE(SUM(x.damage_condition_count), 0)::int AS total_damage")
            ->selectRaw("COALESCE(MAX(x.risk_score), 0)::int AS max_risk")
            ->groupBy('label', 'code')
            ->orderByDesc('total_item')
            ->orderByDesc('max_risk')
            ->limit(8)
            ->get();

        $areas = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("COALESCE(x.current_area_label, 'Lokasi Lain') AS label")
            ->selectRaw("COUNT(*)::int AS total_item")
            ->selectRaw("COUNT(DISTINCT x.product_id)::int AS total_product")
            ->selectRaw("COUNT(CASE WHEN x.risk_score >= 5 THEN 1 END)::int AS high_risk_item")
            ->groupBy('label')
            ->orderByDesc('total_item')
            ->get();

        $conditions = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("COALESCE(x.current_condition_name, 'Kondisi Tidak Diisi') AS label")
            ->selectRaw("COUNT(*)::int AS total_item")
            ->selectRaw("COUNT(CASE WHEN x.risk_score >= 5 THEN 1 END)::int AS high_risk_item")
            ->groupBy('label')
            ->orderByDesc('total_item')
            ->limit(8)
            ->get();

        $anomalies = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("COALESCE(x.anomaly_label, 'Normal') AS label")
            ->selectRaw("COUNT(*)::int AS total_item")
            ->selectRaw("COUNT(DISTINCT x.product_id)::int AS total_product")
            ->groupBy('label')
            ->orderByDesc('total_item')
            ->get();

        $risks = DB::query()
            ->fromSub($this->buildAnomalyQuery($filters), 'x')
            ->selectRaw("CASE WHEN x.risk_score >= 5 THEN 'Risiko Tinggi' WHEN x.risk_score = 4 THEN 'Perlu Dicek' WHEN x.risk_score >= 3 THEN 'Risiko Sedang' ELSE 'Normal' END AS label")
            ->selectRaw("COUNT(*)::int AS total_item")
            ->selectRaw("COUNT(DISTINCT x.product_id)::int AS total_product")
            ->groupBy('label')
            ->orderByDesc('total_item')
            ->get();

        return [
            'summary' => $summary,
            'products' => $products,
            'areas' => $areas,
            'conditions' => $conditions,
            'anomalies' => $anomalies,
            'risks' => $risks,
        ];
    }

    private function getSummary(array $filters)
    {
        $base = $this->buildAnomalyQuery($filters);

        return DB::query()
            ->fromSub($base, 'x')
            ->selectRaw('COUNT(*)::int as total_item')
            ->selectRaw("COUNT(CASE WHEN x.risk_score >= 3 THEN 1 END)::int as total_anomaly")
            ->selectRaw("COUNT(CASE WHEN x.anomaly_label = 'Sering pindah lokasi' THEN 1 END)::int as total_frequent_move")
            ->selectRaw("COUNT(CASE WHEN x.anomaly_label = 'Indikasi salah naruh / lokasi tidak sinkron' THEN 1 END)::int as total_location_mismatch")
            ->selectRaw("COUNT(CASE WHEN x.anomaly_label = 'Kondisi sering rusak' THEN 1 END)::int as total_frequent_damage")
            ->selectRaw("COUNT(CASE WHEN x.repaired_then_damaged_count > 0 THEN 1 END)::int as total_repaired_then_damaged")
            ->first();
    }

    private function applyMainFilters($query, array $filters, int $minMoveCount, int $minDamageCount): void
    {
        $q = $filters['q'];
        $locationId = $filters['location_id'];
        $conditionId = $filters['condition_id'];
        $anomalyType = $filters['anomaly_type'];

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'ILIKE', "%{$q}%")
                    ->orWhere('p.code', 'ILIKE', "%{$q}%")
                    ->orWhere('i.status', 'ILIKE', "%{$q}%")
                    ->orWhereRaw('i.rfid_tag_id::text ILIKE ?', ["%{$q}%"])
                    ->orWhere('current_location.name', 'ILIKE', "%{$q}%")
                    ->orWhere('current_location.code', 'ILIKE', "%{$q}%")
                    ->orWhere('latest_history_location.name', 'ILIKE', "%{$q}%")
                    ->orWhere('latest_history_location.code', 'ILIKE', "%{$q}%")
                    ->orWhere('current_warehouse.name', 'ILIKE', "%{$q}%")
                    ->orWhere('current_store.name', 'ILIKE', "%{$q}%")
                    ->orWhere('latest_warehouse.name', 'ILIKE', "%{$q}%")
                    ->orWhere('latest_store.name', 'ILIKE', "%{$q}%")
                    ->orWhere('current_condition.name', 'ILIKE', "%{$q}%");
            });
        }

        if ($locationId) {
            $query->where('i.current_location_id', $locationId);
        }

        if ($conditionId) {
            $query->where('i.current_condition_id', $conditionId);
        }

        if ($anomalyType === 'frequent_move') {
            $query->whereRaw('COALESCE(ls.location_move_count, 0) >= ?', [$minMoveCount]);
        }

        if ($anomalyType === 'location_mismatch') {
            $query->whereRaw('i.current_location_id IS DISTINCT FROM lld.latest_history_location_id')
                ->whereNotNull('lld.latest_history_location_id');
        }

        if ($anomalyType === 'frequent_damage') {
            $query->whereRaw('COALESCE(cs.damage_condition_count, 0) >= ?', [$minDamageCount]);
        }

        if ($anomalyType === 'repaired_then_damaged') {
            $query->whereRaw('COALESCE(rtd.repaired_then_damaged_count, 0) > 0');
        }

        if ($anomalyType === 'high_risk') {
            $query->where(function ($sub) use ($minMoveCount, $minDamageCount) {
                $sub->whereRaw('COALESCE(ls.location_move_count, 0) >= ?', [$minMoveCount])
                    ->orWhereRaw('COALESCE(cs.damage_condition_count, 0) >= ?', [$minDamageCount])
                    ->orWhereRaw('COALESCE(rtd.repaired_then_damaged_count, 0) > 0')
                    ->orWhere(function ($mismatch) {
                        $mismatch->whereRaw('i.current_location_id IS DISTINCT FROM lld.latest_history_location_id')
                            ->whereNotNull('lld.latest_history_location_id');
                    });
            });
        }
    }

    /**
     * Monitoring ini hanya menampilkan item yang anomali.
     * Logika dasar anomali tetap sama dengan query sebelumnya:
     * - sering pindah lokasi,
     * - lokasi saat ini tidak sama dengan history terakhir,
     * - sering rusak,
     * - sudah diperbaiki lalu rusak lagi.
     */
    private function applyOnlyAnomalyFilter($query, int $minMoveCount, int $minDamageCount): void
    {
        $query->where(function ($sub) use ($minMoveCount, $minDamageCount) {
            $sub->whereRaw('COALESCE(ls.location_move_count, 0) >= ?', [$minMoveCount])
                ->orWhereRaw('COALESCE(cs.damage_condition_count, 0) >= ?', [$minDamageCount])
                ->orWhereRaw('COALESCE(rtd.repaired_then_damaged_count, 0) > 0')
                ->orWhere(function ($mismatch) {
                    $mismatch->whereRaw('i.current_location_id IS DISTINCT FROM lld.latest_history_location_id')
                        ->whereNotNull('lld.latest_history_location_id');
                });
        });
    }

    private function applySoftDelete($query, string $table, string $alias): void
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$alias}.deleted_at");
        }
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return date('Y-m-d', strtotime((string) $value));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
