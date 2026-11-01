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

class StokPercabangStoreSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
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
        $name = $this->filters['store_name'] ?? 'Cabang';
        $code = $this->filters['store_code'] ?? '';

        $title = $code ? $code . ' - ' . $name : $name;
        $title = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '-', $title);
        $title = trim($title);

        return mb_substr($title ?: 'Cabang', 0, 31);
    }

    public function array(): array
    {
        $storeName = $this->filters['store_name'] ?? 'Cabang';
        $storeCode = $this->filters['store_code'] ?? '-';
        $keyword   = $this->filters['q'] ?? '-';
        $minStock  = $this->filters['min'] ?? 0;
        $printedAt = now()->format('d-m-Y H:i:s');

        $totalStore           = (int) $this->rows->sum('qty_store');
        $totalDisplay         = (int) $this->rows->sum('qty_display');
        $totalReal            = (int) $this->rows->sum('qty_real');
        $totalReserved        = (int) $this->rows->sum('qty_reserved');
        $totalAvailable       = (int) $this->rows->sum('qty_available');
        $totalGood            = (int) $this->rows->sum('qty_good');
        $totalGoodAfterRepair = (int) $this->rows->sum('qty_good_after_repair');
        $totalBad             = (int) $this->rows->sum('qty_bad');
        $totalQty             = (int) $this->rows->sum('total_qty');

        $data = [
            ['LAPORAN STOK PER CABANG'],
            [''],
            ['Tanggal Export', ': ' . $printedAt],
            ['Cabang', ': ' . $storeName],
            ['Kode Cabang', ': ' . ($storeCode ?: '-')],
            ['Keyword', ': ' . ($keyword !== '' ? $keyword : '-')],
            ['Min. Stok Tersedia', ': ' . $minStock],
            [''],
            [
                'No',
                'Kode Produk',
                'Nama Produk',
                'Satuan',
                'Store',
                'Store Display',
                'Stok Real',
                'Dipesan',
                'Stok Tersedia',
                'Good',
                'Good After Repair',
                'Bad',
                'Total Qty',
            ],
        ];

        foreach ($this->rows as $index => $row) {
            $data[] = [
                $index + 1,
                $row->product_code,
                $row->product_name,
                $row->unit_name,
                (int) $row->qty_store,
                (int) $row->qty_display,
                (int) ($row->qty_real ?? 0),
                (int) ($row->qty_reserved ?? 0),
                (int) ($row->qty_available ?? 0),
                (int) $row->qty_good,
                (int) ($row->qty_good_after_repair ?? 0),
                (int) $row->qty_bad,
                (int) $row->total_qty,
            ];
        }

        $data[] = [
            '',
            '',
            '',
            'TOTAL',
            $totalStore,
            $totalDisplay,
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

        $sheet->mergeCells('A1:M1');

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B7')->applyFromArray([
            'font' => ['size' => 11],
        ]);

        $sheet->getStyle('A3:A7')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:M{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$lastRow}:M{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAD3'],
            ],
        ]);

        $sheet->getStyle("A{$dataStartRow}:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E{$dataStartRow}:M{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("A1:M{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->freezePane('A10');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(36);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(12);

        return [];
    }
}
