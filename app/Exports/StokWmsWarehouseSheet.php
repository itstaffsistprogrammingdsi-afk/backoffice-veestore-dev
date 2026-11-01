<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StokWmsWarehouseSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $rows;
    protected array $filters;

    public function __construct(Collection $rows, array $filters = [])
    {
        $this->rows = $rows->values();
        $this->filters = $filters;
    }

    public function title(): string
    {
        $name = $this->filters['warehouse_name'] ?? 'Warehouse';
        $code = $this->filters['warehouse_code'] ?? '';

        $title = $code ? $code . ' - ' . $name : $name;

        // Excel sheet title max 31 chars, tidak boleh ada karakter tertentu
        $title = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '-', $title);
        $title = trim($title);

        return mb_substr($title ?: 'Warehouse', 0, 31);
    }

    public function array(): array
    {
        $warehouseName = $this->filters['warehouse_name'] ?? 'Warehouse';
        $warehouseCode = $this->filters['warehouse_code'] ?? '-';
        $keyword       = $this->filters['q'] ?? '-';
        $minStock      = $this->filters['min'] ?? 0;
        $printedAt     = now()->format('d-m-Y H:i:s');

        $totalGood            = (int) $this->rows->sum('qty_good');
        $totalGoodAfterRepair = (int) $this->rows->sum('qty_good_after_repair');
        $totalBad             = (int) $this->rows->sum('qty_bad');
        $totalReal            = (int) $this->rows->sum('qty_real');
        $totalReserved        = (int) $this->rows->sum('qty_reserved');
        $totalAvailable       = (int) $this->rows->sum('qty_available');
        $totalQty             = (int) $this->rows->sum('total_qty');

        $data = [
            ['LAPORAN STOK WMS'],
            [''],
            ['Tanggal Export', ': ' . $printedAt],
            ['Warehouse', ': ' . $warehouseName],
            ['Kode Warehouse', ': ' . ($warehouseCode ?: '-')],
            ['Keyword', ': ' . ($keyword !== '' ? $keyword : '-')],
            ['Min. Stok Tersedia', ': ' . $minStock],
            [''],
            [
                'No',
                'Kode Produk',
                'Nama Produk',
                'Satuan',
                'Stok Real',
                'Dipesan',
                'Stok Tersedia',
                'Qty Good',
                'Qty Good After Repair',
                'Qty Bad',
                'Total Qty',
            ],
        ];

        foreach ($this->rows as $index => $row) {
            $data[] = [
                $index + 1,
                $row->product_code,
                $row->product_name,
                $row->unit_name,
                (int) ($row->qty_real ?? 0),
                (int) ($row->qty_reserved ?? 0),
                (int) ($row->qty_available ?? 0),
                (int) ($row->qty_good ?? 0),
                (int) ($row->qty_good_after_repair ?? 0),
                (int) ($row->qty_bad ?? 0),
                (int) ($row->total_qty ?? 0),
            ];
        }

        $data[] = [
            '',
            '',
            '',
            'TOTAL',
            $totalReal,
            $totalReserved,
            $totalAvailable,
            $totalGood,
            $totalGoodAfterRepair,
            $totalBad,
            $totalQty,
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 9;
        $dataStartRow = 10;
        $lastRow = $headerRow + $this->rows->count() + 1;

        $sheet->mergeCells('A1:K1');

        // Title
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Info filter
        $sheet->getStyle('A3:B7')->applyFromArray([
            'font' => ['size' => 11],
        ]);

        $sheet->getStyle('A3:A7')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Header tabel
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
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
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Border tabel
        $sheet->getStyle("A{$headerRow}:K{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        // Row total
        $sheet->getStyle("A{$lastRow}:K{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAD3'],
            ],
        ]);

        // Alignment
        $sheet->getStyle("A{$dataStartRow}:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E{$dataStartRow}:K{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("A1:K{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Freeze
        $sheet->freezePane('A10');

        // Width
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(36);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(12);

        return [];
    }
}
