<?php

namespace App\Exports\Monitoring;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductAgingExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows
    ) {}

    public function collection()
    {
        return $this->rows->map(function ($row) {
            return [
                'Scope'              => ucfirst((string) $row->scope_type),
                'Warehouse'          => $row->warehouse_name ?: '-',
                'Store'              => $row->store_name ?: '-',
                'Product Code'       => $row->product_code,
                'Product Name'       => $row->product_name,
                'Condition'          => $row->condition_status,
                'Expired Date'       => $row->expired_date ? date('Y-m-d', strtotime($row->expired_date)) : '-',
                'Warehouse Inbound'  => $row->warehouse_inbound_date ? date('Y-m-d H:i:s', strtotime($row->warehouse_inbound_date)) : '-',
                'Store Inbound'      => $row->store_inbound_date ? date('Y-m-d H:i:s', strtotime($row->store_inbound_date)) : '-',
                'Warehouse Aging'    => $row->warehouse_aging_label,
                'Store Aging'        => $row->store_aging_label,
                'Total Aging'        => $row->total_aging_label,
                'Total Aging Days'   => $row->total_aging_days,
                'Quantity'           => $row->quantity,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Scope',
            'Warehouse',
            'Store',
            'Product Code',
            'Product Name',
            'Condition',
            'Expired Date',
            'Warehouse Inbound',
            'Store Inbound',
            'Warehouse Aging',
            'Store Aging',
            'Total Aging',
            'Total Aging Days',
            'Quantity',
        ];
    }
}