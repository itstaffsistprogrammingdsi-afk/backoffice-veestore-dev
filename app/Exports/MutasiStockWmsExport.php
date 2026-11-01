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

class MutasiStockWmsExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $rows;
    protected Collection $productDetails;
    protected array $filters;

    public function __construct(Collection $rows, Collection $productDetails, array $filters = [])
    {
        $this->rows = $rows;
        $this->productDetails = $productDetails;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Mutasi Stock WMS';
    }

    public function array(): array
    {
        $warehouseName = $this->filters['warehouse_name'] ?? 'Semua Warehouse';
        $outboundType  = $this->filters['outbound_type'] ?? '-';
        $dateFrom      = $this->filters['date_from'] ?? '-';
        $dateTo        = $this->filters['date_to'] ?? '-';
        $keyword       = $this->filters['q'] ?? '-';
        $printedAt     = now()->format('d-m-Y H:i:s');

        $grandItems = (int) $this->rows->sum('total_items');
        $grandSku   = (int) $this->rows->sum('total_sku');

        $data = [
            ['LAPORAN MUTASI STOCK WMS'],
            [''],
            ['Tanggal Export', ': ' . $printedAt],
            ['Warehouse', ': ' . $warehouseName],
            ['Outbound Type', ': ' . ($outboundType ?: '-')],
            ['Periode', ': ' . ($dateFrom ?: '-') . ' s/d ' . ($dateTo ?: '-')],
            ['Keyword', ': ' . ($keyword !== '' ? $keyword : '-')],
            [''],
            [
                'No',
                'Tanggal',
                'Warehouse',
                'Invoice',
                'Delivery Order',
                'Order Number',
                'Shipping Number',
                'Outbound Type',
                'Courier',
                'PIC/User',
                'Total SKU',
                'Total Item',
                'Kode Produk',
                'Nama Produk',
                'Qty Produk',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $details = collect($this->productDetails->get($row->id, []));

            if ($details->isEmpty()) {
                $data[] = [
                    $i + 1,
                    $row->event_date ? \Carbon\Carbon::parse($row->event_date)->format('d-m-Y H:i') : '',
                    $row->warehouse_name,
                    $row->invoice_number,
                    $row->delivery_order_number,
                    $row->order_number,
                    $row->shipping_number,
                    $row->outbound_type_label ?? $row->outbound_type,
                    $row->courier_name,
                    $row->user_name,
                    (int) $row->total_sku,
                    (int) $row->total_items,
                    '',
                    '',
                    '',
                ];
                continue;
            }

            $first = true;
            foreach ($details as $detail) {
                $data[] = [
                    $first ? $i + 1 : '',
                    $first ? ($row->event_date ? \Carbon\Carbon::parse($row->event_date)->format('d-m-Y H:i') : '') : '',
                    $first ? $row->warehouse_name : '',
                    $first ? $row->invoice_number : '',
                    $first ? $row->delivery_order_number : '',
                    $first ? $row->order_number : '',
                    $first ? $row->shipping_number : '',
                    $first ? ($row->outbound_type_label ?? $row->outbound_type) : '',
                    $first ? $row->courier_name : '',
                    $first ? $row->user_name : '',
                    $first ? (int) $row->total_sku : '',
                    $first ? (int) $row->total_items : '',
                    $detail->product_code,
                    $detail->product_name,
                    (int) $detail->qty,
                ];

                $first = false;
            }
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
            $grandSku,
            $grandItems,
            '',
            '',
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 9;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:O1');

        $sheet->getStyle('A1:O1')->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:O{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:O{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$lastRow}:O{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAD3'],
            ],
        ]);

        $sheet->getStyle("A10:A{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("J10:O{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("N10:N{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("A1:O{$lastRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->freezePane('A10');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(18);
        $sheet->getColumnDimension('N')->setWidth(36);
        $sheet->getColumnDimension('O')->setWidth(12);

        return [];
    }
}