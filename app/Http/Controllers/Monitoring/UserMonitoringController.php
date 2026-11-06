<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\UserMonitoringExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UserMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $type = $request->query('type');
        $appType = $request->query('app_type');
        $device = $request->query('device');
        $platform = $request->query('platform');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        $perPage = (int) $request->query('per_page', 5);

        if (! in_array($perPage, [5, 10, 25, 50, 100], true)) {
            $perPage = 5;
        }

        $query = DB::table('session_logs as sl')
            ->select([
                'sl.id',
                'sl.user_id',
                'sl.ip_address',
                'sl.session_token',
                'sl.user_agent',
                'sl.device',
                'sl.platform',
                'sl.last_activity_at',
                'sl.expired_at',
                'sl.status',
                'sl.type',
                'sl.failure_reason',
                'sl.app_type',
                'sl.details',
                'sl.logged_at',
                DB::raw("COALESCE(sl.details->>'username', '-') as username"),
                DB::raw("COALESCE(sl.details->'gps'->>'lat', '-') as gps_lat"),
                DB::raw("COALESCE(sl.details->'gps'->>'lon', '-') as gps_lon"),
                DB::raw("COALESCE(sl.details->'ip_info'->>'isp', '-') as isp"),
                DB::raw("COALESCE(sl.details->'ip_info'->>'city', '-') as city"),
                DB::raw("COALESCE(sl.details->'ip_info'->>'country', '-') as country"),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('sl.id', 'ILIKE', $like)
                        ->orWhereRaw('sl.user_id::text ILIKE ?', [$like])
                        ->orWhere('sl.ip_address', 'ILIKE', $like)
                        ->orWhere('sl.session_token', 'ILIKE', $like)
                        ->orWhere('sl.user_agent', 'ILIKE', $like)
                        ->orWhere('sl.device', 'ILIKE', $like)
                        ->orWhere('sl.platform', 'ILIKE', $like)
                        ->orWhereRaw('sl.status::text ILIKE ?', [$like])
                        ->orWhereRaw('sl.type::text ILIKE ?', [$like])
                        ->orWhereRaw('sl.app_type::text ILIKE ?', [$like])
                        ->orWhere('sl.failure_reason', 'ILIKE', $like)
                        ->orWhereRaw("COALESCE(sl.details->>'username', '') ILIKE ?", [$like])
                        ->orWhereRaw("COALESCE(sl.details::text, '') ILIKE ?", [$like]);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where('sl.status', $status);
            })
            ->when($type, function ($qBuilder) use ($type) {
                $qBuilder->where('sl.type', $type);
            })
            ->when($appType, function ($qBuilder) use ($appType) {
                $qBuilder->where('sl.app_type', $appType);
            })
            ->when($device, function ($qBuilder) use ($device) {
                $qBuilder->where('sl.device', $device);
            })
            ->when($platform, function ($qBuilder) use ($platform) {
                $qBuilder->where('sl.platform', $platform);
            })
            ->whereBetween(DB::raw('sl.logged_at::date'), [$from, $to])
            ->orderByDesc('sl.logged_at')
            ->orderByDesc('sl.last_activity_at');

        $rows = $query->paginate($perPage)->withQueryString();

        $statuses = DB::table('session_logs')
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $types = DB::table('session_logs')
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $appTypes = DB::table('session_logs')
            ->select('app_type')
            ->whereNotNull('app_type')
            ->distinct()
            ->orderBy('app_type')
            ->pluck('app_type');

        $devices = DB::table('session_logs')
            ->select('device')
            ->whereNotNull('device')
            ->distinct()
            ->orderBy('device')
            ->pluck('device');

        $platforms = DB::table('session_logs')
            ->select('platform')
            ->whereNotNull('platform')
            ->distinct()
            ->orderBy('platform')
            ->pluck('platform');

        $dailyRows = DB::table('session_logs as sl')
            ->whereBetween(DB::raw('sl.logged_at::date'), [$from, $to])
            ->selectRaw("
                sl.logged_at::date AS report_date,
                TO_CHAR(sl.logged_at::date, 'DD Mon YYYY') AS period_label,
                COALESCE(sl.app_type::text, 'unknown') AS app_type,
                COUNT(*)::int AS session_count,
                COUNT(DISTINCT sl.user_id)::int AS unique_users,
                COUNT(*) FILTER (WHERE LOWER(COALESCE(sl.status::text, '')) IN ('failed','rejected'))::int AS failed_count,
                MAX(sl.last_activity_at) AS last_activity_at
            ")
            ->groupByRaw('sl.logged_at::date, sl.app_type::text')
            ->orderByDesc('report_date')
            ->orderBy('app_type')
            ->get();

        $appSummary = DB::table('session_logs as sl')
            ->whereBetween(DB::raw('sl.logged_at::date'), [$from, $to])
            ->selectRaw("
                COALESCE(sl.app_type::text, 'unknown') AS app_type,
                COUNT(*)::int AS session_count,
                COUNT(DISTINCT sl.user_id)::int AS unique_users,
                COUNT(*) FILTER (WHERE LOWER(COALESCE(sl.status::text, '')) IN ('failed','rejected'))::int AS failed_count,
                MAX(sl.last_activity_at) AS last_activity_at
            ")
            ->groupByRaw("COALESCE(sl.app_type::text, 'unknown')")
            ->orderByDesc('session_count')
            ->get();

        $summary = DB::table('session_logs as sl')
            ->whereBetween(DB::raw('sl.logged_at::date'), [$from, $to])
            ->selectRaw("
                COUNT(*)::int AS total_sessions,
                COUNT(DISTINCT sl.user_id)::int AS unique_users,
                COUNT(*) FILTER (WHERE LOWER(COALESCE(sl.status::text, '')) IN ('failed','rejected'))::int AS failed_logins,
                COUNT(*) FILTER (WHERE sl.app_type::text = 'warehouse')::int AS wms_sessions,
                COUNT(*) FILTER (WHERE sl.app_type::text = 'store')::int AS store_sessions,
                COUNT(*) FILTER (WHERE sl.app_type::text = 'ecommerce')::int AS ecommerce_sessions,
                COUNT(*) FILTER (WHERE sl.app_type::text = 'backoffice')::int AS backoffice_sessions,
                COUNT(*) FILTER (WHERE sl.logged_at::date = CURRENT_DATE)::int AS today_sessions
            ")
            ->first();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Akses Pengguna Harian',
            'description' => 'Rekap login dan aktivitas pengguna per hari dan per aplikasi, termasuk akses WMS, toko, ecommerce, dan Master Data & Report.',
            'data' => compact(
                'rows',
                'dailyRows',
                'appSummary',
                'summary',
                'q',
                'status',
                'type',
                'appType',
                'device',
                'platform',
                'from',
                'to',
                'perPage',
                'statuses',
                'types',
                'appTypes',
                'devices',
                'platforms'
            ),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.user-monitoring.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'monitoring-user-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new UserMonitoringExport($request), $filename);
    }
}
