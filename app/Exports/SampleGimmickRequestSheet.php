<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SampleGimmickRequestSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Pengajuan';
    }

    public function headings(): array
    {
        return [
            'No Pengajuan',
            'Cabang',
            'Jenis',
            'Status',
            'Diajukan Oleh',
            'Diajukan Pada',
            'Direview Oleh',
            'Direview Pada',
            'Jumlah Item',
            'Nilai Pengajuan',
            'Catatan Review',
            'Alasan Ditolak',
        ];
    }

    public function map($row): array
    {
        return [
            $row['request_number'] ?? '-',
            $row['store_name'] ?? '-',
            $row['type_label'] ?? '-',
            $row['status_label'] ?? '-',
            $row['created_by_name'] ?? '-',
            $row['submitted_at'] ?? '-',
            $row['reviewed_by_name'] ?? '-',
            $row['reviewed_at'] ?? '-',
            (int) ($row['item_count'] ?? 0),
            (float) ($row['total_value'] ?? 0),
            $row['review_note'] ?? '-',
            $row['rejection_reason'] ?? '-',
        ];
    }
}
