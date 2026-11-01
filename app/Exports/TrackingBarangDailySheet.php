<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TrackingBarangDailySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Harian';
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            return [
                'date' => $row->period_date,
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'item_id' => $row->item_id,
                'display_item_id' => $row->display_item_id,
                'rfid_value' => $row->rfid_value,
                'in_count' => (int) ($row->in_count ?? 0),
                'out_count' => (int) ($row->out_count ?? 0),
                'balance_count' => (int) ($row->balance_count ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Product ID',
            'Product Name',
            'Item UUID',
            'Display Item ID',
            'RFID',
            'Barang Masuk',
            'Barang Keluar',
            'Saldo',
        ];
    }
}