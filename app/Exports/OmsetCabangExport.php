<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OmsetCabangExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Kode Cabang',
            'Total Order',
            'Total Produk',
            'Qty Diambil',
            'Qty Keluar',
            'Qty Diterima',
            'Sisa Belum Keluar',
            'Omset Kotor',
            'Diskon Produk',
            'Voucher / Poin',
            'Omset Bersih',
            'Omset Bersih Store POS',
            'Omset Bersih Ecommerce',
            'Nilai Harga Dasar (Base)',
            'Laba Kotor',
            'Margin Kotor (%)',
            'Qty Good',
            'Qty Good After Repair',
            'Qty Clearance',
            'Omset Kotor Good',
            'Omset Kotor Good After Repair',
            'Diskon Clearance',
            'Clearance (%)',
        ];
    }

    public function map($row): array
    {
        $netSales = (float) ($row->total_omset_bersih ?? 0);
        $basePrice = (float) ($row->total_harga_base ?? 0);
        $grossProfit = $netSales - $basePrice;

        return [
            $row->store_name ?? '-',
            $row->store_code ?? '-',
            (int) ($row->total_order ?? 0),
            (int) ($row->total_produk ?? 0),
            (int) ($row->total_barang_diambil ?? 0),
            (int) ($row->total_barang_keluar ?? 0),
            (int) ($row->total_barang_diterima ?? 0),
            (int) ($row->total_sisa_belum_keluar ?? 0),
            (float) ($row->total_omset_kotor ?? 0),
            (float) ($row->total_diskon_produk ?? 0),
            (float) ($row->total_voucher_promo ?? 0),
            $netSales,
            (float) ($row->total_omset_kotor_store ?? 0),
            (float) ($row->total_omset_kotor_ecommerce ?? 0),
            $basePrice,
            $grossProfit,
            $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0,
            (int) ($row->total_qty_good ?? 0),
            (int) ($row->total_qty_good_after_repair ?? 0),
            (int) ($row->total_qty_clearance ?? 0),
            (float) ($row->total_omset_good ?? 0),
            (float) ($row->total_omset_good_after_repair ?? 0),
            (float) ($row->total_clearance_discount ?? 0),
            (float) ($row->clearance_percentage ?? 0),
        ];
    }
}
