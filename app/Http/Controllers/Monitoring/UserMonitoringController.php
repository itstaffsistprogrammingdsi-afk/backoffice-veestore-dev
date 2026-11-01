<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\UserMonitoringExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (!in_array($perPage, [5, 10, 25, 50, 100], true)) {
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

        return Inertia::render('Monitoring/Report', [
            'title' => 'User Monitoring',
            'description' => 'Laporan monitoring.',
            'data' => compact(
            'rows',
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
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'monitoring-user-' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new UserMonitoringExport($request), $filename);
    }
}