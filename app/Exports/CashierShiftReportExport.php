<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CashierShiftReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $dailyShiftRows,
        protected Collection $detailRows
    ) {
    }

    public function sheets(): array
    {
        return [
            new CashierShiftPerShiftExport($this->dailyShiftRows),
            new CashierShiftSessionDetailExport($this->detailRows),
        ];
    }
}
