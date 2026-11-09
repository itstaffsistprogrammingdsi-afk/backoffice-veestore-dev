<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class EdcBcaReportExport implements WithMultipleSheets
{
    public function __construct(
        protected array $rows,
        protected array $summary,
        protected array $byMethod,
        protected array $mdr
    ) {}

    public function sheets(): array
    {
        return [
            new EdcBcaSummarySheet($this->summary, $this->byMethod, $this->mdr),
            new EdcBcaTransactionSheet(collect($this->rows)),
        ];
    }
}

class EdcBcaSummarySheet implements FromCollection, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected array $summary,
        protected array $byMethod,
        protected array $mdr
    ) {}

    public function title(): string
    {
        return 'Ringkasan EDC BCA';
    }

    public function collection(): Collection
    {
        $rows = [
            ['Ringkasan EDC BCA', ''],
            ['Total Transaksi', (int) ($this->summary['total_transactions'] ?? 0)],
            ['Gross Amount', (float) ($this->summary['gross_amount'] ?? 0)],
            ['Estimasi MDR', (float) ($this->summary['mdr_amount'] ?? 0)],
            ['Estimasi Settlement', (float) ($this->summary['net_amount'] ?? 0)],
            ['MDR Kredit (%)', (float) ($this->mdr['credit_percent'] ?? 0)],
            ['MDR Debit (%)', (float) ($this->mdr['debit_percent'] ?? 0)],
            ['Settlement (hari)', (int) ($this->mdr['settlement_days'] ?? 0)],
            [],
            ['Per Metode', 'Transaksi', 'Gross', 'MDR', 'Settlement'],
        ];

        foreach ($this->byMethod as $row) {
            $rows[] = [
                $row['payment_method_name'] ?? '-',
                (int) ($row['total_transactions'] ?? 0),
                (float) ($row['gross_amount'] ?? 0),
                (float) ($row['mdr_amount'] ?? 0),
                (float) ($row['net_amount'] ?? 0),
            ];
        }

        return new Collection($rows);
    }
}

class EdcBcaTransactionSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(protected Collection $rows) {}

    public function title(): string
    {
        return 'Transaksi EDC BCA';
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nomor Transaksi',
            'Toko',
            'Kanal',
            'Metode',
            'Status',
            'Terminal',
            'Approval Code',
            'Batch',
            'Kartu',
            'Acquirer',
            'Referensi',
            'Amount',
            'MDR',
            'Settlement',
        ];
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row): array {
            $metadata = $row['metadata'] ?? [];

            return [
                $row['event_at'] ?? null,
                $row['transaction_number'] ?? '-',
                $row['store_name'] ?? '-',
                $row['channel_label'] ?? '-',
                $row['payment_method_name'] ?? '-',
                $row['detail_status'] ?? '-',
                $metadata['terminal_id'] ?? '-',
                $metadata['approval_code'] ?? '-',
                $metadata['batch_number'] ?? '-',
                $metadata['card_number'] ?? '-',
                $metadata['acquirer'] ?? '-',
                $metadata['reference_number'] ?? '-',
                (float) ($row['amount'] ?? 0),
                (float) ($row['mdr_amount'] ?? 0),
                (float) ($row['net_amount'] ?? 0),
            ];
        });
    }
}
