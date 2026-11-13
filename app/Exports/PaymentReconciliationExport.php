<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PaymentReconciliationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected Collection $rows) {}

    public function title(): string
    {
        return 'Rekonsiliasi Pembayaran';
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal Order',
            'No. Pesanan',
            'Status Pesanan',
            'Grand Total',
            'Status Pembayaran',
            'No. Transaksi',
            'Nominal Dibayar (legacy)',
            'Total Pembayaran',
            'Pembayaran Detail Sukses',
            'Jumlah Detail',
            'Status Rekonsiliasi',
            'Customer',
        ];
    }

    public function map($row): array
    {
        return [
            ! empty($row->created_at) ? Carbon::parse($row->created_at)->format('d-m-Y H:i') : '-',
            $row->order_number ?? '-',
            $row->order_status ?? '-',
            (float) ($row->grand_total ?? 0),
            $row->payment_status ?? '-',
            $row->transaction_number ?? '-',
            (float) ($row->paid_amount_legacy ?? 0),
            (float) ($row->total_amount ?? 0),
            (float) ($row->detail_success_amount ?? 0),
            (int) ($row->detail_count ?? 0),
            $row->reconciliation_status ?? '-',
            $row->customer_name ?? '-',
        ];
    }
}
