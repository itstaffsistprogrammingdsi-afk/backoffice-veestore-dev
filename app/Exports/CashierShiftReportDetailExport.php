<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CashierShiftReportDetailExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected object $session,
        protected Collection $transactions,
        protected Collection $items
    ) {
    }

    public function sheets(): array
    {
        return [
            new CashierShiftReportDetailSummarySheet($this->session),
            new CashierShiftReportDetailTransactionSheet($this->transactions),
            new CashierShiftReportDetailItemSheet($this->transactions, $this->items),
        ];
    }
}