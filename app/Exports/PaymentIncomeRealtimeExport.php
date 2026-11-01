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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentIncomeRealtimeExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $rows,
        protected ?object $summary,
        protected Collection $methodSummaries,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Pemasukan Realtime';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN PEMASUKAN REALTIME'],
            [''],
            ['Tanggal Export', ': '.now()->format('d-m-Y H:i:s')],
            ['Periode', ': '.$this->formatPeriod()],
            ['Store', ': '.($this->filters['store_name'] ?? 'Semua Store')],
            ['Metode Pembayaran', ': '.($this->filters['payment_method_name'] ?? 'Semua Metode')],
            ['Status', ': '.$this->statusLabel($this->filters['payment_status'] ?? null)],
            ['Keyword', ': '.(($this->filters['keyword'] ?? null) ?: '-')],
            [''],
            ['RINGKASAN PEMASUKAN'],
            ['Keterangan', 'Nilai'],
            ['Total Transaksi', (int) ($this->summary->total_transactions ?? 0)],
            ['Total Store', (int) ($this->summary->total_stores ?? 0)],
            ['Total Customer', (int) ($this->summary->total_customers ?? 0)],
            ['Total Pemasukan', (float) ($this->summary->total_amount ?? 0)],
            ['Total Dibayar', (float) ($this->summary->total_paid ?? 0)],
            ['Total Kembalian', (float) ($this->summary->total_change ?? 0)],
            [''],
            ['RINGKASAN METODE PEMBAYARAN'],
            ['No', 'Metode Pembayaran', 'Jumlah Transaksi', 'Total Pemasukan', 'Pertama Masuk', 'Terakhir Masuk'],
        ];

        if ($this->methodSummaries->isEmpty()) {
            $data[] = ['', 'Tidak ada data metode pembayaran', '', '', '', ''];
        } else {
            foreach ($this->methodSummaries->values() as $index => $method) {
                $data[] = [
                    $index + 1,
                    $this->safeText($method->payment_method_name ?? '-'),
                    (int) ($method->total_transactions ?? 0),
                    (float) ($method->total_amount ?? 0),
                    $this->formatDateTime($method->first_used_at ?? null),
                    $this->formatDateTime($method->last_used_at ?? null),
                ];
            }
        }

        $data[] = [''];
        $data[] = [
            'No',
            'Waktu Masuk',
            'Sumber Pemasukan',
            'Store',
            'Kode Store',
            'Customer',
            'Referensi',
            'Transaction No',
            'Metode Pembayaran',
            'Status',
            'Total',
            'Paid',
            'Change',
            'Payment Source',
            'Transaction Ref',
            'Status Asli',
        ];

        foreach ($this->rows->values() as $index => $row) {
            $data[] = [
                $index + 1,
                $this->formatDateTime($row->income_at ?? null),
                $this->safeText($row->income_source_label ?? '-'),
                $this->safeText($row->store_name ?? '-'),
                $this->safeText($row->store_code ?? '-'),
                $this->safeText($row->customer_name ?? 'Guest / Non Member'),
                $this->safeText($row->reference_code ?? '-'),
                $this->safeText($row->transaction_number ?? '-'),
                $this->safeText($row->payment_method_descriptions ?? $row->payment_methods ?? '-'),
                $this->statusLabel($row->payment_status ?? null),
                (float) ($row->total_amount ?? 0),
                (float) ($row->paid_amount ?? 0),
                (float) ($row->change_amount ?? 0),
                $this->safeText($row->payment_source ?? '-'),
                $this->safeText($row->transaction_refs ?? '-'),
                $this->safeText($row->raw_payment_status ?? '-'),
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'P';
        $detailHeaderRow = $this->findRow($sheet, 'A', 'No', 'P', 'Status Asli');

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A10:{$lastColumn}10");
        $sheet->mergeCells("A19:{$lastColumn}19");

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (['A10:P10', 'A19:P19'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '5B5CF6'],
                ],
            ]);
        }

        $sheet->getStyle('A11:B17')->applyFromArray($this->tableBorderStyle());
        $sheet->getStyle('A11:B11')->applyFromArray($this->headerStyle('4338CA'));
        $sheet->getStyle('B12:B17')->getNumberFormat()->setFormatCode('#,##0');

        $methodLastRow = $detailHeaderRow ? $detailHeaderRow - 2 : 20;
        $sheet->getStyle("A20:F{$methodLastRow}")->applyFromArray($this->tableBorderStyle());
        $sheet->getStyle('A20:F20')->applyFromArray($this->headerStyle('4338CA'));
        $sheet->getStyle("C21:D{$methodLastRow}")->getNumberFormat()->setFormatCode('#,##0');

        if ($detailHeaderRow) {
            $sheet->getStyle("A{$detailHeaderRow}:{$lastColumn}{$detailHeaderRow}")
                ->applyFromArray($this->headerStyle('1E293B'));
            $sheet->getStyle("A{$detailHeaderRow}:{$lastColumn}{$lastRow}")
                ->applyFromArray($this->tableBorderStyle());
            $sheet->getStyle('K'.($detailHeaderRow + 1).":M{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->freezePane('A'.($detailHeaderRow + 1));
            $sheet->setAutoFilter("A{$detailHeaderRow}:{$lastColumn}{$lastRow}");
        }

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }

    private function formatPeriod(): string
    {
        $startDate = $this->formatDate($this->filters['start_date'] ?? null);
        $endDate = $this->formatDate($this->filters['end_date'] ?? null);

        return $startDate === '-' || $endDate === '-'
            ? '-'
            : "{$startDate} s/d {$endDate}";
    }

    private function formatDate(?string $date): string
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
            return Carbon::parse($date)->format('d-m-Y H:i:s');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            null, '' => 'Semua Status',
            default => ucfirst($status),
        };
    }

    private function safeText($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+@]|^-(?=\d)/', $value) ? "'{$value}" : $value;
    }

    private function findRow(Worksheet $sheet, string $firstColumn, string $firstValue, string $lastColumn, string $lastValue): ?int
    {
        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            if (
                $sheet->getCell("{$firstColumn}{$row}")->getValue() === $firstValue
                && $sheet->getCell("{$lastColumn}{$row}")->getValue() === $lastValue
            ) {
                return $row;
            }
        }

        return null;
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

    private function tableBorderStyle(): array
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
