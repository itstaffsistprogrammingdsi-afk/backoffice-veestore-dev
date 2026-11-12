<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitLossExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected string $periodLabel,
        protected array $finance,
        protected array $expenses,
        protected array $summary
    ) {}

    public function title(): string
    {
        return 'Estimasi Laba Operasional';
    }

    public function array(): array
    {
        $rows = [
            ['ESTIMASI LABA OPERASIONAL'],
            ['Periode', $this->periodLabel],
            ['Basis', 'Tanggal transaksi. Harga dasar adalah harga jual acuan, bukan HPP.'],
            [],
            ['Komponen', 'Nominal'],
            ['Omset Kotor', (float) ($this->finance['gross_sales'] ?? 0)],
            ['Diskon Produk', -1 * (float) ($this->finance['product_discount'] ?? 0)],
            ['Diskon Voucher / Poin', -1 * (float) ($this->finance['voucher_discount'] ?? 0)],
        ];

        $clamped = (float) ($this->finance['reconciliation']['clamped_discount'] ?? 0);

        if ($clamped > 0.009) {
            $rows[] = ['Penyesuaian batas bawah 0', $clamped];
        }

        $rows = array_merge($rows, [
            ['Omset Bersih', (float) ($this->summary['net_sales'] ?? 0)],
            ['Nilai Harga Dasar (bukan HPP)', -1 * (float) ($this->summary['base_price'] ?? 0)],
            ['Selisih Kotor (estimasi)', (float) ($this->summary['gross_profit'] ?? 0)],
            ['Beban Operasional', -1 * (float) ($this->summary['total_expenses'] ?? 0)],
            ['Estimasi Laba Operasional', (float) ($this->summary['net_profit'] ?? 0)],
            [],
            ['Per Kanal', 'Omset Bersih', 'Selisih Kotor', 'Margin (%)', 'Transaksi'],
        ]);

        foreach (($this->finance['channels'] ?? []) as $channel) {
            $rows[] = [
                $channel['label'] ?? $channel['key'] ?? '-',
                (float) ($channel['net_sales'] ?? 0),
                (float) ($channel['gross_profit'] ?? 0),
                (float) ($channel['gross_margin_percent'] ?? 0),
                (int) ($channel['transactions'] ?? 0),
            ];
        }

        $rows[] = [];
        $rows[] = ['Beban Operasional', 'Kategori', 'Catatan', 'Dicatat Oleh', 'Tanggal', 'Nominal'];

        foreach ($this->expenses as $expense) {
            $rows[] = [
                'Beban',
                $expense['category'] ?? '-',
                $expense['note'] ?? '-',
                $expense['created_by'] ?? '-',
                $expense['created_at'] ?? '-',
                -1 * (float) ($expense['amount'] ?? 0),
            ];
        }

        $rows[] = [];
        $rows[] = ['Catatan', 'Laporan ini estimasi manajemen, bukan laba rugi akuntansi. Biaya perolehan, pajak, dan jurnal belum termasuk.'];

        return $rows;
    }
}
