<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrackingBarangExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $summaryRows,
        protected Collection $realtimeRows,
        protected Collection $dailyRows,
        protected Collection $monthlyRows,
        protected array $filters = []
    ) {}

    public function sheets(): array
    {
        return [
            new TrackingBarangSummarySheet($this->summaryRows, $this->filters),
            new TrackingBarangRealtimeSheet($this->realtimeRows, $this->filters),
            new TrackingBarangDailySheet($this->dailyRows, $this->filters),
            new TrackingBarangMonthlySheet($this->monthlyRows, $this->filters),
        ];
    }
}