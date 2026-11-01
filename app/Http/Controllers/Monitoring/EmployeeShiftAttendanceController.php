<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\EmployeeShiftAttendanceExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeShiftAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $reportQuery = $this->buildReportQuery(
            q: $filters['q'],
            storeId: $filters['store_id'],
            userId: $filters['user_id'],
            shiftId: $filters['shift_id'],
            status: $filters['status'],
            attendanceType: $filters['attendance_type'],
            from: $filters['from'],
            to: $filters['to']
        );

        $rows = (clone $reportQuery)
            ->orderByDesc('x.date')
            ->orderBy('x.store_name')
            ->orderBy('x.employee_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Summary Absensi
        |--------------------------------------------------------------------------
        | Data absensi tetap mengikuti filter tanggal hari ini / periode.
        | Total karyawan dan total store dihitung dari master data,
        | supaya tetap tampil meskipun belum ada absensi hari ini.
        */
        $summary = DB::query()
            ->fromSub(clone $reportQuery, 'x')
            ->selectRaw("
                COUNT(*)::int as total_attendance,
                COALESCE(SUM(CASE WHEN x.is_late THEN 1 ELSE 0 END), 0)::int as total_late,
                COALESCE(SUM(CASE WHEN x.is_overtime THEN 1 ELSE 0 END), 0)::int as total_overtime,
                COALESCE(SUM(CASE WHEN x.clock_in_at IS NULL THEN 1 ELSE 0 END), 0)::int as total_not_clock_in,
                COALESCE(SUM(CASE WHEN x.clock_out_at IS NULL THEN 1 ELSE 0 END), 0)::int as total_not_clock_out,
                COALESCE(SUM(x.late_minutes), 0)::int as total_late_minutes,
                COALESCE(SUM(x.overtime_minutes), 0)::int as total_overtime_minutes,
                COALESCE(SUM(x.work_minutes), 0)::int as total_work_minutes
            ")
            ->first();

        if (!$summary) {
            $summary = (object) [
                'total_attendance' => 0,
                'total_late' => 0,
                'total_overtime' => 0,
                'total_not_clock_in' => 0,
                'total_not_clock_out' => 0,
                'total_late_minutes' => 0,
                'total_overtime_minutes' => 0,
                'total_work_minutes' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Summary Master Karyawan
        |--------------------------------------------------------------------------
        | Tetap tampil walaupun data absensi hari ini kosong.
        */
        $totalEmployeeQuery = DB::table('users')
            ->whereNull('deleted_at');

        if ($filters['user_id']) {
            $totalEmployeeQuery->where('id', $filters['user_id']);
        }

        /*
        |--------------------------------------------------------------------------
        | Summary Master Store
        |--------------------------------------------------------------------------
        | Tetap tampil walaupun data absensi hari ini kosong.
        */
        $totalStoreQuery = DB::table('stores')
            ->whereNull('deleted_at');

        if ($filters['store_id']) {
            $totalStoreQuery->where('id', $filters['store_id']);
        }

        $summary->total_employee = (int) $totalEmployeeQuery->count();
        $summary->total_store = (int) $totalStoreQuery->count();

        $chartDailyRows = DB::query()
            ->fromSub(clone $reportQuery, 'x')
            ->selectRaw("
                x.date,
                COUNT(*)::int as total_attendance,
                COALESCE(SUM(CASE WHEN x.is_late THEN 1 ELSE 0 END), 0)::int as total_late,
                COALESCE(SUM(CASE WHEN x.is_overtime THEN 1 ELSE 0 END), 0)::int as total_overtime
            ")
            ->groupBy('x.date')
            ->orderBy('x.date')
            ->get();

        $chartStoreRows = DB::query()
            ->fromSub(clone $reportQuery, 'x')
            ->selectRaw("
                COALESCE(x.store_name, 'Store Tidak Diketahui') as store_name,
                COUNT(*)::int as total_attendance,
                COALESCE(SUM(CASE WHEN x.is_late THEN 1 ELSE 0 END), 0)::int as total_late,
                COALESCE(SUM(CASE WHEN x.is_overtime THEN 1 ELSE 0 END), 0)::int as total_overtime
            ")
            ->groupBy('store_name')
            ->orderByDesc('total_late')
            ->orderByDesc('total_overtime')
            ->limit(10)
            ->get();

        $chartEmployeeRows = DB::query()
            ->fromSub(clone $reportQuery, 'x')
            ->selectRaw("
                COALESCE(x.employee_name, 'Karyawan Tidak Diketahui') as employee_name,
                COUNT(*)::int as total_attendance,
                COALESCE(SUM(CASE WHEN x.is_late THEN 1 ELSE 0 END), 0)::int as total_late,
                COALESCE(SUM(CASE WHEN x.is_overtime THEN 1 ELSE 0 END), 0)::int as total_overtime
            ")
            ->groupBy('employee_name')
            ->orderByDesc('total_late')
            ->orderByDesc('total_overtime')
            ->limit(10)
            ->get();

        $latestRows = DB::query()
            ->fromSub(clone $reportQuery, 'x')
            ->select([
                'x.id',
                'x.date',
                'x.store_name',
                'x.store_code',
                'x.employee_name',
                'x.employee_email',
                'x.shift_name',
                'x.shift_code',
                'x.status',
                'x.clock_in_at',
                'x.clock_out_at',
                'x.is_late',
                'x.is_overtime',
                'x.late_minutes',
                'x.overtime_minutes',
                'x.note',
            ])
            ->orderByDesc('x.date')
            ->orderByDesc('x.clock_in_at')
            ->limit(10)
            ->get();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $shifts = DB::table('store_attendance_shifts as sas')
            ->leftJoin('stores as s', 's.id', '=', 'sas.store_id')
            ->where('sas.is_active', true)
            ->orderBy('s.name')
            ->orderBy('sas.start_time')
            ->get([
                'sas.id',
                'sas.store_id',
                'sas.name',
                'sas.code',
                'sas.start_time',
                'sas.end_time',
                's.name as store_name',
            ]);

        $statuses = DB::table('store_attendances as sa')
            ->selectRaw('DISTINCT sa.status::text as status')
            ->whereNotNull('sa.status')
            ->orderBy('status')
            ->pluck('status');


        /*
        |--------------------------------------------------------------------------
        | Jadwal Shift per Cabang / Klinik
        |--------------------------------------------------------------------------
        | Data ini berasal dari store_shift_schedules + store_shift_schedule_details.
        | Dipakai oleh Holding untuk melihat jadwal semua cabang, termasuk status
        | absensi aktual jika sudah ada data di store_attendances.
        */
        $scheduleRows = DB::table('store_shift_schedule_details as d')
            ->join('store_shift_schedules as h', 'h.id', '=', 'd.store_shift_schedule_id')
            ->leftJoin('stores as s', 's.id', '=', 'h.store_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('store_attendance_shifts as shift', 'shift.id', '=', 'd.attendance_shift_id')
            ->leftJoin('store_attendances as sa', function ($join) {
                $join->on('sa.store_id', '=', 'h.store_id')
                    ->on('sa.user_id', '=', 'd.user_id')
                    ->on('sa.date', '=', 'd.schedule_date');
            })
            ->selectRaw("
                d.id,
                d.store_shift_schedule_id,
                d.user_id,
                d.schedule_date,
                d.attendance_shift_id,
                d.is_day_off,
                d.is_national_holiday,
                d.notes,

                h.store_id,
                h.schedule_month,
                h.status::text as schedule_status,

                s.name as store_name,
                s.code as store_code,

                u.name as employee_name,
                u.email as employee_email,

                shift.name as shift_name,
                shift.code as shift_code,
                shift.start_time as shift_start_time,
                shift.end_time as shift_end_time,

                sa.id as attendance_id,
                sa.clock_in_at,
                sa.clock_out_at,
                sa.scheduled_start,
                sa.scheduled_end,
                sa.status::text as attendance_status,

                CASE
                    WHEN d.is_day_off = true THEN 'day_off'
                    WHEN d.is_national_holiday = true THEN 'holiday'
                    WHEN sa.id IS NULL AND d.schedule_date < CURRENT_DATE THEN 'absent'
                    WHEN sa.id IS NULL AND d.schedule_date >= CURRENT_DATE THEN 'scheduled'
                    WHEN sa.clock_in_at IS NULL THEN 'not_clock_in'
                    WHEN sa.clock_out_at IS NULL THEN 'not_clock_out'
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.scheduled_start IS NOT NULL
                     AND sa.clock_in_at > sa.scheduled_start
                     AND sa.clock_out_at IS NOT NULL
                     AND sa.scheduled_end IS NOT NULL
                     AND sa.clock_out_at > sa.scheduled_end THEN 'late_overtime'
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.scheduled_start IS NOT NULL
                     AND sa.clock_in_at > sa.scheduled_start THEN 'late'
                    WHEN sa.clock_out_at IS NOT NULL
                     AND sa.scheduled_end IS NOT NULL
                     AND sa.clock_out_at > sa.scheduled_end THEN 'overtime'
                    ELSE 'present'
                END as marker_status,

                CASE
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.scheduled_start IS NOT NULL
                     AND sa.clock_in_at > sa.scheduled_start
                    THEN FLOOR(EXTRACT(EPOCH FROM (sa.clock_in_at - sa.scheduled_start)) / 60)::int
                    ELSE 0
                END as late_minutes,

                CASE
                    WHEN sa.clock_out_at IS NOT NULL
                     AND sa.scheduled_end IS NOT NULL
                     AND sa.clock_out_at > sa.scheduled_end
                    THEN FLOOR(EXTRACT(EPOCH FROM (sa.clock_out_at - sa.scheduled_end)) / 60)::int
                    ELSE 0
                END as overtime_minutes
            ")
            ->when($filters['store_id'], fn ($query) => $query->where('h.store_id', $filters['store_id']))
            ->when($filters['user_id'], fn ($query) => $query->where('d.user_id', $filters['user_id']))
            ->when($filters['shift_id'], fn ($query) => $query->where('d.attendance_shift_id', $filters['shift_id']))
            ->when($filters['from'], fn ($query) => $query->whereDate('d.schedule_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('d.schedule_date', '<=', $filters['to']))
            ->when($filters['q'], function ($query) use ($filters) {
                $like = '%' . $filters['q'] . '%';

                $query->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('s.code', 'ILIKE', $like)
                        ->orWhere('u.name', 'ILIKE', $like)
                        ->orWhere('u.email', 'ILIKE', $like)
                        ->orWhere('shift.name', 'ILIKE', $like)
                        ->orWhere('shift.code', 'ILIKE', $like)
                        ->orWhere('d.notes', 'ILIKE', $like);
                });
            })
            ->orderBy('s.name')
            ->orderBy('d.schedule_date')
            ->orderBy('shift.start_time')
            ->orderBy('u.name')
            ->get();

        $scheduleByStore = $scheduleRows
            ->groupBy(fn ($row) => (string) ($row->store_id ?? 'unknown'))
            ->map(function ($items) {
                $first = $items->first();

                return (object) [
                    'store_id' => $first->store_id ?? null,
                    'store_name' => $first->store_name ?? 'Cabang Tidak Diketahui',
                    'store_code' => $first->store_code ?? null,
                    'total_schedule' => $items->count(),
                    'total_present' => $items->where('marker_status', 'present')->count(),
                    'total_scheduled' => $items->where('marker_status', 'scheduled')->count(),
                    'total_absent' => $items->where('marker_status', 'absent')->count(),
                    'total_late' => $items->whereIn('marker_status', ['late', 'late_overtime'])->count(),
                    'total_overtime' => $items->whereIn('marker_status', ['overtime', 'late_overtime'])->count(),
                    'total_incomplete_clock' => $items->whereIn('marker_status', ['not_clock_in', 'not_clock_out'])->count(),
                    'total_day_off' => $items->whereIn('marker_status', ['day_off', 'holiday'])->count(),
                    'items' => $items->values(),
                ];
            })
            ->values();


        $dailyLabels = $chartDailyRows
            ->map(fn ($row) => \Carbon\Carbon::parse($row->date)->format('d M'))
            ->values();

        $dailyAttendances = $chartDailyRows
            ->map(fn ($row) => (int) ($row->total_attendance ?? 0))
            ->values();

        $dailyLates = $chartDailyRows
            ->map(fn ($row) => (int) ($row->total_late ?? 0))
            ->values();

        $dailyOvertimes = $chartDailyRows
            ->map(fn ($row) => (int) ($row->total_overtime ?? 0))
            ->values();

        $storeLabels = $chartStoreRows
            ->map(fn ($row) => $row->store_name ?? '-')
            ->values();

        $storeLates = $chartStoreRows
            ->map(fn ($row) => (int) ($row->total_late ?? 0))
            ->values();

        $storeOvertimes = $chartStoreRows
            ->map(fn ($row) => (int) ($row->total_overtime ?? 0))
            ->values();

        $employeeLabels = $chartEmployeeRows
            ->map(fn ($row) => $row->employee_name ?? '-')
            ->values();

        $employeeLates = $chartEmployeeRows
            ->map(fn ($row) => (int) ($row->total_late ?? 0))
            ->values();

        $employeeOvertimes = $chartEmployeeRows
            ->map(fn ($row) => (int) ($row->total_overtime ?? 0))
            ->values();

        return view('content.monitoring.employee-shift-attendance.index', [
            'rows' => $rows,
            'summary' => $summary,
            'stores' => $stores,
            'users' => $users,
            'shifts' => $shifts,
            'statuses' => $statuses,
            'latestRows' => $latestRows,
            'scheduleRows' => $scheduleRows,
            'scheduleByStore' => $scheduleByStore,

            'q' => $filters['q'],
            'storeId' => $filters['store_id'],
            'userId' => $filters['user_id'],
            'shiftId' => $filters['shift_id'],
            'status' => $filters['status'],
            'attendanceType' => $filters['attendance_type'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'autoRefresh' => $filters['auto_refresh'],

            'dailyLabels' => $dailyLabels,
            'dailyAttendances' => $dailyAttendances,
            'dailyLates' => $dailyLates,
            'dailyOvertimes' => $dailyOvertimes,

            'storeLabels' => $storeLabels,
            'storeLates' => $storeLates,
            'storeOvertimes' => $storeOvertimes,

            'employeeLabels' => $employeeLabels,
            'employeeLates' => $employeeLates,
            'employeeOvertimes' => $employeeOvertimes,
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new EmployeeShiftAttendanceExport($request),
            'monitoring-shift-karyawan-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default Realtime Hari Ini
        |--------------------------------------------------------------------------
        | Jika user tidak memilih tanggal, data otomatis tampil hari ini saja.
        */
        $today = now()->toDateString();

        $from = $this->normalizeNullableString($request->query('from')) ?? $today;
        $to = $this->normalizeNullableString($request->query('to')) ?? $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $attendanceType = $this->normalizeNullableString($request->query('attendance_type'));

        if (!in_array($attendanceType, [
            null,
            'late',
            'overtime',
            'late_overtime',
            'not_clock_in',
            'not_clock_out',
        ], true)) {
            $attendanceType = null;
        }

        return [
            'q' => $this->normalizeNullableString($request->query('q')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'user_id' => $this->normalizeNullableString($request->query('user_id')),
            'shift_id' => $this->normalizeNullableString($request->query('shift_id')),
            'status' => $this->normalizeNullableString($request->query('status')),
            'attendance_type' => $attendanceType,
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function buildReportQuery(
        ?string $q = null,
        ?string $storeId = null,
        ?string $userId = null,
        ?string $shiftId = null,
        ?string $status = null,
        ?string $attendanceType = null,
        ?string $from = null,
        ?string $to = null
    ) {
        $base = DB::table('store_attendances as sa')
            ->leftJoin('stores as s', 's.id', '=', 'sa.store_id')
            ->leftJoin('users as u', 'u.id', '=', 'sa.user_id')
            ->leftJoin('store_attendance_shifts as shift', 'shift.id', '=', 'sa.attendance_shift_id')
            ->selectRaw("
                sa.id,
                sa.store_id,
                sa.user_id,
                sa.attendance_shift_id,
                sa.date,
                sa.scheduled_start,
                sa.scheduled_end,
                sa.clock_in_at,
                sa.clock_out_at,
                sa.status::text as status,
                sa.note,
                sa.created_at,
                sa.updated_at,

                s.name as store_name,
                s.code as store_code,

                u.name as employee_name,
                u.email as employee_email,

                shift.name as shift_name,
                shift.code as shift_code,
                shift.start_time as shift_start_time,
                shift.end_time as shift_end_time,

                CASE
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.scheduled_start IS NOT NULL
                     AND sa.clock_in_at > sa.scheduled_start
                    THEN true
                    ELSE false
                END as is_late,

                CASE
                    WHEN sa.clock_out_at IS NOT NULL
                     AND sa.scheduled_end IS NOT NULL
                     AND sa.clock_out_at > sa.scheduled_end
                    THEN true
                    ELSE false
                END as is_overtime,

                CASE
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.scheduled_start IS NOT NULL
                     AND sa.clock_in_at > sa.scheduled_start
                    THEN FLOOR(EXTRACT(EPOCH FROM (sa.clock_in_at - sa.scheduled_start)) / 60)::int
                    ELSE 0
                END as late_minutes,

                CASE
                    WHEN sa.clock_out_at IS NOT NULL
                     AND sa.scheduled_end IS NOT NULL
                     AND sa.clock_out_at > sa.scheduled_end
                    THEN FLOOR(EXTRACT(EPOCH FROM (sa.clock_out_at - sa.scheduled_end)) / 60)::int
                    ELSE 0
                END as overtime_minutes,

                CASE
                    WHEN sa.clock_in_at IS NOT NULL
                     AND sa.clock_out_at IS NOT NULL
                     AND sa.clock_out_at > sa.clock_in_at
                    THEN FLOOR(EXTRACT(EPOCH FROM (sa.clock_out_at - sa.clock_in_at)) / 60)::int
                    ELSE 0
                END as work_minutes
            ")
            ->when($q, function ($query) use ($q) {
                $like = "%{$q}%";

                $query->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('s.code', 'ILIKE', $like)
                        ->orWhere('u.name', 'ILIKE', $like)
                        ->orWhere('u.email', 'ILIKE', $like)
                        ->orWhere('shift.name', 'ILIKE', $like)
                        ->orWhere('shift.code', 'ILIKE', $like)
                        ->orWhere('sa.note', 'ILIKE', $like)
                        ->orWhere(DB::raw('sa.status::text'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(sa.id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($storeId, fn ($query) => $query->where('sa.store_id', $storeId))
            ->when($userId, fn ($query) => $query->where('sa.user_id', $userId))
            ->when($shiftId, fn ($query) => $query->where('sa.attendance_shift_id', $shiftId))
            ->when($status, fn ($query) => $query->where(DB::raw('sa.status::text'), $status))
            ->when($from, fn ($query) => $query->whereDate('sa.date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('sa.date', '<=', $to));

        $query = DB::query()->fromSub($base, 'x');

        if ($attendanceType === 'late') {
            $query->where('x.is_late', true);
        }

        if ($attendanceType === 'overtime') {
            $query->where('x.is_overtime', true);
        }

        if ($attendanceType === 'late_overtime') {
            $query->where('x.is_late', true)
                ->where('x.is_overtime', true);
        }

        if ($attendanceType === 'not_clock_in') {
            $query->whereNull('x.clock_in_at');
        }

        if ($attendanceType === 'not_clock_out') {
            $query->whereNull('x.clock_out_at');
        }

        return $query->selectRaw('x.*');
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}