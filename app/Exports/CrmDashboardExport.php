<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CrmDashboardExport implements WithMultipleSheets
{
    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function sheets(): array
    {
        return [
            new CrmDashboardSheet($this->payload),
            new CrmBranchOmsetSheet($this->payload),
            new CrmPurchaseCustomersSheet($this->payload),
            new CrmSpendingCustomersSheet($this->payload),
            new CrmVisitCustomersSheet($this->payload),
            new CrmTransactionDetailsSheet($this->payload),
            new CrmVisitDetailsSheet($this->payload),
        ];
    }
}

class CrmDashboardSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents, WithCharts
{
    protected array $payload;
    protected Collection $branchRows;
    protected Collection $purchaseRows;
    protected Collection $spendingRows;
    protected Collection $visitRows;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->branchRows = collect($payload['branch_omset_rows'] ?? []);
        $this->purchaseRows = collect($payload['purchase_customers'] ?? []);
        $this->spendingRows = collect($payload['spending_customers'] ?? []);
        $this->visitRows = collect($payload['visit_customers'] ?? []);
    }

    public function title(): string
    {
        return 'Dashboard';
    }

    public function array(): array
    {
        $summary = $this->payload['summary'] ?? (object) [];

        $totalStoreOmset = (float) $this->branchRows->sum('store_omset');
        $totalEcommerceOmset = (float) $this->branchRows->sum('ecommerce_omset');
        $totalBranchOmset = (float) $this->branchRows->sum('total_omset');

        $rows = [
            ['CRM CUSTOMER DASHBOARD'],
            [''],
            ['Periode', $this->formatDate($this->payload['from'] ?? null) . ' s/d ' . $this->formatDate($this->payload['to'] ?? null)],
            ['Channel', $this->payload['channel_label'] ?? 'Semua'],
            ['Store', $this->payload['store_name'] ?? 'Semua Store'],
            ['Tanggal Export', now()->format('d-m-Y H:i:s')],
            [''],
            ['RINGKASAN'],
            ['Total Buyer', (int) ($summary->total_buyers ?? 0)],
            ['Total Transaksi', (int) ($summary->total_transactions ?? 0)],
            ['Total Spending', (float) ($summary->total_spending ?? 0)],
            ['Total Visitor', (int) ($summary->total_visitors ?? 0)],
            ['Total Kunjungan', (int) ($summary->total_visits ?? 0)],
            ['Omset Store/POS', $totalStoreOmset],
            ['Omset Ecommerce', $totalEcommerceOmset],
            ['Total Omset Cabang', $totalBranchOmset],
            [''],
            ['DATA OMSET CABANG'],
            ['Cabang', 'Transaksi Store', 'Transaksi Ecommerce', 'Omset Store', 'Omset Ecommerce', 'Total Omset', 'Total Transaksi'],
        ];

        foreach ($this->branchRows as $row) {
            $rows[] = [
                $row->store_name ?? '-',
                (int) ($row->store_transactions ?? 0),
                (int) ($row->ecommerce_transactions ?? 0),
                (float) ($row->store_omset ?? 0),
                (float) ($row->ecommerce_omset ?? 0),
                (float) ($row->total_omset ?? 0),
                (int) ($row->total_transactions ?? 0),
            ];
        }

        $rows[] = [''];
        $rows[] = ['KOMPOSISI OMSET'];
        $rows[] = ['Channel', 'Nominal'];
        $rows[] = ['Store/POS', $totalStoreOmset];
        $rows[] = ['Ecommerce', $totalEcommerceOmset];

        $rows[] = [''];
        $rows[] = ['TOP CUSTOMER PEMBELIAN'];
        $rows[] = ['Customer', 'Pembelian'];
        foreach ($this->purchaseRows as $row) {
            $rows[] = [
                $row->customer_name ?? '-',
                (int) ($row->total_purchases ?? 0),
            ];
        }

        $rows[] = [''];
        $rows[] = ['TOP CUSTOMER SPENDING'];
        $rows[] = ['Customer', 'Spending'];
        foreach ($this->spendingRows as $row) {
            $rows[] = [
                $row->customer_name ?? '-',
                (float) ($row->total_spending ?? 0),
            ];
        }

        $rows[] = [''];
        $rows[] = ['TOP CUSTOMER KUNJUNGAN'];
        $rows[] = ['Customer', 'Kunjungan'];
        foreach ($this->visitRows as $row) {
            $rows[] = [
                $row->customer_name ?? '-',
                (int) ($row->total_visits ?? 0),
            ];
        }

        return $rows;
    }

    public function charts()
    {
        $charts = [];

        $branchCount = $this->branchRows->count();

        if ($branchCount > 0) {
            $branchHeaderRow = 19;
            $branchStartRow = 20;
            $branchEndRow = $branchStartRow + $branchCount - 1;

            $charts[] = $this->barChart(
                name: 'branch_omset_chart',
                title: 'Grafik Omset Terbanyak per Cabang',
                labels: [
                    new DataSeriesValues('String', "'Dashboard'!\$D\${$branchHeaderRow}", null, 1),
                    new DataSeriesValues('String', "'Dashboard'!\$E\${$branchHeaderRow}", null, 1),
                ],
                categories: [
                    new DataSeriesValues('String', "'Dashboard'!\$A\${$branchStartRow}:\$A\${$branchEndRow}", null, $branchCount),
                ],
                values: [
                    new DataSeriesValues('Number', "'Dashboard'!\$D\${$branchStartRow}:\$D\${$branchEndRow}", null, $branchCount),
                    new DataSeriesValues('Number', "'Dashboard'!\$E\${$branchStartRow}:\$E\${$branchEndRow}", null, $branchCount),
                ],
                topLeft: 'I3',
                bottomRight: 'Q20'
            );
        }

        $compositionTitleRow = 20 + $branchCount + 2;
        $compositionHeaderRow = $compositionTitleRow + 1;
        $compositionStartRow = $compositionHeaderRow + 1;
        $compositionEndRow = $compositionStartRow + 1;

        if (((float) $this->branchRows->sum('store_omset') + (float) $this->branchRows->sum('ecommerce_omset')) > 0) {
            $charts[] = $this->pieChart(
                name: 'omset_composition_chart',
                title: 'Komposisi Omset Store/POS vs Ecommerce',
                categories: [
                    new DataSeriesValues('String', "'Dashboard'!\$A\${$compositionStartRow}:\$A\${$compositionEndRow}", null, 2),
                ],
                values: [
                    new DataSeriesValues('Number', "'Dashboard'!\$B\${$compositionStartRow}:\$B\${$compositionEndRow}", null, 2),
                ],
                topLeft: 'I22',
                bottomRight: 'Q38'
            );
        }

        $purchaseTitleRow = $compositionEndRow + 2;
        $purchaseHeaderRow = $purchaseTitleRow + 1;
        $purchaseStartRow = $purchaseHeaderRow + 1;
        $purchaseEndRow = $purchaseStartRow + max($this->purchaseRows->count(), 1) - 1;

        if ($this->purchaseRows->count() > 0) {
            $charts[] = $this->barChart(
                name: 'purchase_customer_chart',
                title: 'Top Customer Pembelian Terbanyak',
                labels: [
                    new DataSeriesValues('String', "'Dashboard'!\$B\${$purchaseHeaderRow}", null, 1),
                ],
                categories: [
                    new DataSeriesValues('String', "'Dashboard'!\$A\${$purchaseStartRow}:\$A\${$purchaseEndRow}", null, $this->purchaseRows->count()),
                ],
                values: [
                    new DataSeriesValues('Number', "'Dashboard'!\$B\${$purchaseStartRow}:\$B\${$purchaseEndRow}", null, $this->purchaseRows->count()),
                ],
                topLeft: 'I40',
                bottomRight: 'Q56'
            );
        }

        $spendingTitleRow = $purchaseEndRow + 2;
        $spendingHeaderRow = $spendingTitleRow + 1;
        $spendingStartRow = $spendingHeaderRow + 1;
        $spendingEndRow = $spendingStartRow + max($this->spendingRows->count(), 1) - 1;

        if ($this->spendingRows->count() > 0) {
            $charts[] = $this->barChart(
                name: 'spending_customer_chart',
                title: 'Top Customer Spending Terbesar',
                labels: [
                    new DataSeriesValues('String', "'Dashboard'!\$B\${$spendingHeaderRow}", null, 1),
                ],
                categories: [
                    new DataSeriesValues('String', "'Dashboard'!\$A\${$spendingStartRow}:\$A\${$spendingEndRow}", null, $this->spendingRows->count()),
                ],
                values: [
                    new DataSeriesValues('Number', "'Dashboard'!\$B\${$spendingStartRow}:\$B\${$spendingEndRow}", null, $this->spendingRows->count()),
                ],
                topLeft: 'I58',
                bottomRight: 'Q74'
            );
        }

        return $charts;
    }

    private function barChart(
        string $name,
        string $title,
        array $labels,
        array $categories,
        array $values,
        string $topLeft,
        string $bottomRight
    ): Chart {
        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);

        $chart = new Chart(
            $name,
            new Title($title),
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    private function pieChart(
        string $name,
        string $title,
        array $categories,
        array $values,
        string $topLeft,
        string $bottomRight
    ): Chart {
        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            [0],
            [],
            $categories,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);

        $chart = new Chart(
            $name,
            new Title($title),
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:G1');

                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '696CFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A8:B16')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getStyle('A18:G18')->applyFromArray($this->sectionStyle());
                $sheet->getStyle('A19:G19')->applyFromArray($this->headerStyle());

                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:G{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                foreach (['B9:B16', 'D20:F200', 'B1:B200'] as $range) {
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                }

                $sheet->getStyle('B11:B11')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('B14:B16')->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $sheet->getStyle('D20:F200')->getNumberFormat()->setFormatCode('"Rp" #,##0');

                $sheet->freezePane('A19');
            },
        ];
    }

    private function sectionStyle(): array
    {
        return [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '566A7F'],
            ],
        ];
    }

    private function headerStyle(): array
    {
        return [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '8592A3'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
    }

    private function formatDate($date): string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '-';
    }
}

abstract class BaseCrmArraySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    abstract public function title(): string;

    abstract public function headings(): array;

    abstract public function rows(): array;

    public function array(): array
    {
        return array_merge(
            [
                [$this->title()],
                ['Periode', $this->formatDate($this->payload['from'] ?? null) . ' s/d ' . $this->formatDate($this->payload['to'] ?? null)],
                ['Channel', $this->payload['channel_label'] ?? 'Semua'],
                ['Store', $this->payload['store_name'] ?? 'Semua Store'],
                [''],
                $this->headings(),
            ],
            $this->rows()
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells("A1:{$highestColumn}1");

                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '696CFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A6:{$highestColumn}6")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '8592A3'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                if ($highestRow >= 6) {
                    $sheet->getStyle("A6:{$highestColumn}{$highestRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                }

                $sheet->freezePane('A7');
            },
        ];
    }

    protected function formatDate($date): string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '-';
    }

    protected function formatDateTime($date): string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y H:i') : '-';
    }
}

class CrmBranchOmsetSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Omset Cabang';
    }

    public function headings(): array
    {
        return [
            'No',
            'Cabang',
            'Transaksi Store',
            'Transaksi Ecommerce',
            'Omset Store',
            'Omset Ecommerce',
            'Total Omset',
            'Total Transaksi',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['branch_omset_rows'] ?? [])
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->store_name ?? '-',
                    (int) ($row->store_transactions ?? 0),
                    (int) ($row->ecommerce_transactions ?? 0),
                    (float) ($row->store_omset ?? 0),
                    (float) ($row->ecommerce_omset ?? 0),
                    (float) ($row->total_omset ?? 0),
                    (int) ($row->total_transactions ?? 0),
                ];
            })
            ->toArray();
    }
}

class CrmPurchaseCustomersSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Pembelian Customer';
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'WA',
            'Channel',
            'Store',
            'Pembelian',
            'Spending',
            'First Purchase',
            'Last Purchase',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['purchase_customers'] ?? [])
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->customer_name ?? '-',
                    $row->wa_number ?? '-',
                    $row->channels ?? '-',
                    $row->stores ?? '-',
                    (int) ($row->total_purchases ?? 0),
                    (float) ($row->total_spending ?? 0),
                    $this->formatDate($row->first_purchase ?? null),
                    $this->formatDate($row->last_purchase ?? null),
                ];
            })
            ->toArray();
    }
}

class CrmSpendingCustomersSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Spending Customer';
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'WA',
            'Channel',
            'Store',
            'Spending',
            'Pembelian',
            'First Purchase',
            'Last Purchase',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['spending_customers'] ?? [])
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->customer_name ?? '-',
                    $row->wa_number ?? '-',
                    $row->channels ?? '-',
                    $row->stores ?? '-',
                    (float) ($row->total_spending ?? 0),
                    (int) ($row->total_purchases ?? 0),
                    $this->formatDate($row->first_purchase ?? null),
                    $this->formatDate($row->last_purchase ?? null),
                ];
            })
            ->toArray();
    }
}

class CrmVisitCustomersSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Kunjungan Customer';
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'WA',
            'Store',
            'Kunjungan',
            'First Visit',
            'Last Visit',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['visit_customers'] ?? [])
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->customer_name ?? '-',
                    $row->wa_number ?? '-',
                    $row->stores ?? '-',
                    (int) ($row->total_visits ?? 0),
                    $this->formatDate($row->first_visit ?? null),
                    $this->formatDate($row->last_visit ?? null),
                ];
            })
            ->toArray();
    }
}

class CrmTransactionDetailsSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Detail Transaksi';
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'WA',
            'Tanggal',
            'Channel',
            'Store',
            'No Transaksi',
            'Visit Code',
            'Status',
            'Nominal',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['customer_transaction_details'] ?? [])
            ->flatMap(fn ($group) => collect($group))
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->customer_name ?? '-',
                    $row->wa_number ?? '-',
                    $this->formatDateTime($row->transaction_at ?? null),
                    $row->channel ?? '-',
                    $row->store_name ?? '-',
                    $row->transaction_code ?? '-',
                    $row->visit_code ?? '-',
                    $row->transaction_status ?? '-',
                    (float) ($row->amount ?? 0),
                ];
            })
            ->toArray();
    }
}

class CrmVisitDetailsSheet extends BaseCrmArraySheet
{
    public function title(): string
    {
        return 'Detail Kunjungan';
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'WA',
            'Visit Code',
            'Store',
            'Check In',
            'Check Out',
            'Visit Type',
            'Status Visit',
            'Status Payment',
        ];
    }

    public function rows(): array
    {
        return collect($this->payload['customer_visit_details'] ?? [])
            ->flatMap(fn ($group) => collect($group))
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->customer_name ?? '-',
                    $row->wa_number ?? '-',
                    $row->visit_code ?? '-',
                    $row->store_name ?? '-',
                    $this->formatDateTime($row->check_in_at ?? null),
                    $this->formatDateTime($row->check_out_at ?? null),
                    $row->visit_type ?? '-',
                    $row->visit_status ?? '-',
                    $row->visit_payment_status ?? '-',
                ];
            })
            ->toArray();
    }
}