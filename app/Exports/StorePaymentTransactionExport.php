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

class StorePaymentTransactionExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $rows;
    protected Collection $paymentMethodSummaries;
    protected array $filters;

    public function __construct(
        Collection $rows,
        array $filters = [],
        ?Collection $paymentMethodSummaries = null
    ) {
        $this->rows = $rows;
        $this->filters = $filters;
        $this->paymentMethodSummaries = $paymentMethodSummaries ?? collect();
    }

    public function title(): string
    {
        return 'Store Payment Transactions';
    }

    public function array(): array
    {
        $storeName = $this->filters['store_name'] ?? '-';
        $paymentMethodName = $this->filters['payment_method_name'] ?? '-';
        $keyword = $this->filters['keyword'] ?? '-';

        $rawPaymentStatus = $this->filters['payment_status'] ?? '-';
        $paymentStatusLabel = $this->mapPaymentStatusLabel($rawPaymentStatus);

        $startDate = $this->formatDate($this->filters['start_date'] ?? null);
        $endDate = $this->formatDate($this->filters['end_date'] ?? null);

        $periode = '-';
        if ($startDate !== '-' && $endDate !== '-') {
            $periode = $startDate . ' s/d ' . $endDate;
        }

        $data = [
            ['LAPORAN STORE PAYMENT TRANSACTIONS'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Periode', ': ' . $periode],
            ['Store', ': ' . $storeName],
            ['Metode Pembayaran', ': ' . $paymentMethodName],
            ['Status Payment', ': ' . $paymentStatusLabel],
            ['Keyword', ': ' . $keyword],
            [''],
            ['RINGKASAN METODE PEMBAYARAN'],
            ['No', 'Metode Pembayaran', 'Jumlah Transaksi', 'Total Digunakan'],
        ];

        $summaryNo = 1;
        $summaryGrandTransaction = 0;
        $summaryGrandTotal = 0;

        if ($this->paymentMethodSummaries->count()) {
            foreach ($this->paymentMethodSummaries->values() as $summary) {
                $totalTransactions = (int) ($summary->total_transactions ?? 0);
                $totalAmount = (float) ($summary->total_amount ?? 0);

                $summaryGrandTransaction += $totalTransactions;
                $summaryGrandTotal += $totalAmount;

                $data[] = [
                    $summaryNo++,
                    $summary->payment_method_name ?? '-',
                    $totalTransactions,
                    $totalAmount,
                ];
            }

            $data[] = [
                '',
                'Grand Total',
                $summaryGrandTransaction,
                $summaryGrandTotal,
            ];
        } else {
            $data[] = ['', 'Tidak ada ringkasan metode pembayaran', '', ''];
        }

        $data[] = [''];

        $data[] = [
            'No',
            'Store',
            'Store Code',
            'Visit Code',
            'Customer',
            'Check In',
            'Check Out',
            'Transaction Number',
            'Payment Method',
            'Total',
            'Tax',
            'Paid',
            'Change',
            'Payment Status',
            'Voucher',
            'Voucher Discount',
            'Diskon Digunakan',
            'Produk Dibeli',
            'Jumlah Produk',
            'Payment Source',
            'Completed At',
        ];

        foreach ($this->rows->values() as $i => $row) {
            $productDetails = json_decode($row->product_details ?? '[]', true) ?? [];

            if (empty($productDetails)) {
                $data[] = [
                    $i + 1,
                    $row->store_name ?? '-',
                    $row->store_code ?? '-',
                    $row->visit_code ?? '-',
                    $row->customer_name ?? '-',
                    $row->check_in_at ? Carbon::parse($row->check_in_at)->format('d-m-Y H:i:s') : '-',
                    $row->check_out_at ? Carbon::parse($row->check_out_at)->format('d-m-Y H:i:s') : '-',
                    $row->transaction_number ?? '-',
                    $row->payment_methods ?? '-',
                    (float) ($row->total_amount ?? 0),
                    (float) ($row->tax_amount ?? 0),
                    (float) ($row->paid_amount ?? 0),
                    (float) ($row->change_amount ?? 0),
                    $this->mapPaymentStatusLabel($row->payment_status ?? '-'),
                    $row->voucher_name ?? '-',
                    (float) ($row->voucher_discount ?? 0),
                    $row->used_discounts ?? '-',
                    '-',
                    (int) ($row->total_products ?? 0),
                    $row->payment_source ?? '-',
                    $row->completed_at ? Carbon::parse($row->completed_at)->format('d-m-Y H:i:s') : '-',
                ];

                continue;
            }

            $first = true;
            foreach ($productDetails as $product) {
                $data[] = [
                    $first ? $i + 1 : '',
                    $first ? ($row->store_name ?? '-') : '',
                    $first ? ($row->store_code ?? '-') : '',
                    $first ? ($row->visit_code ?? '-') : '',
                    $first ? ($row->customer_name ?? '-') : '',
                    $first ? ($row->check_in_at ? Carbon::parse($row->check_in_at)->format('d-m-Y H:i:s') : '-') : '',
                    $first ? ($row->check_out_at ? Carbon::parse($row->check_out_at)->format('d-m-Y H:i:s') : '-') : '',
                    $first ? ($row->transaction_number ?? '-') : '',
                    $first ? ($row->payment_methods ?? '-') : '',
                    $first ? (float) ($row->total_amount ?? 0) : '',
                    $first ? (float) ($row->tax_amount ?? 0) : '',
                    $first ? (float) ($row->paid_amount ?? 0) : '',
                    $first ? (float) ($row->change_amount ?? 0) : '',
                    $first ? $this->mapPaymentStatusLabel($row->payment_status ?? '-') : '',
                    $first ? ($row->voucher_name ?? '-') : '',
                    $first ? (float) ($row->voucher_discount ?? 0) : '',
                    $first ? ($row->used_discounts ?? '-') : '',
                    $product['product_name'] ?? '-',
                    $first ? (int) ($row->total_products ?? 0) : '',
                    $first ? ($row->payment_source ?? '-') : '',
                    $first ? ($row->completed_at ? Carbon::parse($row->completed_at)->format('d-m-Y H:i:s') : '-') : '',
                ];

                $first = false;
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'U';

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

        $sheet->mergeCells('A10:D10');
        $sheet->getStyle('A10:D10')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A11:D11')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B9BD5'],
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

        $detailHeaderRow = null;
        for ($row = 1; $row <= $lastRow; $row++) {
            if (
                $sheet->getCell("A{$row}")->getValue() === 'No' &&
                $sheet->getCell("B{$row}")->getValue() === 'Store' &&
                $sheet->getCell("U{$row}")->getValue() === 'Completed At'
            ) {
                $detailHeaderRow = $row;
                break;
            }
        }

        if ($detailHeaderRow) {
            $sheet->getStyle("A{$detailHeaderRow}:{$lastColumn}{$detailHeaderRow}")->applyFromArray([
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

            $sheet->getStyle("A{$detailHeaderRow}:{$lastColumn}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'BFBFBF'],
                    ],
                ],
            ]);

            $sheet->getStyle("A" . ($detailHeaderRow + 1) . ":A{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("J" . ($detailHeaderRow + 1) . ":P{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->freezePane("A" . ($detailHeaderRow + 1));
        }

        $summaryLastRow = $detailHeaderRow ? $detailHeaderRow - 2 : 12;
        $sheet->getStyle("A11:D{$summaryLastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("C12:D{$summaryLastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

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

    private function mapPaymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            null, '', '-' => '-',
            default => (string) $status,
        };
    }
}