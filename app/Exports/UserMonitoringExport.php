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

class UserMonitoringExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $type = $request->query('type');
        $appType = $request->query('app_type');
        $device = $request->query('device');
        $platform = $request->query('platform');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

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

        $this->rows = $query->get();

        $this->filters = [
            'q' => $q ?: '-',
            'status' => $status ?: '-',
            'type' => $type ?: '-',
            'app_type' => $appType ?: '-',
            'device' => $device ?: '-',
            'platform' => $platform ?: '-',
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Monitoring User';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN MONITORING USER'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . $this->filters['q']],
            ['Status', ': ' . $this->filters['status']],
            ['Type', ': ' . $this->filters['type']],
            ['App Type', ': ' . $this->filters['app_type']],
            ['Device', ': ' . $this->filters['device']],
            ['Platform', ': ' . $this->filters['platform']],
            ['Tanggal', ': ' . $this->filters['from'] . ' s/d ' . $this->filters['to']],
            [''],
            [
                'No',
                'Logged At',
                'Username',
                'User ID',
                'IP Address',
                'Session Token',
                'User Agent',
                'Device',
                'Platform',
                'Status',
                'Type',
                'App Type',
                'Failure Reason',
                'Last Activity',
                'Expired At',
                'ISP',
                'City',
                'Country',
                'GPS Lat',
                'GPS Lon',
                'Details',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->logged_at ? \Carbon\Carbon::parse($row->logged_at)->format('d-m-Y H:i:s') : '',
                $row->username ?? '-',
                $row->user_id ?? '-',
                $row->ip_address ?? '-',
                $row->session_token ?? '-',
                $row->user_agent ?? '-',
                $row->device ?? '-',
                $row->platform ?? '-',
                $row->status ?? '-',
                $row->type ?? '-',
                $row->app_type ?? '-',
                $row->failure_reason ?? '-',
                $row->last_activity_at ? \Carbon\Carbon::parse($row->last_activity_at)->format('d-m-Y H:i:s') : '',
                $row->expired_at ? \Carbon\Carbon::parse($row->expired_at)->format('d-m-Y H:i:s') : '',
                $row->isp ?? '-',
                $row->city ?? '-',
                $row->country ?? '-',
                $row->gps_lat ?? '-',
                $row->gps_lon ?? '-',
                is_array($row->details) || is_object($row->details)
                    ? json_encode($row->details, JSON_UNESCAPED_UNICODE)
                    : (string) $row->details,
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->mergeCells('A1:U1');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A12:U12')->applyFromArray([
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
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A12:U{$highestRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }
}