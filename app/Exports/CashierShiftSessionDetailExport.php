<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierShiftSessionDetailExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
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
            'Kasir' => $row->cashier_name,
            'Status' => $row->cashier_status,
            'Shift Name' => $row->shift_name,
            'Kode Shift' => $row->shift_code,
            'Session' => $row->session_number,
            'Open' => $row->opened_at,
            'Close' => $row->closed_at,
            'Trx' => $row->total_transactions,
            'Omzet' => $row->omzet,
            'Kembalian' => $row->total_change_amount,
            'Open Cash' => $row->opening_cash,
            'System Cash' => $row->closing_cash_system,
            'Physical Cash' => $row->closing_cash_physical,
            'Selisih' => $row->cash_difference,
        ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Store', 'Kasir', 'Status', 'Shift Name', 'Kode Shift', 'Session', 'Open', 'Close', 'Trx', 'Omzet', 'Kembalian', 'Open Cash', 'System Cash', 'Physical Cash', 'Selisih'];
    }

    public function title(): string
    {
        return 'Detail Cashier Shift';
    }
}
