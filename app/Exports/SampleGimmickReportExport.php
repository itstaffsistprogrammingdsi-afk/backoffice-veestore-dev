<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SampleGimmickReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $gimmickReceiptRows,
        private readonly Collection $sampleRows,
        private readonly Collection $gimmickStockRows,
        private readonly Collection $requests,
    ) {}

    public function sheets(): array
    {
        return [
            new SampleGimmickReceiptSheet($this->gimmickReceiptRows),
            new SampleGimmickStockSheet($this->sampleRows, 'Sample'),
            new SampleGimmickStockSheet($this->gimmickStockRows, 'Gimmick'),
            new SampleGimmickRequestSheet($this->requests),
        ];
    }
}
