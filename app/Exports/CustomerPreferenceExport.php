<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerPreferenceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $filters,
        private readonly string $dimensionLabel,
        private readonly string $metricLabel,
        private readonly string $channelLabel,
        private readonly string $storeName
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Channel',
            'Store',
            'Dimensi Demografi',
            'Segmen',
            'Kode Produk',
            'Nama Produk',
            'Kategori',
            'Brand',
            'Quantity',
            'Omzet',
            'Customer Unik',
            'Transaksi',
            'Metrik Terpilih',
            'Nilai Metrik',
        ];
    }

    public function map($row): array
    {
        $row = is_array($row) ? $row : (array) $row;

        return [
            $this->filters['date_from'].' s/d '.$this->filters['date_to'],
            $this->channelLabel,
            $this->storeName,
            $this->dimensionLabel,
            $row['segment_label'] ?? '-',
            $row['product_code'] ?? '-',
            $row['product_name'] ?? '-',
            $row['category_name'] ?? '-',
            $row['brand_name'] ?? '-',
            (int) ($row['qty'] ?? 0),
            (float) ($row['revenue'] ?? 0),
            (int) ($row['buyers'] ?? 0),
            (int) ($row['transactions'] ?? 0),
            $this->metricLabel,
            (float) ($row['metric_value'] ?? 0),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D35D3']],
            ],
        ];
    }
}
