<?php

namespace App\Exports;

use Generator;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SupplierProductExport extends DefaultValueBinder implements FromGenerator, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function __construct(private readonly Builder $query) {}

    public function generator(): Generator
    {
        $number = 1;

        foreach ($this->query->orderBy('supplier_name')->orderBy('product_name')->cursor() as $row) {
            yield [
                $number++,
                $row->supplier_name,
                $row->supplier_country,
                $row->supplier_phone,
                $row->product_name,
                $row->product_codes,
                (int) $row->qty_inbound,
                (int) $row->stok_now,
                $row->last_inbound_date,
                (int) $row->inbound_rows,
            ];
        }
    }

    public function headings(): array
    {
        return [
            'No',
            'Supplier',
            'Negara',
            'Telepon',
            'Produk',
            'Kode Produk',
            'Total Qty Diterima',
            'Stok Saat Ini',
            'Terakhir Diterima',
            'Jumlah Baris Inbound',
        ];
    }

    public function title(): string
    {
        return 'Supplier Products';
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
