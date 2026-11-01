<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierShiftPerShiftExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected Collection $rows
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            'Tanggal' => $row->period_label,
            'Store' => $row->store_name,
            'Shift Name' => $row->shift_name,
            'Kode Shift' => $row->shift_code,
            'Mulai Shift' => $row->shift_start_time,
            'Jumlah Kasir' => $row->total_cashiers,
            'Jumlah Sesi' => $row->total_sessions,
            'Transaksi' => $row->total_transactions,
            'Omzet' => $row->total_omzet,
            'Kembalian' => $row->total_change_amount,
            'Selisih' => $row->total_cash_difference,
        ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Store', 'Shift Name', 'Kode Shift', 'Mulai Shift', 'Jumlah Kasir', 'Jumlah Sesi', 'Transaksi', 'Omzet', 'Kembalian', 'Selisih'];
    }

    public function title(): string
    {
        return 'Omzet per Shift';
    }
}
