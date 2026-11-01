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

class CrmDemographicsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $customers)
    {
    }

    public function collection(): Collection
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'Nama Customer', 'WhatsApp', 'Email', 'Gender', 'Tanggal Lahir', 'Usia',
            'Alamat', 'Kelurahan', 'Kecamatan', 'Kota / Kabupaten', 'Provinsi',
            'Kode Pos', 'Channel Pendaftaran', 'Sumber Pendaftaran', 'Tanggal Terdaftar',
        ];
    }

    public function map($customer): array
    {
        $birthDate = $customer->date_of_birth;

        return [
            $customer->name ?: '-',
            $customer->wa_number ?: '-',
            $customer->email ?: '-',
            $customer->gender ? ucfirst((string) $customer->gender) : '-',
            $birthDate ? $birthDate->format('d-m-Y') : '-',
            $birthDate ? $birthDate->age : '-',
            $customer->address ?: '-',
            $customer->village_name ?: '-',
            $customer->district_name ?: '-',
            $customer->regency_name ?: '-',
            $customer->province_name ?: '-',
            $customer->postal_code ?: '-',
            $customer->registration_channel ?: '-',
            $customer->registration_source_name ?: '-',
            $customer->created_at ? $customer->created_at->format('d-m-Y H:i') : '-',
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
