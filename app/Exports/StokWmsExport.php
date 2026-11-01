<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StokWmsExport implements WithMultipleSheets
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

        $grouped = $this->rows->groupBy('warehouse_id');

        foreach ($grouped as $warehouseId => $warehouseRows) {
            $warehouseName = $warehouseRows->first()->warehouse_name ?? 'Warehouse';
            $warehouseCode = $warehouseRows->first()->warehouse_code ?? '';

            $sheetFilters = array_merge($this->filters, [
                'warehouse_id'   => $warehouseId,
                'warehouse_name' => $warehouseName,
                'warehouse_code' => $warehouseCode,
            ]);

            $sheets[] = new StokWmsWarehouseSheet($warehouseRows, $sheetFilters);
        }

        // Kalau data kosong, tetap buat 1 sheet kosong
        if (empty($sheets)) {
            $sheets[] = new StokWmsWarehouseSheet(collect(), array_merge($this->filters, [
                'warehouse_name' => 'Tidak Ada Data',
                'warehouse_code' => '',
            ]));
        }

        return $sheets;
    }
}