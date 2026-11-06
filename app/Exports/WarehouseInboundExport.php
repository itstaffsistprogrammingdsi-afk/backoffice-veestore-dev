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

class WarehouseInboundExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const INBOUND_TYPE_LABELS = [
        'supplier' => 'Supplier',
        'store_return' => 'Retur Store',
        'warehouse_transfer' => 'Transfer Warehouse',
    ];

    public function __construct(
        protected Collection $rows,
        protected Collection $detailMap,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Warehouse Inbound';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN WAREHOUSE INBOUND'],
            [''],
            ['Tanggal Export', ': '.now(self::APP_TIMEZONE)->format('d-m-Y H:i:s')],
            ['Periode', ': '.$this->formatPeriod()],
            ['Warehouse Tujuan', ': '.($this->filters['warehouse_name'] ?? 'Semua Warehouse')],
            ['Warehouse Asal', ': '.($this->filters['source_warehouse_name'] ?? 'Semua Warehouse Asal')],
            ['Supplier', ': '.($this->filters['supplier_name'] ?? 'Semua Supplier')],
            ['Tipe Inbound', ': '.($this->filters['inbound_type'] ?? 'Semua Tipe')],
            ['Keyword', ': '.(($this->filters['q'] ?? null) ?: '-')],
            [''],
            ['RINGKASAN'],
            ['Total Inbound', 'Total SKU', 'Total Qty', 'Total Nilai'],
            [
                $this->rows->count(),
                (int) $this->rows->sum('total_sku'),
                (int) $this->rows->sum('total_qty'),
                $this->detailValueTotal(),
            ],
            [''],
            [
                'No',
                'Tanggal Diterima',
                'Warehouse Tujuan',
                'Kode Warehouse',
                'Tipe Inbound',
                'Asal Inbound',
                'Referensi Sumber',
                'Supplier',
                'Invoice',
                'Delivery Order',
                'Batch Number',
                'Outbound Reference',
                'Store Return',
                'PIC Received',
                'PIC Created',
                'Qty Header',
                'Total SKU',
                'Total Qty',
                'Grand Total',
                'Kode Produk',
                'Nama Produk',
                'Qty Detail',
                'Expired Date',
                'Harga IDR',
                'Currency',
                'Exchange Rate',
                'Exchange Amount',
                'Item Count',
                'Good',
                'Bad',
                'Other',
                'Last QC At',
                'Condition Summary',
                'Note',
            ],
        ];

        foreach ($this->rows->values() as $index => $row) {
            $details = collect($this->detailMap->get($row->id, []));

            if ($details->isEmpty()) {
                $data[] = $this->mapRow($row, null, $index + 1, true);

                continue;
            }

            foreach ($details as $detailIndex => $detail) {
                $data[] = $this->mapRow($row, $detail, $index + 1, $detailIndex === 0);
            }
        }

        return $data;
    }

    private function detailValueTotal(): float
    {
        return (float) $this->detailMap->reduce(function (float $carry, mixed $details): float {
            return $carry + (float) collect($details)->sum(
                fn (object $detail): float => (float) ($detail->quantity ?? 0) * (float) ($detail->price_idr ?? 0)
            );
        }, 0.0);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = 'AH';
        $headerRow = 15;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A11:{$lastColumn}11");

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:A9')->getFont()->setBold(true);

        $sheet->getStyle("A11:{$lastColumn}11")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B5CF6'],
            ],
        ]);

        $sheet->getStyle('A12:D12')->applyFromArray($this->headerStyle('4338CA'));
        $sheet->getStyle('A12:D13')->applyFromArray($this->borderStyle());
        $sheet->getStyle('A13:D13')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
            ->applyFromArray($this->headerStyle('1E293B'));
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
            ->applyFromArray($this->borderStyle());

        if ($lastRow > $headerRow) {
            $firstDataRow = $headerRow + 1;

            $sheet->getStyle("P{$firstDataRow}:S{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $sheet->getStyle("V{$firstDataRow}:V{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $sheet->getStyle("X{$firstDataRow}:X{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $sheet->getStyle("Z{$firstDataRow}:Z{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle("AA{$firstDataRow}:AB{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $sheet->getStyle("AC{$firstDataRow}:AE{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->freezePane('A16');
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }

    private function mapRow(object $row, ?object $detail, int $number, bool $first): array
    {
        return [
            $first ? $number : '',
            $first ? $this->formatDateTime($row->event_date ?? $row->received_date ?? null) : '',
            $first ? $this->safeText($row->warehouse_name ?? '-') : '',
            $first ? $this->safeText($row->warehouse_code ?? '-') : '',
            $first ? $this->inboundTypeLabel($row->inbound_type ?? null) : '',
            $first ? $this->safeText($row->origin_name ?? '-') : '',
            $first ? $this->safeText($row->origin_reference ?? '-') : '',
            $first ? $this->safeText($row->supplier_name ?? '-') : '',
            $first ? $this->safeText($row->invoice_number ?? '-') : '',
            $first ? $this->safeText($row->delivery_order_number ?? '-') : '',
            $first ? $this->safeText($row->batch_number ?? '-') : '',
            $first ? $this->safeText($row->outbound_reference_number ?? '-') : '',
            $first ? $this->safeText($row->store_return_number ?? '-') : '',
            $first ? $this->safeText($row->received_by_name ?? '-') : '',
            $first ? $this->safeText($row->created_by_name ?? '-') : '',
            $first ? (int) ($row->quantity_item ?? 0) : '',
            $first ? (int) ($row->total_sku ?? 0) : '',
            $first ? (int) ($row->total_qty ?? 0) : '',
            $first ? (float) ($row->grand_total ?? 0) : '',
            $this->safeText($detail->product_code ?? '-'),
            $this->safeText($detail->product_name ?? '-'),
            (int) ($detail->quantity ?? 0),
            $this->formatDate($detail->expired_date ?? null),
            (float) ($detail->price_idr ?? 0),
            $this->safeText($detail->exchange_currency ?? '-'),
            $detail?->exchange_rate !== null ? (float) $detail->exchange_rate : '',
            $detail?->exchange_amount !== null ? (float) $detail->exchange_amount : '',
            (int) ($detail->item_count ?? 0),
            (int) ($detail->qty_good ?? 0),
            (int) ($detail->qty_bad ?? 0),
            (int) ($detail->qty_other ?? 0),
            $this->formatDateTime($detail->last_qc_at ?? null),
            $this->safeText($detail->condition_summary ?? '-'),
            $this->safeText($detail->note ?? '-'),
        ];
    }

    private function formatPeriod(): string
    {
        $dateFrom = $this->formatDate($this->filters['date_from'] ?? null);
        $dateTo = $this->formatDate($this->filters['date_to'] ?? null);

        if ($dateFrom === '-' && $dateTo === '-') {
            return 'Semua Tanggal';
        }

        return ($dateFrom === '-' ? $dateTo : $dateFrom).' s/d '.($dateTo === '-' ? $dateFrom : $dateTo);
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return '-';
        }

        try {
            return Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function formatDateTime($date): string
    {
        if (! $date) {
            return '-';
        }

        try {
            return Carbon::parse($date)->timezone(self::APP_TIMEZONE)->format('d-m-Y H:i:s');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function inboundTypeLabel(?string $type): string
    {
        return self::INBOUND_TYPE_LABELS[$type] ?? ($type ?: '-');
    }

    private function safeText($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+@]|^-(?=\d)/', $value) ? "'{$value}" : $value;
    }

    private function headerStyle(string $color): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }

    private function borderStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D8DEE9'],
                ],
            ],
        ];
    }
}
