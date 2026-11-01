<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TrackingBarangSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Summary Produk';
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            return [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'total_items' => (int) ($row->total_items ?? 0),
                'inbound_warehouse_items' => (int) ($row->inbound_warehouse_items ?? 0),
                'in_warehouse_items' => (int) ($row->in_warehouse_items ?? 0),
                'stock_warehouse_items' => (int) ($row->stock_warehouse_items ?? 0),
                'outbound_warehouse_items' => (int) ($row->outbound_warehouse_items ?? 0),
                'inbound_store_items' => (int) ($row->inbound_store_items ?? 0),
                'stock_store_items' => (int) ($row->stock_store_items ?? 0),
                'display_store_items' => (int) ($row->display_store_items ?? 0),
                'sold_items' => (int) ($row->sold_items ?? 0),
                'last_item_update' => $row->last_item_update,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Total Items',
            'Inbound Warehouse',
            'In Warehouse',
            'Stock Warehouse',
            'Outbound Warehouse',
            'Inbound Store',
            'Stock Store',
            'Display Store',
            'Sold',
            'Last Item Update',
        ];
    }
}