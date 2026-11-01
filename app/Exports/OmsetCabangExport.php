<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OmsetCabangExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Cabang / Kanal',
            'Sumber Penjualan',
            'Store Pengambilan Ecommerce',
            'Warehouse Pengambilan Ecommerce',
            'Customer',
            'Kode Produk',
            'Produk',
            'No Order',
            'Qty Terjual',
            'Keluar Store untuk Ecommerce',
            'Keluar Warehouse untuk Ecommerce',
            'Harga Awal',
            'Diskon Produk',
            'Voucher / Promo',
            'Harga Akhir',
            'Omset Store POS',
            'Omset Ecommerce',
        ];
    }

    public function map($row): array
    {
        return [
            $row->store_name ?? '-',
            $row->order_source ?? '-',
            $row->fulfillment_store_names ?? '-',
            $row->fulfillment_warehouse_names ?? '-',
            $row->customer_name ?: '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->order_number ?: '-',

            (int) ($row->total_barang_diterima ?? 0),
            (int) ($row->total_barang_keluar_store_ecommerce ?? 0),
            (int) ($row->total_barang_keluar_warehouse_ecommerce ?? 0),
            (float) ($row->total_harga_awal ?? 0),
            (float) ($row->total_diskon_produk ?? 0),
            (float) ($row->total_voucher_promo ?? 0),
            (float) ($row->total_harga_akhir ?? 0),
            (float) ($row->total_omset_kotor_store ?? 0),
            (float) ($row->total_omset_kotor_ecommerce ?? 0),
        ];
    }
}
