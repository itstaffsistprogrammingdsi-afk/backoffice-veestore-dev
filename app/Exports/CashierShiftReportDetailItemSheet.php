<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierShiftReportDetailItemSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected Collection $transactions,
        protected Collection $items
    ) {
    }

    public function headings(): array
    {
        return [
            'No',
            'Transaction Number',
            'Customer',
            'Kode Produk',
            'Nama Produk',
            'Qty',
            'Harga',
            'Diskon',
            'Subtotal',
        ];
    }

    public function collection()
    {
        $transactionMap = $this->transactions->keyBy('payment_id');
        $no = 0;

        return $this->items->values()->map(function ($item) use ($transactionMap, &$no) {
            $trx = $transactionMap->get($item->payment_id);

            return [
                'no'                 => ++$no,
                'transaction_number' => $trx->transaction_number ?? '-',
                'customer'           => $trx->customer_name ?: 'Guest / Non Member',
                'product_code'       => $item->product_code ?? '-',
                'product_name'       => $item->product_name ?? '-',
                'qty'                => (float) ($item->qty ?? 0),
                'price'              => (float) ($item->price ?? 0),
                'discount'           => (float) ($item->discount_amount ?? 0),
                'subtotal'           => (float) ($item->subtotal ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Detail Pembelian';
    }
}