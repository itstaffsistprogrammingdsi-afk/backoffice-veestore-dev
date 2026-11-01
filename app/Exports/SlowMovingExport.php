<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SlowMovingExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $rows;
    protected array $filters;

    public function __construct(Collection $rows, array $filters = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Slow Moving';
    }

    public function array(): array
    {
        $warehouseName = $this->filters['warehouse_name'] ?? '-';
        $startDate = $this->formatDate($this->filters['start_date'] ?? null);
        $endDate = $this->formatDate($this->filters['end_date'] ?? null);

        $periode = '-';
        if ($startDate !== '-' && $endDate !== '-') {
            $periode = $startDate . ' s/d ' . $endDate;
        } elseif ($startDate !== '-') {
            $periode = $startDate;
        } elseif ($endDate !== '-') {
            $periode = $endDate;
        }

        $data = [
            ['LAPORAN SLOW MOVING'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Warehouse', ': ' . $warehouseName],
            ['Periode', ': ' . $periode],
            [''],
            ['No', 'Kode Produk', 'Nama Produk', 'Status', 'Room', 'Rack', 'Layer', 'Qty'],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->product_code,
                $row->product_name,
                $row->status,
                $row->room_name ?? '-',
                $row->rack_name ?? '-',
                $row->layer_name ?? '-',
                (int) $row->quantity,
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 7;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:H1');

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B5')->applyFromArray([
            'font' => [
                'size' => 11,
            ],
        ]);

        $sheet->getStyle('A3:A5')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
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

        $sheet->getStyle("A{$headerRow}:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A8:A{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("H8:H{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        $sheet->freezePane('A8');

        return [];
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        try {
            return Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable $th) {
            return $date;
        }
    }
}