<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WarehouseUserAuditReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $activity = $this->activityQuery($start, $end);

        $filtered = DB::query()
            ->fromSub(clone $activity, 'act')
            ->when($filters['user_id'] !== '', fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['warehouse_id'] !== '', fn ($q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('user_name', 'ILIKE', $like)
                        ->orWhere('warehouse_name', 'ILIKE', $like)
                        ->orWhere('reference', 'ILIKE', $like)
                        ->orWhere('detail', 'ILIKE', $like);
                });
            });

        $rows = (clone $filtered)
            ->orderByDesc('occurred_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $userSummary = (clone $filtered)
            ->selectRaw('
                user_id,
                MAX(user_name) AS user_name,
                COUNT(*)::int AS event_count,
                COUNT(*) FILTER (WHERE action = \'Inbound - Buat\' OR action = \'Inbound - Terima\')::int AS inbound_events,
                COUNT(*) FILTER (WHERE action LIKE \'Outbound%\')::int AS outbound_events,
                COUNT(*) FILTER (WHERE action LIKE \'QC%\')::int AS qc_events,
                COUNT(*) FILTER (WHERE action LIKE \'Stok Opname%\')::int AS opname_events,
                COALESCE(SUM(qty), 0)::int AS total_qty,
                MAX(occurred_at) AS last_activity_at
            ')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('event_count')
            ->limit(100)
            ->get();

        $summary = DB::query()
            ->fromSub(clone $filtered, 'act')
            ->selectRaw('
                COUNT(DISTINCT user_id)::int AS active_users,
                COUNT(*)::int AS total_events,
                COUNT(*) FILTER (WHERE action LIKE \'Inbound%\')::int AS inbound_events,
                COUNT(*) FILTER (WHERE action LIKE \'Outbound%\')::int AS outbound_events,
                COUNT(*) FILTER (WHERE action LIKE \'QC%\')::int AS qc_events,
                COUNT(*) FILTER (WHERE action LIKE \'Stok Opname%\')::int AS opname_events,
                COALESCE(SUM(qty), 0)::int AS total_qty
            ')
            ->first();

        $users = DB::table('users as u')
            ->whereNull('u.deleted_at')
            ->where(function ($q) {
                $q->where('u.wms_access', true)
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('warehouse_users as wu')
                            ->whereColumn('wu.user_id', 'u.id');
                    });
            })
            ->orderBy('u.name')
            ->get(['u.id', 'u.name']);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $actions = DB::query()
            ->fromSub(clone $activity, 'act')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('Monitoring/Report', [
            'title' => 'Audit Pengguna Gudang',
            'description' => 'Telusuri aktivitas pengguna WMS pada inbound, outbound, QC, dan stok opname gudang beserta volume dan waktu kejadiannya.',
            'data' => [
                'rows' => $rows,
                'userSummary' => $userSummary,
                'summary' => (array) $summary,
                'users' => $users,
                'warehouses' => $warehouses,
                'actions' => $actions,
                'filters' => $filters,
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function activityQuery(Carbon $start, Carbon $end)
    {
        $inboundCreated = DB::table('warehouse_inbounds as wi')
            ->join('users as u', 'u.id', '=', 'wi.created_by')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->whereBetween('wi.created_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Inbound - Buat' AS action,
                COALESCE(wi.invoice_number, wi.delivery_order_number, wi.id::text) AS reference,
                COALESCE(wi.quantity_item, 0)::int AS qty,
                wi.created_at AS occurred_at,
                COALESCE(wi.batch_number, '-') AS detail
            ");

        $inboundReceived = DB::table('warehouse_inbounds as wi')
            ->join('users as u', 'u.id', '=', 'wi.received_by')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->whereBetween(DB::raw('COALESCE(wi.received_date, wi.created_at)'), [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Inbound - Terima' AS action,
                COALESCE(wi.invoice_number, wi.delivery_order_number, wi.id::text) AS reference,
                COALESCE(wi.quantity_item, 0)::int AS qty,
                COALESCE(wi.received_date, wi.created_at) AS occurred_at,
                COALESCE(wi.batch_number, '-') AS detail
            ");

        $outboundCreated = DB::table('warehouse_outbounds as wo')
            ->join('users as u', 'u.id', '=', 'wo.user_id')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereBetween('wo.created_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Outbound - Buat' AS action,
                COALESCE(wo.invoice_number, wo.delivery_order_number, wo.order_number, wo.id::text) AS reference,
                COALESCE(wo.quantity_item, 0)::int AS qty,
                wo.created_at AS occurred_at,
                COALESCE(wo.outbound_type::text, '-') AS detail
            ");

        $outboundQc = DB::table('warehouse_outbounds as wo')
            ->join('users as u', 'u.id', '=', 'wo.qc_passed_by')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereNotNull('wo.qc_passed_at')
            ->whereBetween('wo.qc_passed_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Outbound - QC Lolos' AS action,
                COALESCE(wo.invoice_number, wo.delivery_order_number, wo.order_number, wo.id::text) AS reference,
                COALESCE(wo.quantity_item, 0)::int AS qty,
                wo.qc_passed_at AS occurred_at,
                '-' AS detail
            ");

        $qc = DB::table('warehouse_qc as qc')
            ->join('users as u', 'u.id', '=', 'qc.performed_by')
            ->join('warehouses as w', 'w.id', '=', 'qc.warehouse_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
            ->whereBetween(DB::raw('COALESCE(qc.performed_at, qc.created_at)'), [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'QC Gudang' AS action,
                qc.id::text AS reference,
                1::int AS qty,
                COALESCE(qc.performed_at, qc.created_at) AS occurred_at,
                COALESCE(qc.qc_type::text, '-') || COALESCE(' / ' || ic.name, '') AS detail
            ");

        $opnameCreated = DB::table('warehouse_stock_opnames as wo')
            ->join('users as u', 'u.id', '=', 'wo.user_id')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereBetween('wo.created_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Stok Opname - Buat' AS action,
                wo.code AS reference,
                0::int AS qty,
                wo.created_at AS occurred_at,
                COALESCE(wo.status::text, '-') AS detail
            ");

        $opnameApproved = DB::table('warehouse_stock_opnames as wo')
            ->join('users as u', 'u.id', '=', 'wo.approved_by')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereNotNull('wo.approved_at')
            ->whereBetween('wo.approved_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Stok Opname - Setujui' AS action,
                wo.code AS reference,
                0::int AS qty,
                wo.approved_at AS occurred_at,
                COALESCE(wo.status::text, '-') AS detail
            ");

        $opnameScanned = DB::table('warehouse_stock_opname_details as wd')
            ->join('warehouse_stock_opnames as wo', 'wo.id', '=', 'wd.warehouse_stock_opname_id')
            ->join('users as u', 'u.id', '=', 'wd.created_by')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereBetween(DB::raw('COALESCE(wd.scanned_at, wd.created_at)'), [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Stok Opname - Scan Item' AS action,
                wo.code AS reference,
                1::int AS qty,
                COALESCE(wd.scanned_at, wd.created_at) AS occurred_at,
                COALESCE(wd.status::text, '-') AS detail
            ");

        $opnameMoved = DB::table('warehouse_stock_opname_details as wd')
            ->join('warehouse_stock_opnames as wo', 'wo.id', '=', 'wd.warehouse_stock_opname_id')
            ->join('users as u', 'u.id', '=', 'wd.move_to_location_by')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereNotNull('wd.move_to_location_by')
            ->whereBetween('wd.updated_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Stok Opname - Pindah Lokasi' AS action,
                wo.code AS reference,
                1::int AS qty,
                wd.updated_at AS occurred_at,
                COALESCE(wd.status::text, '-') AS detail
            ");

        $opnameCondition = DB::table('warehouse_stock_opname_details as wd')
            ->join('warehouse_stock_opnames as wo', 'wo.id', '=', 'wd.warehouse_stock_opname_id')
            ->join('users as u', 'u.id', '=', 'wd.change_to_condition_by')
            ->join('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->whereNotNull('wd.change_to_condition_by')
            ->whereBetween('wd.updated_at', [$start, $end])
            ->selectRaw("
                u.id AS user_id,
                u.name AS user_name,
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                'Stok Opname - Ubah Kondisi' AS action,
                wo.code AS reference,
                1::int AS qty,
                wd.updated_at AS occurred_at,
                COALESCE(wd.status::text, '-') AS detail
            ");

        return $inboundCreated
            ->unionAll($inboundReceived)
            ->unionAll($outboundCreated)
            ->unionAll($outboundQc)
            ->unionAll($qc)
            ->unionAll($opnameCreated)
            ->unionAll($opnameApproved)
            ->unionAll($opnameScanned)
            ->unionAll($opnameMoved)
            ->unionAll($opnameCondition);
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->subDays(30)->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'action' => trim((string) $request->query('action', '')),
            'start_date' => $from,
            'end_date' => $to,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
