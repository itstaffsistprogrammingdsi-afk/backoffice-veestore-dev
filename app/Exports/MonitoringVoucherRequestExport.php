<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringVoucherRequestExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No. Request', 'Tanggal', 'Store', 'Status', 'Requester', 'Approver', 'Voucher', 'Kode Voucher', 'Tipe Voucher', 'Nilai Voucher', 'Jumlah Item', 'Usage', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->request_number ?? '-',
            $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d-m-Y') : '-',
            $row->store_name ?? '-',
            $row->status_label ?? $row->status ?? '-',
            $row->requested_by_name ?? '-',
            $row->approved_by_name ?? '-',
            $row->voucher_name ?? '-',
            $row->voucher_code ?? '-',
            $row->voucher_type ?? '-',
            (float) ($row->voucher_value ?? 0),
            (int) ($row->total_items ?? 0),
            (int) ($row->usage_count ?? 0),
            $row->notes ?? '-',
        ];
    }
}
