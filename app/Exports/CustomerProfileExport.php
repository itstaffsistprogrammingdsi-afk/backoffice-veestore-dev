<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerProfileExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $payload
    ) {
    }

    public function sheets(): array
    {
        return [
            new CustomerProfileSummarySheet($this->payload),
            new CustomerProfileTransactionsSheet(collect($this->payload['transactions'] ?? [])),
            new CustomerProfileVisitsSheet(collect($this->payload['visits'] ?? [])),
            new CustomerProfilePointsSheet(collect($this->payload['pointMutations'] ?? [])),
        ];
    }
}

abstract class CustomerProfileBaseSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    abstract public function title(): string;

    abstract public function array(): array;

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D35D3']],
            ],
        ];
    }

    protected function formatDate($value): string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y H:i') : '-';
    }
}

class CustomerProfileSummarySheet extends CustomerProfileBaseSheet
{
    public function __construct(
        private readonly array $payload
    ) {
    }

    public function title(): string
    {
        return 'Profil Customer';
    }

    public function array(): array
    {
        $customer = $this->payload['customer'];
        $summary = $this->payload['transactionSummary'] ?? (object) [];
        $points = $this->payload['pointSummary'] ?? (object) [];

        return [
            ['CUSTOMER PROFILE'],
            ['Data diekspor', now()->format('d-m-Y H:i')],
            [],
            ['PROFIL', 'NILAI'],
            ['Nama Customer', $customer->name ?: '-'],
            ['WhatsApp', $customer->wa_number ?: '-'],
            ['Email', $customer->email ?: '-'],
            ['Gender', $customer->gender ? ucfirst((string) $customer->gender) : '-'],
            ['Tanggal Lahir', $customer->date_of_birth ? Carbon::parse($customer->date_of_birth)->format('d-m-Y') : '-'],
            ['Alamat', $customer->address ?: '-'],
            ['Kelurahan', $customer->village_name ?: '-'],
            ['Kecamatan', $customer->district_name ?: '-'],
            ['Kota / Kabupaten', $customer->regency_name ?: '-'],
            ['Provinsi', $customer->province_name ?: '-'],
            ['Kode Pos', $customer->postal_code ?: '-'],
            ['Channel Pendaftaran', $customer->registration_channel ?: '-'],
            ['Sumber Pendaftaran', $customer->registration_source_name ?: '-'],
            ['Referral Code', $customer->referral_code ?: '-'],
            ['Tipe Customer', $customer->is_guest ? 'Guest' : 'Terdaftar'],
            ['Tanggal Terdaftar', $this->formatDate($customer->created_at)],
            [],
            ['METRIK', 'NILAI'],
            ['Total Transaksi', (int) ($summary->total_transactions ?? 0)],
            ['Total Belanja', (float) ($summary->total_spending ?? 0)],
            ['Transaksi Pertama', $this->formatDate($summary->first_purchase ?? null)],
            ['Transaksi Terakhir', $this->formatDate($summary->last_purchase ?? null)],
            ['Total Kunjungan POS', collect($this->payload['visits'] ?? [])->count()],
            ['Poin Saat Ini', (int) ($points->current_balance ?? 0)],
            ['Poin Masuk', (int) ($points->total_point_in ?? 0)],
            ['Poin Digunakan', (int) ($points->total_point_out ?? 0)],
            ['Mutasi Poin Terakhir', $this->formatDate($points->last_mutation_at ?? null)],
        ];
    }
}

class CustomerProfileTransactionsSheet extends CustomerProfileBaseSheet
{
    public function __construct(
        private readonly Collection $transactions
    ) {
    }

    public function title(): string
    {
        return 'Transaksi';
    }

    public function array(): array
    {
        $rows = [[
            'Tanggal', 'Channel', 'Kode Transaksi', 'Kode Visit', 'Store', 'Status', 'Rincian Pembelian', 'Total',
        ]];

        foreach ($this->transactions as $transaction) {
            $rows[] = [
                $this->formatDate($transaction->transaction_at),
                $transaction->channel ?: '-',
                $transaction->transaction_code ?: '-',
                $transaction->visit_code ?: '-',
                $transaction->store_name ?: '-',
                $transaction->transaction_status ?: '-',
                $transaction->purchase_details ?: '-',
                (float) ($transaction->amount ?? 0),
            ];
        }

        return $rows;
    }
}

class CustomerProfileVisitsSheet extends CustomerProfileBaseSheet
{
    public function __construct(
        private readonly Collection $visits
    ) {
    }

    public function title(): string
    {
        return 'Kunjungan POS';
    }

    public function array(): array
    {
        $rows = [[
            'Check In', 'Check Out', 'Kode Visit', 'Store', 'Status Kunjungan', 'Status Pembayaran', 'Tipe Kunjungan',
        ]];

        foreach ($this->visits as $visit) {
            $rows[] = [
                $this->formatDate($visit->check_in_at),
                $this->formatDate($visit->check_out_at),
                $visit->visit_code ?: '-',
                $visit->store_name ?: '-',
                $visit->visit_status ?: '-',
                $visit->payment_status ?: '-',
                $visit->visit_type ?: '-',
            ];
        }

        return $rows;
    }
}

class CustomerProfilePointsSheet extends CustomerProfileBaseSheet
{
    public function __construct(
        private readonly Collection $mutations
    ) {
    }

    public function title(): string
    {
        return 'Mutasi Poin';
    }

    public function array(): array
    {
        $rows = [[
            'Tanggal', 'Tipe', 'Jenis Mutasi', 'Poin', 'Saldo Setelah', 'Deskripsi', 'Referensi',
        ]];

        foreach ($this->mutations as $mutation) {
            $rows[] = [
                $this->formatDate($mutation->created_at),
                $mutation->type ?: '-',
                $mutation->mutation_type ?: '-',
                (int) ($mutation->points ?? 0),
                $mutation->balance_after === null ? '-' : (int) $mutation->balance_after,
                $mutation->description ?: '-',
                $mutation->ref ?: '-',
            ];
        }

        return $rows;
    }
}
