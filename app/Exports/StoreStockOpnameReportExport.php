<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreStockOpnameReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'Waktu Laporan' => $row->report_at
                    ? Carbon::parse($row->report_at)->format('d-m-Y H:i')
                    : '-',
                'Store' => $row->store_name,
                'Kode Opname' => $row->opname_code,
                'Disetujui Oleh' => $row->approved_by_name,
                'Kode Produk' => $row->product_code,
                'Nama Produk' => $row->product_name,
                'Qty System' => $row->system_stock,
                'Qty Scan' => $row->physical_stock,
                'Selisih' => $row->difference,
                'Status Summary' => $row->status_summary,
                'Alasan Selisih' => $row->difference_reason ?: '-',
                'Catatan Tambahan' => $row->additional_note ?: '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Waktu Laporan',
            'Store',
            'Kode Opname',
            'Disetujui Oleh',
            'Kode Produk',
            'Nama Produk',
            'Qty System',
            'Qty Scan',
            'Selisih',
            'Status Summary',
            'Alasan Selisih',
            'Catatan Tambahan',
        ];
    }
}
