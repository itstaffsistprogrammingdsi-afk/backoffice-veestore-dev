<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ShiftPurchaseReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $rows,
        protected Collection $products,
        protected Collection $transactions,
        protected array $summary,
        protected array $filters
    ) {}

    public function sheets(): array
    {
        return [
            new ShiftPurchaseSummarySheet($this->rows, $this->summary, $this->filters),
            new ShiftPurchaseProductSheet($this->products),
            new ShiftPurchaseTransactionSheet($this->transactions),
        ];
    }
}

class ShiftPurchaseSummarySheet implements FromCollection, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected Collection $rows,
        protected array $summary,
        protected array $filters
    ) {}

    public function title(): string
    {
        return 'Ringkasan Shift';
    }

    public function collection(): Collection
    {
        $data = [
            ['Laporan Pembelian per Shift', ''],
            ['Periode', ($this->filters['from'] ?? '-').' s/d '.($this->filters['to'] ?? '-')],
            ['Total Shift', (int) ($this->summary['total_shifts'] ?? 0)],
            ['Total Sesi', (int) ($this->summary['total_sessions'] ?? 0)],
            ['Total Transaksi', (int) ($this->summary['total_transactions'] ?? 0)],
            ['Total Item', (int) ($this->summary['total_items'] ?? 0)],
            ['Gross', (float) ($this->summary['gross_amount'] ?? 0)],
            ['Diskon', (float) ($this->summary['discount_amount'] ?? 0)],
            ['Net', (float) ($this->summary['net_amount'] ?? 0)],
            [],
            ['Tanggal', 'Store', 'Shift', 'Kode Shift', 'Kasir', 'Sesi', 'Transaksi', 'Item', 'Gross', 'Diskon', 'Net'],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row->period_label ?? '-',
                $row->store_name ?? '-',
                $row->shift_name ?? '-',
                $row->shift_code ?? '-',
                (int) ($row->total_cashiers ?? 0),
                (int) ($row->total_sessions ?? 0),
                (int) ($row->total_transactions ?? 0),
                (int) ($row->total_items ?? 0),
                (float) ($row->gross_amount ?? 0),
                (float) ($row->discount_amount ?? 0),
                (float) ($row->net_amount ?? 0),
            ];
        }

        return new Collection($data);
    }
}

class ShiftPurchaseProductSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(protected Collection $products) {}

    public function title(): string
    {
        return 'Produk Dibeli';
    }

    public function headings(): array
    {
        return ['Kode Produk', 'Nama Produk', 'Qty', 'Gross', 'Diskon', 'Net', 'Jumlah Sesi'];
    }

    public function collection(): Collection
    {
        return $this->products->map(fn ($row): array => [
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            (int) ($row->qty ?? 0),
            (float) ($row->gross_amount ?? 0),
            (float) ($row->discount_amount ?? 0),
            (float) ($row->net_amount ?? 0),
            (int) ($row->session_count ?? 0),
        ]);
    }
}

class ShiftPurchaseTransactionSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(protected Collection $transactions) {}

    public function title(): string
    {
        return 'Transaksi';
    }

    public function headings(): array
    {
        return ['Tanggal', 'Store', 'Sesi', 'Nomor Transaksi', 'Kasir', 'Customer', 'Total', 'Dibayar', 'Kembalian', 'Status'];
    }

    public function collection(): Collection
    {
        return $this->transactions->map(fn ($row): array => [
            $row->completed_at ?? '-',
            $row->store_name ?? '-',
            $row->session_number ?? '-',
            $row->transaction_number ?? '-',
            $row->cashier_name ?? '-',
            $row->customer_name ?? 'Guest',
            (float) ($row->total_amount ?? 0),
            (float) ($row->paid_amount ?? 0),
            (float) ($row->change_amount ?? 0),
            $row->status ?? '-',
        ]);
    }
}
