<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StoreScheduleApprovalController extends Controller
{
    private const STATUSES = ['draft', 'submitted', 'approved', 'auto_approved'];

    private const STATUS_LABELS = [
        'draft' => 'Draft',
        'submitted' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'auto_approved' => 'Disetujui otomatis',
    ];

    private const STATUS_TONES = [
        'draft' => 'secondary',
        'submitted' => 'warning',
        'approved' => 'success',
        'auto_approved' => 'info',
    ];

    public function __construct()
    {
        $this->middleware('permission:view branch-store-schedule');
        $this->middleware('permission:approve branch-store-schedule')->only(['approve', 'reject']);
    }

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->has('status')
            ? trim((string) $request->query('status', ''))
            : 'submitted';

        if ($status === 'all') {
            $status = '';
        } elseif ($status !== '' && ! in_array($status, self::STATUSES, true)) {
            $status = 'submitted';
        }

        $month = $this->normalizeMonth($request->query('month'));

        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = DB::table('store_shift_schedules as schedule')
            ->join('stores as store', 'store.id', '=', 'schedule.store_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'schedule.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'schedule.approved_by')
            ->select([
                'schedule.id',
                'schedule.store_id',
                'schedule.schedule_month',
                'schedule.status',
                'schedule.submitted_at',
                'schedule.approved_at',
                'store.name as store_name',
                'store.code as store_code',
                'creator.name as creator_name',
                'approver.name as approver_name',
            ])
            ->selectSub(
                DB::table('store_shift_schedule_details')
                    ->selectRaw('count(distinct user_id)')
                    ->whereColumn('store_shift_schedule_id', 'schedule.id'),
                'employees_count'
            )
            ->selectSub(
                DB::table('store_shift_schedule_details')
                    ->selectRaw('count(*)')
                    ->whereColumn('store_shift_schedule_id', 'schedule.id'),
                'entries_count'
            )
            ->selectSub(
                DB::table('store_shift_schedule_details')
                    ->selectRaw('count(*)')
                    ->whereColumn('store_shift_schedule_id', 'schedule.id')
                    ->where('is_day_off', true),
                'day_off_count'
            )
            ->selectSub(
                DB::table('store_shift_schedule_details')
                    ->selectRaw('count(*)')
                    ->whereColumn('store_shift_schedule_id', 'schedule.id')
                    ->where('is_national_holiday', true),
                'holiday_count'
            )
            ->when($q !== '', function ($inner) use ($q): void {
                $inner->where(function ($search) use ($q): void {
                    $search->where('store.name', 'ILIKE', "%{$q}%")
                        ->orWhere('store.code', 'ILIKE', "%{$q}%");
                });
            })
            ->when($status !== '', fn ($inner) => $inner->where('schedule.status', $status))
            ->when($month, fn ($inner) => $inner->whereDate('schedule.schedule_month', "{$month}-01"));

        $query = $status === 'submitted'
            ? $query->orderByRaw('schedule.submitted_at ASC NULLS LAST')->orderBy('store.name')
            : $query->orderByDesc('schedule.schedule_month')->orderBy('store.name');

        $schedules = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (object $row): array => $this->listRow($row));

        return Inertia::render('Operations/StoreScheduleApproval', [
            'schedules' => $schedules,
            'stats' => $this->stats(),
            'statusOptions' => collect(self::STATUSES)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => self::STATUS_LABELS[$value] ?? $value,
                ])
                ->push(['value' => 'all', 'label' => 'Semua status'])
                ->values()
                ->all(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'month' => $month,
                'per_page' => $perPage,
            ],
            'urls' => [
                'index' => route('operations.store-schedules.index'),
                'show' => route('operations.store-schedules.show', ['schedule' => '__ID__']),
            ],
        ]);
    }

    public function show(string $schedule): Response
    {
        $row = DB::table('store_shift_schedules as schedule')
            ->join('stores as store', 'store.id', '=', 'schedule.store_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'schedule.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'schedule.approved_by')
            ->where('schedule.id', $schedule)
            ->select([
                'schedule.id',
                'schedule.store_id',
                'schedule.schedule_month',
                'schedule.status',
                'schedule.submitted_at',
                'schedule.approved_at',
                'schedule.approval_notes',
                'store.name as store_name',
                'store.code as store_code',
                'creator.name as creator_name',
                'approver.name as approver_name',
            ])
            ->first();

        abort_if(! $row, 404);

        $details = DB::table('store_shift_schedule_details as detail')
            ->join('users as employee', 'employee.id', '=', 'detail.user_id')
            ->leftJoin('store_attendance_shifts as shift', 'shift.id', '=', 'detail.attendance_shift_id')
            ->where('detail.store_shift_schedule_id', $schedule)
            ->orderBy('employee.name')
            ->orderBy('detail.schedule_date')
            ->get([
                'detail.user_id',
                'employee.name as employee_name',
                'detail.schedule_date',
                'detail.attendance_shift_id',
                'shift.code as shift_code',
                'shift.name as shift_name',
                'shift.start_time',
                'shift.end_time',
                'detail.is_day_off',
                'detail.is_national_holiday',
                'detail.notes',
            ]);

        $month = Carbon::parse((string) $row->schedule_month);
        $daysInMonth = $month->daysInMonth;

        $employees = $details
            ->groupBy('user_id')
            ->map(function ($rows, string $userId) use ($daysInMonth): array {
                $days = array_fill(0, $daysInMonth, null);

                foreach ($rows as $detail) {
                    $day = (int) Carbon::parse((string) $detail->schedule_date)->day;

                    if ($day < 1 || $day > $daysInMonth) {
                        continue;
                    }

                    $days[$day - 1] = [
                        'date' => Carbon::parse((string) $detail->schedule_date)->toDateString(),
                        'shift_code' => $detail->shift_code,
                        'shift_name' => $detail->shift_name,
                        'start_time' => $this->shortTime($detail->start_time),
                        'end_time' => $this->shortTime($detail->end_time),
                        'is_day_off' => (bool) $detail->is_day_off,
                        'is_national_holiday' => (bool) $detail->is_national_holiday,
                        'notes' => $detail->notes,
                    ];
                }

                return [
                    'id' => (string) $userId,
                    'name' => (string) $rows->first()->employee_name,
                    'days' => $days,
                ];
            })
            ->values()
            ->all();

        $shifts = DB::table('store_attendance_shifts')
            ->where('store_id', $row->store_id)
            ->orderBy('start_time')
            ->get(['id', 'name', 'code', 'start_time', 'end_time', 'is_active'])
            ->map(fn (object $shift): array => [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'code' => $shift->code,
                'start_time' => $this->shortTime($shift->start_time),
                'end_time' => $this->shortTime($shift->end_time),
                'is_active' => (bool) $shift->is_active,
            ])
            ->values()
            ->all();

        $user = Auth::user();

        return Inertia::render('Operations/StoreScheduleApprovalDetail', [
            'schedule' => [
                'id' => (string) $row->id,
                'store' => [
                    'id' => (string) $row->store_id,
                    'name' => $row->store_name,
                    'code' => $row->store_code,
                ],
                'schedule_month' => $month->toDateString(),
                'month_label' => $this->monthLabel((string) $row->schedule_month),
                'days_in_month' => $daysInMonth,
                'status' => $row->status,
                'status_label' => self::STATUS_LABELS[$row->status] ?? $row->status,
                'status_tone' => self::STATUS_TONES[$row->status] ?? 'secondary',
                'submitted_at' => $row->submitted_at,
                'approved_at' => $row->approved_at,
                'created_by' => $row->creator_name,
                'approved_by' => $row->approver_name,
            ],
            'employees' => $employees,
            'shifts' => $shifts,
            'summary' => [
                'employees_count' => $details->pluck('user_id')->unique()->count(),
                'entries_count' => $details->count(),
                'day_off_count' => $details->where('is_day_off', true)->count(),
                'holiday_count' => $details->where('is_national_holiday', true)->count(),
                'shift_slots' => $details->whereNotNull('attendance_shift_id')->count(),
            ],
            'audit' => $this->auditEntries($row->approval_notes),
            'can_approve' => strtolower((string) $row->status) === 'submitted'
                && $user !== null
                && $user->can('approve branch-store-schedule'),
            'urls' => [
                'index' => route('operations.store-schedules.index'),
                'approve' => route('operations.store-schedules.approve', ['schedule' => $schedule]),
                'reject' => route('operations.store-schedules.reject', ['schedule' => $schedule]),
            ],
        ]);
    }

    public function approve(Request $request, string $schedule): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $message = '';
        $updated = false;

        DB::transaction(function () use ($schedule, $data, &$message, &$updated): void {
            $row = DB::table('store_shift_schedules')
                ->where('id', $schedule)
                ->lockForUpdate()
                ->first();

            abort_if(! $row, 404);

            if ((string) $row->status !== 'submitted') {
                $message = 'Jadwal ini sudah diproses. Muat ulang halaman untuk melihat status terbaru.';

                return;
            }

            $monthLabel = $this->monthLabel((string) $row->schedule_month);
            $notes = trim((string) ($data['notes'] ?? ''));

            DB::table('store_shift_schedules')
                ->where('id', $schedule)
                ->update([
                    'status' => 'approved',
                    'approved_at' => now()->toDateString(),
                    'approved_by' => Auth::id(),
                    'approval_notes' => $this->appendAudit($row->approval_notes, [
                        'at' => now()->toIso8601String(),
                        'by' => (string) Auth::id(),
                        'by_name' => Auth::user()?->name ?? 'Backoffice',
                        'action' => 'approve',
                        'target' => "Jadwal {$monthLabel}",
                        'old' => 'Menunggu approval',
                        'new' => 'Disetujui',
                        'reason' => $notes !== '' ? $notes : null,
                    ]),
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $message = "Jadwal {$monthLabel} disetujui.";
            $updated = true;
        });

        return $updated
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    public function reject(Request $request, string $schedule): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $message = '';
        $updated = false;

        DB::transaction(function () use ($schedule, $data, &$message, &$updated): void {
            $row = DB::table('store_shift_schedules')
                ->where('id', $schedule)
                ->lockForUpdate()
                ->first();

            abort_if(! $row, 404);

            if ((string) $row->status !== 'submitted') {
                $message = 'Jadwal ini sudah diproses. Muat ulang halaman untuk melihat status terbaru.';

                return;
            }

            $monthLabel = $this->monthLabel((string) $row->schedule_month);

            DB::table('store_shift_schedules')
                ->where('id', $schedule)
                ->update([
                    'status' => 'draft',
                    'submitted_at' => null,
                    'approved_at' => null,
                    'approved_by' => null,
                    'approval_notes' => $this->appendAudit($row->approval_notes, [
                        'at' => now()->toIso8601String(),
                        'by' => (string) Auth::id(),
                        'by_name' => Auth::user()?->name ?? 'Backoffice',
                        'action' => 'reject',
                        'target' => "Jadwal {$monthLabel}",
                        'old' => 'Menunggu approval',
                        'new' => 'Draft',
                        'reason' => trim((string) $data['reason']),
                    ]),
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $message = "Jadwal {$monthLabel} dikembalikan ke draft. Toko dapat memperbaiki dan mengajukan ulang.";
            $updated = true;
        });

        return $updated
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'store' => [
                'id' => (string) $row->store_id,
                'name' => $row->store_name,
                'code' => $row->store_code,
            ],
            'schedule_month' => Carbon::parse((string) $row->schedule_month)->toDateString(),
            'month_label' => $this->monthLabel((string) $row->schedule_month),
            'status' => $row->status,
            'status_label' => self::STATUS_LABELS[$row->status] ?? $row->status,
            'status_tone' => self::STATUS_TONES[$row->status] ?? 'secondary',
            'submitted_at' => $row->submitted_at,
            'approved_at' => $row->approved_at,
            'created_by' => $row->creator_name,
            'approved_by' => $row->approver_name,
            'employees_count' => (int) ($row->employees_count ?? 0),
            'entries_count' => (int) ($row->entries_count ?? 0),
            'day_off_count' => (int) ($row->day_off_count ?? 0),
            'holiday_count' => (int) ($row->holiday_count ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'pending' => DB::table('store_shift_schedules')->where('status', 'submitted')->count(),
            'approved_this_month' => DB::table('store_shift_schedules')
                ->whereIn('status', ['approved', 'auto_approved'])
                ->where('approved_at', '>=', $monthStart)
                ->count(),
            'draft' => DB::table('store_shift_schedules')->where('status', 'draft')->count(),
            'stores_pending' => DB::table('store_shift_schedules')
                ->where('status', 'submitted')
                ->distinct()
                ->count('store_id'),
        ];
    }

    private function normalizeMonth(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return null;
        }

        return $value;
    }

    private function monthLabel(string $date): string
    {
        $parsed = Carbon::parse($date);

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return ($months[$parsed->month] ?? $parsed->format('F')).' '.$parsed->year;
    }

    private function shortTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function auditEntries(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [[
                'at' => null,
                'by_name' => null,
                'action' => 'note',
                'target' => null,
                'old' => null,
                'new' => null,
                'reason' => $raw,
            ]];
        }

        $entries = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entries[] = [
                'at' => $entry['at'] ?? null,
                'by_name' => $entry['by_name'] ?? null,
                'action' => $entry['action'] ?? 'note',
                'target' => $entry['target'] ?? null,
                'old' => $entry['old'] ?? null,
                'new' => $entry['new'] ?? null,
                'reason' => $entry['reason'] ?? null,
            ];
        }

        return array_reverse($entries);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function appendAudit(?string $raw, array $entry): string
    {
        $raw = trim((string) $raw);
        $decoded = [];

        if ($raw !== '') {
            $parsed = json_decode($raw, true);

            if (is_array($parsed)) {
                $decoded = array_values(array_filter($parsed, 'is_array'));
            }
        }

        $decoded[] = $entry;

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
}
