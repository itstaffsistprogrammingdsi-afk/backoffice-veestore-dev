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

class ReferralScoreReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $referrerId = $request->query('referrer_id');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $query = DB::table('referrals as r')
            ->leftJoin('customers as referrer', 'referrer.id', '=', 'r.referrer_id')
            ->leftJoin('customers as referee', 'referee.id', '=', 'r.referee_id')
            ->leftJoin('referrer_scores as rs', 'rs.customer_id', '=', 'r.referrer_id')
            ->select([
                'r.id',
                'r.referrer_id',
                'r.referee_id',
                'r.code_used',
                'r.status',
                'r.created_at',

                'referrer.name as referrer_name',
                'referrer.email as referrer_email',
                'referrer.wa_number as referrer_wa_number',

                'referee.name as referee_name',
                'referee.email as referee_email',
                'referee.wa_number as referee_wa_number',

                DB::raw('COALESCE(rs.score, 0) as score'),
                DB::raw("
                    CASE
                        WHEN referee.name IS NOT NULL THEN 'Referral customer: ' || referee.name
                        ELSE 'Referral customer'
                    END as score_source
                "),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('referrer.name', 'ILIKE', $like)
                        ->orWhere('referrer.email', 'ILIKE', $like)
                        ->orWhere('referee.name', 'ILIKE', $like)
                        ->orWhere('referee.email', 'ILIKE', $like)
                        ->orWhere('r.code_used', 'ILIKE', $like)
                        ->orWhere('r.status', 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where('r.status', $status);
            })
            ->when($referrerId, function ($qBuilder) use ($referrerId) {
                $qBuilder->where('r.referrer_id', $referrerId);
            })
            ->whereBetween(DB::raw('r.created_at::date'), [$from, $to])
            ->orderByDesc('r.created_at')
            ->orderBy('referrer.name');

        $this->rows = $query->get();

        $referrerName = '-';
        if ($referrerId) {
            $referrerName = DB::table('customers')->where('id', $referrerId)->value('name') ?? '-';
        }

        $this->filters = [
            'q' => $q,
            'status' => $status ?: '-',
            'referrer_name' => $referrerName,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Referral Score Report';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN REFERRAL & SKOR REFERRER'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Referrer', ': ' . ($this->filters['referrer_name'] ?: '-')],
            ['Status', ': ' . ($this->filters['status'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal Referral',
                'Nama Referrer',
                'Email Referrer',
                'No. WA Referrer',
                'Score Referrer',
                'Asal Score',
                'Nama Referee',
                'Email Referee',
                'No. WA Referee',
                'Code Used',
                'Status',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '',
                $row->referrer_name ?? '',
                $row->referrer_email ?? '',
                $row->referrer_wa_number ?? '',
                (int) ($row->score ?? 0),
                $row->score_source ?? '',
                $row->referee_name ?? '',
                $row->referee_email ?? '',
                $row->referee_wa_number ?? '',
                $row->code_used ?? '',
                $row->status ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 9;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:L1');

        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:L{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("F" . ($headerRow + 1) . ":F{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}