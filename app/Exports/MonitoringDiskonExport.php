<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringDiskonExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $name = $request->get('name');
        $type = $request->get('type');
        $isActive = $request->get('is_active');
        $periodStatus = $request->get('period_status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = DB::table('discounts as d')
            ->leftJoin('promos as p', 'p.id', '=', 'd.promo_id')
            ->select([
                'd.id',
                'd.name',
                'd.description',
                'd.type',
                'd.value',
                'd.max_discount',
                'd.min_purchase',
                'd.valid_from',
                'd.valid_until',
                'd.usage_limit',
                'd.usage_count',
                'd.is_active',
                'd.priority',
                'd.combinable',
                'd.created_at',
                DB::raw("COALESCE(p.name, '-') as promo_name"),
            ])
            ->when($name, function ($q) use ($name) {
                $q->where('d.name', 'ilike', "%{$name}%");
            })
            ->when($type, function ($q) use ($type) {
                $q->where('d.type', $type);
            })
            ->when($isActive !== null && $isActive !== '', function ($q) use ($isActive) {
                $q->where('d.is_active', (bool) $isActive);
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('d.valid_from', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('d.valid_until', '<=', $endDate);
            })
            ->when($periodStatus, function ($q) use ($periodStatus) {
                if ($periodStatus === 'active') {
                    $q->whereNotNull('d.valid_from')
                        ->whereNotNull('d.valid_until')
                        ->whereRaw('CURRENT_TIMESTAMP BETWEEN d.valid_from AND d.valid_until');
                }

                if ($periodStatus === 'not_started') {
                    $q->whereNotNull('d.valid_from')
                        ->whereRaw('CURRENT_TIMESTAMP < d.valid_from');
                }

                if ($periodStatus === 'expired') {
                    $q->whereNotNull('d.valid_until')
                        ->whereRaw('CURRENT_TIMESTAMP > d.valid_until');
                }

                if ($periodStatus === 'no_period') {
                    $q->whereNull('d.valid_from')
                        ->whereNull('d.valid_until');
                }
            })
            ->orderByDesc('d.created_at');

        $this->rows = $query->get();

        $this->filters = [
            'name' => $name,
            'type' => $type,
            'is_active' => $isActive,
            'period_status' => $periodStatus,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function title(): string
    {
        return 'Monitoring Diskon';
    }

    public function array(): array
    {
        $printedAt = now()->format('d-m-Y H:i:s');

        $typeLabel = match ($this->filters['type'] ?? '') {
            'percentage' => 'Percentage',
            'fixed' => 'Fixed',
            default => 'Semua Tipe',
        };

        $activeLabel = match ((string) ($this->filters['is_active'] ?? '')) {
            '1' => 'Aktif',
            '0' => 'Nonaktif',
            default => 'Semua Status',
        };

        $periodLabel = match ($this->filters['period_status'] ?? '') {
            'active' => 'Aktif',
            'not_started' => 'Belum Mulai',
            'expired' => 'Expired',
            'no_period' => 'Tanpa Periode',
            default => 'Semua Periode',
        };

        $startDate = $this->filters['start_date'] ?: '-';
        $endDate = $this->filters['end_date'] ?: '-';
        $name = $this->filters['name'] ?: '-';

        $totalDiscounts = $this->rows->count();
        $totalActive = $this->rows->where('is_active', true)->count();
        $totalInactive = $this->rows->where('is_active', false)->count();

        $data = [
            ['LAPORAN MONITORING DISKON'],
            [''],
            ['Tanggal Export', ': ' . $printedAt],
            ['Nama Diskon', ': ' . $name],
            ['Tipe', ': ' . $typeLabel],
            ['Status Aktif', ': ' . $activeLabel],
            ['Status Periode', ': ' . $periodLabel],
            ['Periode Filter', ': ' . $startDate . ' s/d ' . $endDate],
            [''],
            [
                'No',
                'Nama Diskon',
                'Promo',
                'Tipe',
                'Nilai',
                'Max Discount',
                'Min Purchase',
                'Valid From',
                'Valid Until',
                'Status Periode',
                'Status Aktif',
                'Usage Limit',
                'Usage Count',
                'Priority',
                'Combinable',
                'Deskripsi',
                'Created At',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $periodStatus = 'Tanpa Periode';

            if ($row->valid_from && $row->valid_until) {
                $now = now();

                if ($now->lt(\Carbon\Carbon::parse($row->valid_from))) {
                    $periodStatus = 'Belum Mulai';
                } elseif ($now->between(\Carbon\Carbon::parse($row->valid_from), \Carbon\Carbon::parse($row->valid_until))) {
                    $periodStatus = 'Aktif';
                } elseif ($now->gt(\Carbon\Carbon::parse($row->valid_until))) {
                    $periodStatus = 'Expired';
                }
            }

            $data[] = [
                $i + 1,
                $row->name ?? '',
                $row->promo_name ?? '-',
                $row->type ?? '-',
                (float) ($row->value ?? 0),
                $row->max_discount !== null ? (float) $row->max_discount : '',
                $row->min_purchase !== null ? (float) $row->min_purchase : '',
                $row->valid_from ? \Carbon\Carbon::parse($row->valid_from)->format('d-m-Y H:i') : '',
                $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d-m-Y H:i') : '',
                $periodStatus,
                $row->is_active ? 'Aktif' : 'Nonaktif',
                (int) ($row->usage_limit ?? 0),
                (int) ($row->usage_count ?? 0),
                (int) ($row->priority ?? 0),
                $row->combinable ? 'Ya' : 'Tidak',
                $row->description ?? '',
                $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '',
            ];
        }

        $data[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTAL',
            '',
            $this->rows->sum(fn ($row) => (int) ($row->usage_limit ?? 0)),
            $this->rows->sum(fn ($row) => (int) ($row->usage_count ?? 0)),
            '',
            '',
            'Jumlah Data: ' . $totalDiscounts . ' | Aktif: ' . $totalActive . ' | Nonaktif: ' . $totalInactive,
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 10;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:Q1');

        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B8')->applyFromArray([
            'font' => ['size' => 11],
        ]);

        $sheet->getStyle('A3:A8')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle("A{$headerRow}:Q{$headerRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:Q{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$lastRow}:Q{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAD3'],
            ],
        ]);

        $sheet->getStyle("A11:A{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E11:G{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("L11:N{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("A1:Q{$lastRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->freezePane('A11');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(16);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(12);
        $sheet->getColumnDimension('N')->setWidth(10);
        $sheet->getColumnDimension('O')->setWidth(12);
        $sheet->getColumnDimension('P')->setWidth(36);
        $sheet->getColumnDimension('Q')->setWidth(18);

        return [];
    }
}