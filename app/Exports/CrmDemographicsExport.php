<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class CrmDemographicsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly Collection $customers) {}

    public function collection(): Collection
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'Nama Customer',
            'WhatsApp',
            'Email',
            'Gender',
            'Tanggal Lahir',
            'Usia',
            'Kelompok Usia',
            'Status Customer',
            'Alamat',
            'Kelurahan / Desa',
            'Kecamatan',
            'Kota / Kabupaten',
            'Provinsi',
            'Kode Pos',
            'Kode Referral',
            'Channel Pendaftaran',
            'Sumber Pendaftaran',
            'Tier Membership',
            'Status Membership',
            'Tier Dievaluasi',
            'Tier Berlaku Sampai',
            'Total Spend 12 Bulan',
            'Poin Loyalty',
            'Jumlah Kunjungan',
            'Store Dikunjungi',
            'Kunjungan Terakhir',
            'Transaksi POS',
            'Belanja POS',
            'Order Ecommerce',
            'Belanja Ecommerce',
            'Total Belanja',
            'Tanggal Terdaftar',
            'Terakhir Diperbarui',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name ?: '-',
            $customer->wa_number ?: '-',
            $customer->email ?: '-',
            $this->genderLabel($customer->gender),
            $this->formatDate($customer->date_of_birth),
            $customer->age ?? '-',
            $customer->age_group ?: '-',
            $customer->is_guest ? 'Guest' : 'Terdaftar',
            $customer->address ?: '-',
            $customer->village_name ?: '-',
            $customer->district_name ?: '-',
            $customer->regency_name ?: '-',
            $customer->province_name ?: '-',
            $customer->postal_code ?: '-',
            $customer->referral_code ?: '-',
            $this->channelLabel($customer->registration_channel),
            $customer->registration_source_name ?: ($customer->registration_source_id ?: '-'),
            $customer->membership_name ?: 'Belum memiliki tier',
            $this->membershipStatusLabel($customer->membership_status),
            $this->formatDateTime($customer->membership_evaluated_at),
            $this->formatDate($customer->membership_valid_until),
            (float) ($customer->membership_total_spend_12_month ?? 0),
            (float) ($customer->loyalty_points ?? 0),
            (int) ($customer->visit_count ?? 0),
            (int) ($customer->visited_store_count ?? 0),
            $this->formatDateTime($customer->last_visit_at),
            (int) ($customer->pos_transactions ?? 0),
            (float) ($customer->pos_spending ?? 0),
            (int) ($customer->ecom_orders ?? 0),
            (float) ($customer->ecom_spending ?? 0),
            (float) ($customer->total_spending ?? 0),
            $this->formatDateTime($customer->created_at),
            $this->formatDateTime($customer->updated_at),
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

    private function genderLabel(mixed $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male', 'm', 'laki-laki' => 'Laki-laki',
            'female', 'f', 'perempuan' => 'Perempuan',
            default => '-',
        };
    }

    private function channelLabel(mixed $channel): string
    {
        return match (strtolower((string) $channel)) {
            'store' => 'Store',
            'ecommerce' => 'E-commerce',
            'backoffice' => 'Backoffice',
            'warehouse' => 'Warehouse',
            default => $channel ?: '-',
        };
    }

    private function membershipStatusLabel(mixed $status): string
    {
        return match (strtolower((string) $status)) {
            'active' => 'Aktif',
            'non_active' => 'Tidak Aktif',
            default => $status ?: '-',
        };
    }

    private function formatDate(mixed $value): string
    {
        if (! $value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d-m-Y');
        } catch (Throwable) {
            return '-';
        }
    }

    private function formatDateTime(mixed $value): string
    {
        if (! $value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d-m-Y H:i');
        } catch (Throwable) {
            return '-';
        }
    }
}
