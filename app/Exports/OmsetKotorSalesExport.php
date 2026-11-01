<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OmsetKotorSalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Channel',
            'No Transaksi',
            'Customer',
            'Kode Produk',
            'Produk',
           
            'RFID',
            'Qty',
            'Harga Jual',
            'Omset Kotor',
            'Harga Base',
            'Harga Base Total',
            'Nama Diskon',
            'Diskon Produk',
            'Nama Voucher',
            'Voucher / Point / Order Discount',
            'Total Potongan',
            'Harga Akhir / Final Paid',
        ];
    }

    public function map($row): array
    {
        $productDiscount = (float) ($row->product_discount_amount ?? 0);
        $voucherDiscount = (float) ($row->allocated_order_discount ?? 0);
        $totalDiscount = (float) ($row->total_discount_amount ?? ($productDiscount + $voucherDiscount));

        return [
            !empty($row->trx_datetime)
                ? Carbon::parse($row->trx_datetime)->format('d-m-Y H:i')
                : '-',

            ucfirst((string) ($row->channel ?? '-')),
            $row->transaction_number ?? '-',
            $row->customer_name ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->display_item_id ?? '-',
            $row->rfid_value ?? '-',

            (int) ($row->qty ?? 0),
            (float) ($row->selling_price ?? 0),
            (float) ($row->gross_sales ?? 0),

            (float) ($row->base_price_amount ?? 0),
            (float) ($row->base_price_total ?? 0),

            $row->discount_names ?: '-',
            $productDiscount,

            $row->voucher_names ?: '-',
            $voucherDiscount,

            $totalDiscount,
            (float) ($row->final_paid_amount ?? 0),
        ];
    }
}