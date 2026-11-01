<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierShiftReportDetailTransactionSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(protected Collection $transactions)
    {
    }

    public function headings(): array
    {
        return [
            'No',
            'Transaction Number',
            'Customer',
            'Status',
            'Source',
            'Total Amount',
            'Tax',
            'Paid',
            'Change Amount',
            'Completed At',
            'Catatan',
        ];
    }

    public function collection()
    {
        return $this->transactions->values()->map(function ($trx, $index) {
            return [
                'no'                 => $index + 1,
                'transaction_number' => $trx->transaction_number ?: '-',
                'customer'           => $trx->customer_name ?: 'Guest / Non Member',
                'status'             => $trx->payment_status ?: '-',
                'source'             => $trx->source ?: '-',
                'total_amount'       => (float) ($trx->total_amount ?? 0),
                'tax'                => (float) ($trx->tax_amount ?? 0),
                'paid'               => (float) ($trx->paid_amount ?? 0),
                'change_amount'      => (float) ($trx->change_amount ?? 0),
                'completed_at'       => $trx->completed_at ? Carbon::parse($trx->completed_at)->format('d-m-Y H:i:s') : '-',
                'catatan'            => $trx->payment_note ?: ($trx->store_payment_note ?: '-'),
            ];
        });
    }

    public function title(): string
    {
        return 'Detail Transaksi';
    }
}