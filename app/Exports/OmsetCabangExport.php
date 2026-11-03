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
            'Nilai Harga Dasar',
            'Omset Store POS',
            'Omset Ecommerce',
            'Total Omset',
        ];
    }

    public function map($row): array
    {
        return [
            $row->store_name ?? '-',
            $row->store_code ?? '-',
            (int) ($row->total_order ?? 0),
            (int) ($row->total_produk ?? 0),
            (int) ($row->total_barang_diambil ?? 0),
            (int) ($row->total_barang_keluar ?? 0),
            (int) ($row->total_barang_diterima ?? 0),
            (int) ($row->total_sisa_belum_keluar ?? 0),
            (float) ($row->total_harga_base ?? 0),
            (float) ($row->total_omset_kotor_store ?? 0),
            (float) ($row->total_omset_kotor_ecommerce ?? 0),
            (float) ($row->total_omset ?? 0),
        ];
    }
}
