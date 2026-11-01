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

class RedeemPointReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $customerId = $request->query('customer_id');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $query = DB::table('customer_loyalty_point_mutations as clpm')
            ->join('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('customer_loyalty_points as clp', 'clp.customer_id', '=', 'c.id')
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',
                'c.referral_code as customer_referral_code',
                'clp.balance as current_balance',

                DB::raw('COUNT(clpm.id) as total_mutations'),
                DB::raw('SUM(ABS(COALESCE(clpm.points, 0))) as total_redeem_points'),
                DB::raw('MAX(clpm.created_at) as last_redeem_at'),
            ])
            ->where('clpm.type', 'redeem')
            ->when($q !== '', function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('c.wa_number', 'ILIKE', $like)
                        ->orWhere('c.referral_code', 'ILIKE', $like)
                        ->orWhere('clpm.description', 'ILIKE', $like)
                        ->orWhere('clpm.ref', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.ref_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(clpm.customer_id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($customerId, function ($builder) use ($customerId) {
                $builder->where('clpm.customer_id', $customerId);
            })
            ->whereBetween(DB::raw('clpm.created_at::date'), [$from, $to])
            ->groupBy([
                'c.id',
                'c.name',
                'c.email',
                'c.wa_number',
                'c.referral_code',
                'clp.balance',
            ])
            ->orderByDesc(DB::raw('MAX(clpm.created_at)'));

        $this->rows = $query->get();

        $customerName = '-';
        if ($customerId) {
            $customerName = DB::table('customers')->where('id', $customerId)->value('name') ?? '-';
        }

        $this->filters = [
            'q' => $q,
            'customer_name' => $customerName,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Redeem Point Report';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN REDEEM POINT'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Customer', ': ' . ($this->filters['customer_name'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Customer',
                'Email',
                'WA Number',
                'Referral Code',
                'Total Mutation',
                'Total Redeem Points',
                'Current Balance',
                'Last Redeem At',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->customer_name ?? '',
                $row->customer_email ?? '',
                $row->customer_wa_number ?? '',
                $row->customer_referral_code ?? '',
                $row->total_mutations ?? 0,
                $row->total_redeem_points ?? 0,
                $row->current_balance ?? 0,
                $row->last_redeem_at ? \Carbon\Carbon::parse($row->last_redeem_at)->format('d-m-Y H:i') : '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 8;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:I1');

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        return [];
    }
}