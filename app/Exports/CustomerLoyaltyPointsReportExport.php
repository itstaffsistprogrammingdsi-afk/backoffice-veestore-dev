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

class CustomerLoyaltyPointsReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $mutationType = $request->query('mutation_type');
        $type = $request->query('type');
        $isUsed = $request->query('is_used');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $query = DB::table('customer_loyalty_point_mutations as clpm')
            ->leftJoin('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('customer_loyalty_points as clp', 'clp.customer_id', '=', 'clpm.customer_id')
            ->select([
                'clpm.id',
                'clpm.customer_id',
                'clpm.ref_id',
                'clpm.type',
                'clpm.points',
                'clpm.balance_after',
                'clpm.description',
                'clpm.created_at',
                'clpm.updated_at',
                'clpm.ref',
                'clpm.customer_loyalty_point_id',
                'clpm.mutation_type',
                'clpm.expired_at',

                DB::raw('CASE WHEN COALESCE(clpm.used_points, 0) > 0 THEN true ELSE false END as is_used'),

                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',

                DB::raw('COALESCE(clp.balance, 0) as current_balance'),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('c.wa_number', 'ILIKE', $like)
                        ->orWhere('clpm.description', 'ILIKE', $like)
                        ->orWhere('clpm.ref', 'ILIKE', $like);
                });
            })
            ->when($mutationType, function ($qBuilder) use ($mutationType) {
                $qBuilder->where('clpm.mutation_type', $mutationType);
            })
            ->when($type, function ($qBuilder) use ($type) {
                $qBuilder->where('clpm.type', $type);
            })
            ->when($isUsed !== null && $isUsed !== '', function ($qBuilder) use ($isUsed) {
                $isUsed = filter_var($isUsed, FILTER_VALIDATE_BOOLEAN);

                if ($isUsed) {
                    $qBuilder->where('clpm.used_points', '>', 0);
                    return;
                }

                $qBuilder->where(function ($sub) {
                    $sub->whereNull('clpm.used_points')
                        ->orWhere('clpm.used_points', '<=', 0);
                });
            })
            ->whereBetween(DB::raw('clpm.created_at::date'), [$from, $to])
            ->orderByDesc('clpm.created_at')
            ->orderBy('c.name');

        $this->rows = $query->get();

        $this->filters = [
            'q' => $q,
            'mutation_type' => $mutationType ?: '-',
            'type' => $type ?: '-',
            'is_used' => ($isUsed === '' || $isUsed === null) ? '-' : ($isUsed ? 'Ya' : 'Tidak'),
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Customer Loyalty Points';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN CUSTOMER LOYALTY POINTS'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Mutation Type', ': ' . ($this->filters['mutation_type'] ?: '-')],
            ['Type', ': ' . ($this->filters['type'] ?: '-')],
            ['Is Used', ': ' . ($this->filters['is_used'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal',
                'Nama Customer',
                'Email',
                'No. WA',
                'Current Balance',
                'Mutation Type',
                'Type',
                'Points',
                'Balance After',
                'Description',
                'Reference',
                'Reference ID',
                'Is Used',
                'Expired At',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '',
                $row->customer_name ?? '',
                $row->customer_email ?? '',
                $row->customer_wa_number ?? '',
                (float) ($row->current_balance ?? 0),
                $row->mutation_type ?? '',
                $row->type ?? '',
                (float) ($row->points ?? 0),
                (float) ($row->balance_after ?? 0),
                $row->description ?? '',
                $row->ref ?? '',
                $row->ref_id ?? '',
                ($row->is_used ?? false) ? 'Ya' : 'Tidak',
                $row->expired_at ? \Carbon\Carbon::parse($row->expired_at)->format('d-m-Y H:i') : '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 10;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:O1');

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:O{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:O{$lastRow}")->applyFromArray([
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
