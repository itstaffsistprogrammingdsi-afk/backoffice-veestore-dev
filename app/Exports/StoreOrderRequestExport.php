<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StoreOrderRequestExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
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
        return 'Store Order Requests';
    }

    public function array(): array
    {
        $storeName = $this->filters['store_name'] ?? '-';
        $warehouseName = $this->filters['warehouse_name'] ?? '-';
        $status = $this->filters['status'] ?? '-';
        $keyword = $this->filters['keyword'] ?? '-';

        $startDate = $this->formatDate($this->filters['start_date'] ?? null);
        $endDate = $this->formatDate($this->filters['end_date'] ?? null);

        $periode = '-';
        if ($startDate !== '-' && $endDate !== '-') {
            $periode = $startDate . ' s/d ' . $endDate;
        }

        $data = [
            ['LAPORAN PERMINTAAN BARANG STORE KE WMS'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Periode', ': ' . $periode],
            ['Store', ': ' . $storeName],
            ['Warehouse', ': ' . $warehouseName],
            ['Status', ': ' . $status],
            ['Keyword', ': ' . $keyword],
            [''],
            [
                'No',
                'Tanggal Request',
                'Order Number',
                'Store',
                'Store Code',
                'Warehouse',
                'Status',
                'Kode Produk',
                'Nama Produk',
                'Harga',
                'Qty Request',
                'Qty Approve',
                'Qty Receive',
                'Total Request',
                'Shipping Number',
                'Note',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->requested_at ? Carbon::parse($row->requested_at)->format('d-m-Y H:i:s') : ($row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y H:i:s') : '-'),
                $row->order_number ?? '-',
                $row->store_name ?? '-',
                $row->store_code ?? '-',
                $row->warehouse_name ?? '-',
                $row->status ?? '-',
                $row->product_code ?? '-',
                $row->product_name ?? '-',
                (float) $row->product_price,
                (int) $row->requested_qty,
                (int) $row->approved_qty,
                (int) $row->received_qty,
                (float) $row->requested_total,
                $row->shipping_number ?? '-',
                $row->note ?? '-',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 10;
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'P';

        $sheet->mergeCells("A1:{$lastColumn}1");

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B8')->applyFromArray([
            'font' => ['size' => 11],
        ]);

        $sheet->getStyle('A3:A8')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
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
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A11:A{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("J11:N{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->freezePane('A11');

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
            return '-';
        }
    }
}