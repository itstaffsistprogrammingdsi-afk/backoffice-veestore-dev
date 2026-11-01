<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StokPercabangExport implements WithMultipleSheets
{
    protected Collection $rows;
    protected array $filters;

    public function __construct(Collection $rows, array $filters = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];

        $grouped = $this->rows->groupBy('store_id');

        foreach ($grouped as $storeId => $storeRows) {
            $storeName = $storeRows->first()->store_name ?? 'Cabang';
            $storeCode = $storeRows->first()->store_code ?? '';

            $sheetFilters = array_merge($this->filters, [
                'store_id'   => $storeId,
                'store_name' => $storeName,
                'store_code' => $storeCode,
            ]);

            $sheets[] = new StokPercabangStoreSheet($storeRows, $sheetFilters);
        }

        if (empty($sheets)) {
            $sheets[] = new StokPercabangStoreSheet(collect(), array_merge($this->filters, [
                'store_name' => 'Tidak Ada Data',
                'store_code' => '',
            ]));
        }

        return $sheets;
    }
}