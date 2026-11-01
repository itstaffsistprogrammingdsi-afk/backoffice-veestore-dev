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

class WarehouseOutboundExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const APP_TIMEZONE = 'Asia/Jakarta';

    private const OUTBOUND_TYPE_LABELS = [
        'ecommerce' => 'Ecommerce',
        'destroy' => 'Destroy',
        'transfer' => 'Transfer',
        'return_to_supplier' => 'Retur ke Supplier',
        'store' => 'Store',
    ];

    public function __construct(
        protected Collection $rows,
        protected Collection $detailMap,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Warehouse Outbound';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN WAREHOUSE OUTBOUND'],
            [''],
            ['Tanggal Export', ': '.now(self::APP_TIMEZONE)->format('d-m-Y H:i:s')],
            ['Periode', ': '.$this->formatPeriod()],
            ['Warehouse', ': '.($this->filters['warehouse_name'] ?? 'Semua Warehouse')],
            ['Kurir', ': '.($this->filters['courier_name'] ?? 'Semua Kurir')],
            ['Tipe Outbound', ': '.($this->filters['outbound_type'] ?? 'Semua Tipe')],
            ['Status Release', ': '.($this->filters['release_status'] ?? 'Semua Status')],
            ['Status QC', ': '.($this->filters['qc_status'] ?? 'Semua Status QC')],
            ['Keyword', ': '.(($this->filters['q'] ?? null) ?: '-')],
            [''],
            ['RINGKASAN'],
            ['Total Outbound', 'Total SKU', 'Total Item', 'Sudah Release', 'QC Lolos', 'Tanpa Resi'],
            [
                $this->rows->count(),
                (int) $this->rows->sum('total_sku'),
                (int) $this->rows->sum('total_items'),
                $this->rows->filter(fn ($row) => $row->released_at)->count(),
                $this->rows->filter(fn ($row) => $row->qc_passed_at)->count(),
                $this->rows->filter(fn ($row) => trim((string) ($row->shipping_number ?? '')) === '')->count(),
            ],
            [''],
            [
                'No',
                'Tanggal Aktivitas',
                'Warehouse',
                'Kode Warehouse',
                'Tipe Outbound',
                'Invoice',
                'Delivery Order',
                'Order Number',
                'Order Ref',
                'Kurir',
                'No. Resi',
                'Qty Header',
                'Total SKU',
                'Total Item',
                'Released At',
                'Status Release',
                'QC Passed At',
                'Status QC',
                'PIC Outbound',
                'PIC QC',
                'Kode Produk',
                'Nama Produk',
                'Qty Produk',
                'Good',
                'Bad',
                'Other',
                'Ringkasan Kondisi',
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

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = 'AA';
        $headerRow = 16;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A12:{$lastColumn}12");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A3:A10')->getFont()->setBold(true);
        $sheet->getStyle("A12:{$lastColumn}12")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);
        $sheet->getStyle('A13:F13')->applyFromArray($this->headerStyle('115E59'));
        $sheet->getStyle('A13:F14')->applyFromArray($this->borderStyle());
        $sheet->getStyle('A14:F14')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray($this->headerStyle('1E293B'));
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->applyFromArray($this->borderStyle());

        if ($lastRow > $headerRow) {
            $sheet->getStyle('L'.($headerRow + 1).":N{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('W'.($headerRow + 1).":Z{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->freezePane('A17');
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }

    private function mapRow(object $row, ?object $detail, int $number, bool $first): array
    {
        return [
            $first ? $number : '',
            $first ? $this->formatDateTime($row->event_date ?? null) : '',
            $first ? $this->safeText($row->warehouse_name ?? '-') : '',
            $first ? $this->safeText($row->warehouse_code ?? '-') : '',
            $first ? $this->outboundTypeLabel($row->outbound_type ?? null) : '',
            $first ? $this->safeText($row->invoice_number ?? '-') : '',
            $first ? $this->safeText($row->delivery_order_number ?? '-') : '',
            $first ? $this->safeText($row->order_number ?? '-') : '',
            $first ? $this->safeText($row->order_ref ?? '-') : '',
            $first ? $this->safeText($row->courier_name ?? '-') : '',
            $first ? $this->safeText($row->shipping_number ?? '-') : '',
            $first ? (int) ($row->quantity_item ?? 0) : '',
            $first ? (int) ($row->total_sku ?? 0) : '',
            $first ? (int) ($row->total_items ?? 0) : '',
            $first ? $this->formatDateTime($row->released_at ?? null) : '',
            $first ? (($row->released_at ?? null) ? 'Sudah release' : 'Belum release') : '',
            $first ? $this->formatDateTime($row->qc_passed_at ?? null) : '',
            $first ? (($row->qc_passed_at ?? null) ? 'QC lolos' : 'QC belum lolos') : '',
            $first ? $this->safeText($row->outbound_by_name ?? '-') : '',
            $first ? $this->safeText($row->qc_passed_by_name ?? '-') : '',
            $this->safeText($detail->product_code ?? '-'),
            $this->safeText($detail->product_name ?? '-'),
            (int) ($detail->qty ?? 0),
            (int) ($detail->qty_good ?? 0),
            (int) ($detail->qty_bad ?? 0),
            (int) ($detail->qty_other ?? 0),
            $this->safeText($detail->condition_summary ?? '-'),
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

    private function outboundTypeLabel(?string $type): string
    {
        return self::OUTBOUND_TYPE_LABELS[$type] ?? ($type ?: '-');
    }

    private function safeText($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '-';
        }

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }

    private function headerStyle(string $color): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
    }

    private function borderStyle(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
