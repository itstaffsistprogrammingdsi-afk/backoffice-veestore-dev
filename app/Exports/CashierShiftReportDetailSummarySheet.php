<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierShiftReportDetailSummarySheet implements FromCollection, ShouldAutoSize, WithTitle
{
    public function __construct(protected object $session)
    {
    }

    public function collection()
    {
        return new Collection([
            ['Store', $this->session->store_name ?? '-'],
            ['Kasir', $this->session->cashier_name ?? '-'],
            ['Session Number', $this->session->session_number ?? '-'],
            ['Status', $this->session->status ?? '-'],
            ['Opened At', $this->session->opened_at ? Carbon::parse($this->session->opened_at)->format('d-m-Y H:i:s') : '-'],
            ['Closed At', $this->session->closed_at ? Carbon::parse($this->session->closed_at)->format('d-m-Y H:i:s') : '-'],
            ['Opening Cash', (float) ($this->session->opening_cash ?? 0)],
            ['Expected Cash', (float) ($this->session->expected_cash ?? 0)],
            ['Actual Cash', (float) ($this->session->actual_cash ?? 0)],
            ['Difference', (float) ($this->session->difference ?? 0)],
            ['Total Sales', (float) ($this->session->total_sales ?? 0)],
            ['Total Transactions', (int) ($this->session->total_transactions ?? 0)],
            ['Note Open', $this->session->note_open ?? '-'],
            ['Note Close', $this->session->note_close ?? '-'],
        ]);
    }

    public function title(): string
    {
        return 'Ringkasan Session';
    }
}