<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TrackingBarangRealtimeSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $filters = []
    ) {}

    public function title(): string
    {
        return 'Realtime';
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            return [
                'event_time' => $row->event_time,
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'item_id' => $row->item_id,
                'display_item_id' => $row->display_item_id,
                'rfid_value' => $row->rfid_value,
                'event_code' => $row->event_code,
                'event_name' => $row->event_name,
                'movement_type' => $row->movement_type,
                'warehouse_id' => $row->warehouse_id,
                'store_id' => $row->store_id,
                'reference_info' => $row->reference_info,
                'detail_info' => $row->detail_info,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Event Time',
            'Product ID',
            'Product Name',
            'Item UUID',
            'Display Item ID',
            'RFID',
            'Event Code',
            'Event Name',
            'Movement Type',
            'Warehouse ID',
            'Store ID',
            'Reference',
            'Detail',
        ];
    }
}