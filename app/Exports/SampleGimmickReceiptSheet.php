<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SampleGimmickReceiptSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Gimmick Nota';
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'No Transaksi',
            'Cabang',
            'Kode Cabang',
            'Kode Produk',
            'Produk',
            'Kondisi',
            'Clearance (%)',
            'Qty',
            'Nilai Barang',
            'Diskon Tercatat',
            'Nilai Setelah Diskon',
            'Nilai Gimmick / Gratis',
            'Status Void',
        ];
    }

    public function map($row): array
    {
        return [
            ! empty($row->trx_datetime) ? Carbon::parse($row->trx_datetime)->format('d-m-Y H:i') : '-',
            $row->transaction_number ?? '-',
            $row->store_name ?? '-',
            $row->store_code ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->condition_name ?? 'Tidak Tercatat',
            (float) ($row->clearance_percentage ?? 0),
            (int) ($row->qty ?? 0),
            (float) ($row->gross_value ?? 0),
            (float) ($row->discount_value ?? 0),
            (float) ($row->net_value ?? 0),
            (float) ($row->gimmick_value ?? 0),
            $row->void_status ?? '-',
        ];
    }
}
