<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringDataCustomerExport implements FromGenerator, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $name = $request->get('name');
        $email = $request->get('email');
        $waNumber = $request->get('wa_number');
        $gender = $request->get('gender');
        $provinceCode = $request->get('province_code');
        $regencyCode = $request->get('regency_code');
        $districtCode = $request->get('district_code');
        $villageCode = $request->get('village_code');
        $postalCode = $request->get('postal_code');
        $startBirthDate = $request->get('start_birth_date');
        $endBirthDate = $request->get('end_birth_date');
        $registrationChannel = $request->get('registration_channel');
        $registrationStoreId = $request->get('registration_store_id');

        if (! in_array($registrationChannel, ['store', 'ecommerce', 'backoffice'], true)) {
            $registrationChannel = null;
        }

        $query = DB::table('customers as c')
            ->leftJoin('provinces as p', 'p.code', '=', 'c.province_code')
            ->leftJoin('regencies as r', function ($join) {
                $join->on('r.code', '=', 'c.regency_code')
                    ->on('r.province_code', '=', 'c.province_code');
            })
            ->leftJoin('districts as d', function ($join) {
                $join->on('d.code', '=', 'c.district_code')
                    ->on('d.regency_code', '=', 'c.regency_code');
            })
            ->leftJoin('villages as v', function ($join) {
                $join->on('v.code', '=', 'c.village_code')
                    ->on('v.district_code', '=', 'c.district_code');
            })
            ->leftJoin('postal_codes as pc', function ($join) {
                $join->on('pc.postal_code', '=', 'c.postal_code')
                    ->on('pc.province_code', '=', 'c.province_code')
                    ->on('pc.regency_code', '=', 'c.regency_code')
                    ->on('pc.district_code', '=', 'c.district_code')
                    ->on('pc.village_code', '=', 'c.village_code');
            })
            ->select([
                'c.id',
                'c.name',
                'c.address',
                'c.email',
                'c.wa_number',
                'c.gender',
                'c.created_at',
                'c.updated_at',
                'c.deleted_at',
                'c.date_of_birth',
                'c.user_id',
                'c.referral_code',
                'c.province_code',
                'c.regency_code',
                'c.village_code',
                'c.district_code',
                'c.postal_code',
                DB::raw("COALESCE(p.name, '-') as province_name"),
                DB::raw("COALESCE(r.name, '-') as regency_name"),
                DB::raw("COALESCE(d.name, '-') as district_name"),
                DB::raw("COALESCE(v.name, '-') as village_name"),
            ])
            ->whereNull('c.deleted_at')
            ->whereIn('c.registration_channel', ['store', 'ecommerce', 'backoffice'])
            ->when($name, function ($q) use ($name) {
                $q->where('c.name', 'ilike', "%{$name}%");
            })
            ->when($email, function ($q) use ($email) {
                $q->where('c.email', 'ilike', "%{$email}%");
            })
            ->when($waNumber, function ($q) use ($waNumber) {
                $q->where('c.wa_number', 'ilike', "%{$waNumber}%");
            })
            ->when($gender, function ($q) use ($gender) {
                $q->where('c.gender', $gender);
            })
            ->when($provinceCode, function ($q) use ($provinceCode) {
                $q->where('c.province_code', $provinceCode);
            })
            ->when($regencyCode, function ($q) use ($regencyCode) {
                $q->where('c.regency_code', $regencyCode);
            })
            ->when($districtCode, function ($q) use ($districtCode) {
                $q->where('c.district_code', $districtCode);
            })
            ->when($villageCode, function ($q) use ($villageCode) {
                $q->where('c.village_code', $villageCode);
            })
            ->when($postalCode, function ($q) use ($postalCode) {
                $q->where('c.postal_code', 'ilike', "%{$postalCode}%");
            })
            ->when($startBirthDate, function ($q) use ($startBirthDate) {
                $q->whereDate('c.date_of_birth', '>=', $startBirthDate);
            })
            ->when($endBirthDate, function ($q) use ($endBirthDate) {
                $q->whereDate('c.date_of_birth', '<=', $endBirthDate);
            })
            ->when($registrationChannel, function ($q) use ($registrationChannel) {
                $q->where('c.registration_channel', $registrationChannel);
            })
            ->when($registrationStoreId, function ($q) use ($registrationStoreId) {
                $q->where('c.registration_channel', 'store')
                    ->where('c.registration_source_id', (string) $registrationStoreId);
            })
            ->orderByDesc('c.created_at');

        $query->whereBetween('c.created_at', [
            \Illuminate\Support\Carbon::parse($request->input('start_date'))->startOfDay(),
            \Illuminate\Support\Carbon::parse($request->input('end_date'))->endOfDay(),
        ])->orderBy('c.id');
        $this->rows = $query;

        $this->filters = [
            'name' => $name,
            'email' => $email,
            'wa_number' => $waNumber,
            'gender' => $gender,
            'province_code' => $provinceCode,
            'regency_code' => $regencyCode,
            'district_code' => $districtCode,
            'village_code' => $villageCode,
            'postal_code' => $postalCode,
            'start_birth_date' => $startBirthDate,
            'end_birth_date' => $endBirthDate,
            'registration_channel' => $registrationChannel,
            'registration_store_id' => $registrationStoreId,
        ];
    }

    public function title(): string
    {
        return 'Monitoring Data Customer';
    }

    public function generator(): \Generator
    {
        $printedAt = now()->format('d-m-Y H:i:s');

        $genderLabel = match ($this->filters['gender'] ?? '') {
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
            default => 'Semua Gender',
        };

        $data = [
            ['LAPORAN MONITORING DATA CUSTOMER'],
            [''],
            ['Tanggal Export', ': ' . $printedAt],
            ['Nama', ': ' . ($this->filters['name'] ?: '-')],
            ['Email', ': ' . ($this->filters['email'] ?: '-')],
            ['No. WA', ': ' . ($this->filters['wa_number'] ?: '-')],
            ['Gender', ': ' . $genderLabel],
            ['Provinsi', ': ' . ($this->filters['province_code'] ?: '-')],
            ['Kab/Kota', ': ' . ($this->filters['regency_code'] ?: '-')],
            ['Kecamatan', ': ' . ($this->filters['district_code'] ?: '-')],
            ['Kelurahan', ': ' . ($this->filters['village_code'] ?: '-')],
            ['Kode Pos', ': ' . ($this->filters['postal_code'] ?: '-')],
            ['Tanggal Lahir', ': ' . ($this->filters['start_birth_date'] ?: '-') . ' s/d ' . ($this->filters['end_birth_date'] ?: '-')],
            [''],
            [
                'No',
                'Nama',
                'Email',
                'No. WA',
                'Gender',
                'Tanggal Lahir',
                'Referral Code',
                'Alamat',
                'Provinsi',
                'Kab/Kota',
                'Kecamatan',
                'Kelurahan',
                'Kode Pos',
                'Created At',
                'Updated At',
                'Deleted At',
            ],
        ];

        yield from $data;
        $count = 0;
        foreach ($this->rows->lazy(config('report_exports.chunk_size', 500)) as $i => $row) {
            if (++$count > config('report_exports.max_rows', 20000)) {
                throw new \RuntimeException('Jumlah data melebihi batas export. Persempit filter.');
            }
            yield [
                $i + 1,
                $row->name ?? '',
                $row->email ?? '',
                $row->wa_number ?? '',
                $row->gender ?? '',
                $row->date_of_birth ? \Carbon\Carbon::parse($row->date_of_birth)->format('d-m-Y') : '',
                $row->referral_code ?? '',
                $row->address ?? '',
                $row->province_name ?? '-',
                $row->regency_name ?? '-',
                $row->district_name ?? '-',
                $row->village_name ?? '-',
                $row->postal_code ?? '',
                $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '',
                $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('d-m-Y H:i') : '',
                $row->deleted_at ? \Carbon\Carbon::parse($row->deleted_at)->format('d-m-Y H:i') : '',
            ];
        }

        yield [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTAL DATA',
            $count,
        ];


    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 15;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:P1');

        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:B13')->applyFromArray([
            'font' => ['size' => 11],
        ]);

        $sheet->getStyle('A3:A13')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle("A{$headerRow}:P{$headerRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:P{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$lastRow}:P{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAD3'],
            ],
        ]);

        $sheet->getStyle("A16:A{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A1:P{$lastRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->freezePane('A16');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(32);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(18);
        $sheet->getColumnDimension('M')->setWidth(12);
        $sheet->getColumnDimension('N')->setWidth(18);
        $sheet->getColumnDimension('O')->setWidth(18);
        $sheet->getColumnDimension('P')->setWidth(18);

        return [];
    }
}
