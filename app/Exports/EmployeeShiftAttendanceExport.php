<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeShiftAttendanceExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = $this->normalizeNullableString($request->query('q'));
        $storeId = $this->normalizeNullableString($request->query('store_id'));
        $userId = $this->normalizeNullableString($request->query('user_id'));
        $shiftId = $this->normalizeNullableString($request->query('shift_id'));
        $status = $this->normalizeNullableString($request->query('status'));
        $attendanceType = $this->normalizeNullableString($request->query('attendance_type'));
        $from = $this->normalizeNullableString($request->query('from')) ?? now()->startOfMonth()->toDateString();
        $to = $this->normalizeNullableString($request->query('to')) ?? now()->toDateString();

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
                        ->orWhere(DB::raw('sa.status::text'), 'ILIKE', $like);
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

        $this->rows = $query
            ->selectRaw('x.*')
            ->orderByDesc('x.date')
            ->orderBy('x.store_name')
            ->orderBy('x.employee_name')
            ->get();

        $this->filters = [
            'q' => $q ?: '-',
            'store_name' => $storeId ? (DB::table('stores')->where('id', $storeId)->value('name') ?? '-') : '-',
            'employee_name' => $userId ? (DB::table('users')->where('id', $userId)->value('name') ?? '-') : '-',
            'shift_name' => $shiftId ? (DB::table('store_attendance_shifts')->where('id', $shiftId)->value('name') ?? '-') : '-',
            'status' => $status ?: '-',
            'attendance_type' => $attendanceType ?: '-',
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Shift Karyawan';
    }

    public function array(): array
    {
        $data = [
            ['MONITORING SHIFT KERJA KARYAWAN'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Periode', ': ' . $this->filters['from'] . ' s/d ' . $this->filters['to']],
            ['Pencarian', ': ' . $this->filters['q']],
            ['Store', ': ' . $this->filters['store_name']],
            ['Karyawan', ': ' . $this->filters['employee_name']],
            ['Shift', ': ' . $this->filters['shift_name']],
            ['Status', ': ' . $this->filters['status']],
            ['Tipe Absensi', ': ' . $this->filters['attendance_type']],
            [''],
            [
                'No',
                'Tanggal',
                'Store',
                'Kode Store',
                'Karyawan',
                'Email',
                'Shift',
                'Kode Shift',
                'Jadwal Mulai',
                'Jadwal Selesai',
                'Clock In',
                'Clock Out',
                'Status',
                'Telat',
                'Menit Telat',
                'Over Jam',
                'Menit Over',
                'Durasi Kerja',
                'Note',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->date ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '',
                $row->store_name ?? '',
                $row->store_code ?? '',
                $row->employee_name ?? '',
                $row->employee_email ?? '',
                $row->shift_name ?? '',
                $row->shift_code ?? '',
                $row->scheduled_start ? \Carbon\Carbon::parse($row->scheduled_start)->format('d-m-Y H:i') : '',
                $row->scheduled_end ? \Carbon\Carbon::parse($row->scheduled_end)->format('d-m-Y H:i') : '',
                $row->clock_in_at ? \Carbon\Carbon::parse($row->clock_in_at)->format('d-m-Y H:i') : '',
                $row->clock_out_at ? \Carbon\Carbon::parse($row->clock_out_at)->format('d-m-Y H:i') : '',
                $row->status ?? '',
                $row->is_late ? 'Ya' : 'Tidak',
                $row->late_minutes ?? 0,
                $row->is_overtime ? 'Ya' : 'Tidak',
                $row->overtime_minutes ?? 0,
                $this->formatMinutes((int) ($row->work_minutes ?? 0)),
                $row->note ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 12;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:S1');

        $sheet->getStyle('A1:S1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:S{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '198754'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        if ($lastRow >= $headerRow) {
            $sheet->getStyle("A{$headerRow}:S{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ]);
        }

        return [];
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 menit';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }

        if ($hours > 0) {
            return $hours . ' jam';
        }

        return $remainingMinutes . ' menit';
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