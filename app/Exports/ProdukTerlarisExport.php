<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProdukTerlarisExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $number = 0;

    public function __construct(
        private readonly Collection $rows,
        private readonly string $storeName,
        private readonly array $filters,
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Peringkat', 'Bulan', 'Cabang', 'Kode Produk', 'Nama Produk', 'Brand', 'Kategori',
            'Jumlah Transaksi', 'Qty Terjual', 'Nominal Penjualan',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->number,
            $row->periode_bulan ?? '-',
            $row->cabang_name ?? $this->storeName,
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->brand_name ?? '-',
            $row->category_name ?? '-',
            (int) ($row->total_transaksi ?? 0),
            (int) ($row->total_qty ?? 0),
            (float) ($row->nominal_penjualan ?? 0),
        ];
    }
}
