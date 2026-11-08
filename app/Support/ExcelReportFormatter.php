<?php

namespace App\Support;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelReportFormatter
{
    private const CURRENCY_HEADER = '/\b(total|nominal|amount|harga|price|omset|omzet|revenue|pendapatan|income|pemasukan|sales|spending|belanja|penjualan|paid|payment|discount|diskon|pajak|tax|saldo|cost|biaya|cash|tunai|piutang|refund|kembalian|profit|komisi|margin|subtotal|purchase)\b/i';

    private const NON_CURRENCY_HEADER = '/\b(qty|quantity|jumlah transaksi|jumlah produk|count|persentase|percentage|percent|share|poin|stock|stok|date|tanggal|time|waktu|at|id|order|pesanan|produk|product|item|sku|transaction|transaksi|customer|visitor|cashier|session|aging|days|hari|request|shift|baris|row)\b/i';

    private const FILTER_LABELS = [
        'filter', 'filters', 'filter diterapkan', 'tanggal export', 'periode filter', 'rentang tanggal',
        'pencarian', 'search', 'keyword', 'store', 'warehouse', 'status', 'nama diskon', 'tipe',
        'status aktif', 'status periode', 'tanggal', 'periode', 'dari', 'sampai', 'channel',
    ];

    public static function handle(BeforeWriting $event): void
    {
        $routeName = request()->route()?->getName() ?? '';
        $isReportRoute = Str::startsWith($routeName, ['monitoring.', 'reports.']);

        if (! $isReportRoute) {
            return;
        }

        $filters = self::filtersFromRequest(request()->query());
        $spreadsheet = $event->getWriter()->getDelegate();

        if (! $spreadsheet instanceof Spreadsheet) {
            return;
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            self::formatCurrencyColumns($sheet);
            $title = $sheet->getTitle();
            if ($title === 'Worksheet') {
                $className = class_basename($event->getConcernable());
                $title = Str::headline(Str::replaceEnd('Export', '', $className));
            }

            self::addTitleAndFilters($sheet, $filters, $title);
            $sheet->getHeaderFooter()->setOddFooter('&C&"Arial,Italic"&10&KDDDDDD veestore');
        }
    }

    private static function filtersFromRequest(array $query): array
    {
        $filters = [];

        foreach ($query as $key => $value) {
            if (in_array($key, ['page', 'per_page', 'export', 'sort', 'direction', 'tab'], true)) {
                continue;
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $label = Str::of($key)->replace(['_', '-'], ' ')->squish()->title()->toString();
            $display = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;

            if (in_array(strtolower($key), ['is_active', 'is_paid', 'exception_only'], true)) {
                $display = in_array(strtolower($display), ['1', 'true', 'yes'], true) ? 'Ya' : 'Tidak';
            }

            $filters[] = [$label, $display];
        }

        return $filters;
    }

    private static function addTitleAndFilters(Worksheet $sheet, array $filters, string $title): void
    {
        $hasTitle = self::isTitleRow($sheet);
        $hasFilters = self::hasExistingFilterMetadata($sheet);
        $rowsToInsert = ($hasTitle ? 0 : 1) + ($hasFilters ? 0 : count($filters) + 1);

        if ($rowsToInsert === 0) {
            return;
        }

        $insertAt = $hasTitle ? 2 : 1;
        $sheet->insertNewRowBefore($insertAt, $rowsToInsert);
        $lastColumnIndex = max(2, Coordinate::columnIndexFromString($sheet->getHighestColumn()));
        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);
        $row = $insertAt;

        if (! $hasTitle) {
            $sheet->setCellValue('A'.$row, $title);

            if ($lastColumnIndex > 1) {
                $sheet->mergeCells('A'.$row.':'.$lastColumn.$row);
            }

            $sheet->getStyle('A'.$row.':'.$lastColumn.$row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '243047']],
                'alignment' => ['vertical' => 'center'],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(25);
            $row++;
        }

        if (! $hasFilters) {
            $sheet->setCellValue('A'.$row, 'Filter diterapkan');
            $sheet->setCellValue('B'.$row, count($filters) > 0 ? count($filters).' filter' : 'Semua data');
            if ($lastColumnIndex > 2) {
                $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
            }
            $row++;

            foreach ($filters as [$label, $value]) {
                $sheet->setCellValue('A'.$row, $label);
                $sheet->setCellValue('B'.$row, $value);
                if ($lastColumnIndex > 2) {
                    $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
                }
                $row++;
            }

            $filterEndRow = $row - 1;
            $sheet->getStyle('A'.($hasTitle ? 2 : ($insertAt + 1)).':A'.$filterEndRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.($hasTitle ? 2 : ($insertAt + 1)).':'.$lastColumn.$filterEndRow)->applyFromArray([
                'font' => ['color' => ['rgb' => '586174'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F5F7FA']],
            ]);
        }

        if (! $hasTitle && $hasFilters && $lastColumnIndex > 1) {
            // The existing filter block moves down when a title is inserted.
            $sheet->mergeCells('A'.$insertAt.':'.$lastColumn.$insertAt);
        }
    }

    private static function isTitleRow(Worksheet $sheet): bool
    {
        $value = trim((string) $sheet->getCell('A1')->getValue());

        if ($value === '') {
            return false;
        }

        $filledCells = 0;
        foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
            if ($cell->getValue() !== null && trim((string) $cell->getValue()) !== '') {
                $filledCells++;
            }
        }

        if ($filledCells !== 1 || mb_strlen($value) <= 5) {
            return false;
        }

        if (mb_strtoupper($value) === $value || Str::contains(strtolower($value), 'laporan')) {
            return true;
        }

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= min($highestColumnIndex, 5); $column++) {
            if (trim((string) $sheet->getCell([$column, 2])->getValue()) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function hasExistingFilterMetadata(Worksheet $sheet): bool
    {
        for ($row = 1; $row <= min($sheet->getHighestRow(), 20); $row++) {
            $label = Str::of((string) $sheet->getCell('A'.$row)->getValue())->lower()->squish()->toString();
            $value = trim((string) $sheet->getCell('B'.$row)->getValue());

            if (in_array($label, self::FILTER_LABELS, true) && str_starts_with($value, ':')) {
                return true;
            }

            if ($label === 'filter diterapkan') {
                return true;
            }
        }

        return false;
    }

    private static function formatCurrencyColumns(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $format = '"Rp" #,##0;[Red]-"Rp" #,##0';

        for ($row = 1; $row <= min($highestRow, 40); $row++) {
            $moneyColumns = [];

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $heading = trim((string) $sheet->getCell([$column, $row])->getValue());
                if (self::isCurrencyHeading($heading)) {
                    $moneyColumns[] = $column;
                }
            }

            foreach ($moneyColumns as $column) {
                for ($dataRow = $row + 1; $dataRow <= $highestRow; $dataRow++) {
                    $cell = $sheet->getCell([$column, $dataRow]);
                    $value = $cell->getValue();

                    if (is_numeric($value) && $value !== '') {
                        $cell->getStyle()->getNumberFormat()->setFormatCode($format);
                    }
                }
            }

            $label = trim((string) $sheet->getCell([1, $row])->getValue());
            $adjacentValue = $sheet->getCell([2, $row]);
            if ($highestColumnIndex > 1 && self::isCurrencyHeading($label) && is_numeric($adjacentValue->getValue())) {
                $adjacentValue->getStyle()->getNumberFormat()->setFormatCode($format);
            }
        }
    }

    private static function isCurrencyHeading(string $heading): bool
    {
        if (preg_match('/\b(average transaction|average order value|average basket value|aov)\b/i', $heading) === 1) {
            return true;
        }

        if ($heading === '' || preg_match(self::NON_CURRENCY_HEADER, $heading)) {
            return false;
        }

        return preg_match(self::CURRENCY_HEADER, $heading) === 1
            || preg_match('/\b(nilai voucher|voucher amount|voucher nominal|voucher discount)\b/i', $heading) === 1;
    }
}
