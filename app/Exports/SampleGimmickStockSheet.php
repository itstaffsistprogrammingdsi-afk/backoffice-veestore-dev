<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SampleGimmickStockSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private readonly Collection $rows,
        private readonly string $sheetTitle,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function headings(): array
    {
        return [
            'Tanggal Ditandai',
            'Jenis',
            'No Pengajuan',
            'Ditandai Oleh',
            'Cabang',
            'Kode Cabang',
            'Kode Produk',
            'Produk',
            'Kondisi',
            'Qty',
            'Nilai Stok',
            'Nilai Harga Dasar',
        ];
    }

    public function map($row): array
    {
        return [
            ! empty($row->marked_at) ? Carbon::parse($row->marked_at)->format('d-m-Y H:i') : '-',
            ucfirst((string) ($row->marker_type ?? $this->sheetTitle)),
            $row->request_number ?? '-',
            $row->marked_by ?? '-',
            $row->store_name ?? '-',
            $row->store_code ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->condition_name ?? 'Tidak Tercatat',
            (int) ($row->qty ?? 0),
            (float) ($row->stock_value ?? 0),
            (float) ($row->base_value ?? 0),
        ];
    }
}
