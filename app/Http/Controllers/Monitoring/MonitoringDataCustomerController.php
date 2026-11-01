<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\MonitoringDataCustomerExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\District;
use App\Models\PostalCode;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringDataCustomerController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $this->validateRegionFilters($filters);

        $query = $this->baseQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                COUNT(*)::int AS total_customer,

                COUNT(*) FILTER (
                    WHERE DATE(x.created_at) = CURRENT_DATE
                )::int AS total_today,

                COUNT(*) FILTER (
                    WHERE DATE(x.created_at) = CURRENT_DATE
                      AND x.registration_channel = 'store'
                )::int AS total_today_store,

                COUNT(*) FILTER (
                    WHERE DATE(x.created_at) = CURRENT_DATE
                      AND x.registration_channel = 'ecommerce'
                )::int AS total_today_ecommerce,

                COUNT(*) FILTER (
                    WHERE DATE(x.created_at) = CURRENT_DATE
                      AND x.registration_channel = 'backoffice'
                )::int AS total_today_backoffice,

                COUNT(*) FILTER (
                    WHERE x.created_at >= date_trunc('month', CURRENT_DATE)
                )::int AS total_this_month,

                COUNT(*) FILTER (
                    WHERE x.registration_channel = 'store'
                )::int AS total_store,

                COUNT(*) FILTER (
                    WHERE x.registration_channel = 'ecommerce'
                )::int AS total_ecommerce,

                COUNT(*) FILTER (
                    WHERE x.registration_channel = 'backoffice'
                )::int AS total_backoffice,

                COUNT(*) FILTER (
                    WHERE x.gender = 'male'
                )::int AS total_male,

                COUNT(*) FILTER (
                    WHERE x.gender = 'female'
                )::int AS total_female,

                COUNT(*) FILTER (
                    WHERE x.gender IS NULL
                       OR x.gender::text = ''
                )::int AS total_unknown_gender,

                COUNT(*) FILTER (
                    WHERE x.date_of_birth IS NOT NULL
                      AND EXTRACT(MONTH FROM x.date_of_birth) = EXTRACT(MONTH FROM CURRENT_DATE)
                      AND EXTRACT(DAY FROM x.date_of_birth) = EXTRACT(DAY FROM CURRENT_DATE)
                )::int AS birthday_today,

                COUNT(*) FILTER (
                    WHERE x.date_of_birth IS NOT NULL
                      AND EXTRACT(MONTH FROM x.date_of_birth) = EXTRACT(MONTH FROM CURRENT_DATE)
                )::int AS birthday_this_month,

                COUNT(*) FILTER (
                    WHERE x.date_of_birth IS NOT NULL
                )::int AS total_with_birth_date,

                COUNT(*) FILTER (
                    WHERE x.date_of_birth IS NULL
                )::int AS total_without_birth_date,

                COUNT(*) FILTER (
                    WHERE x.province_name IS NOT NULL
                      AND x.regency_name IS NOT NULL
                )::int AS total_with_region,

                COUNT(*) FILTER (
                    WHERE COALESCE(x.wa_number, '') <> ''
                       OR COALESCE(x.email, '') <> ''
                )::int AS total_with_contact
            ")
            ->first();

        $channelChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                CASE
                    WHEN x.registration_channel = 'store' THEN 'Store'
                    WHEN x.registration_channel = 'ecommerce' THEN 'Ecommerce'
                    WHEN x.registration_channel = 'backoffice' THEN 'Backoffice'
                    ELSE 'Lainnya'
                END AS label,
                COUNT(*)::int AS total
            ")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $genderChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                CASE
                    WHEN x.gender = 'male' THEN 'Laki-laki'
                    WHEN x.gender = 'female' THEN 'Perempuan'
                    ELSE 'Tidak Diisi'
                END AS label,
                COUNT(*)::int AS total
            ")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $ageGroupChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                CASE
                    WHEN x.date_of_birth IS NULL THEN 'Tidak Diisi'
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) < 18 THEN '< 18 Tahun'
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 18 AND 24 THEN '18 - 24 Tahun'
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 25 AND 34 THEN '25 - 34 Tahun'
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 35 AND 44 THEN '35 - 44 Tahun'
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 45 AND 54 THEN '45 - 54 Tahun'
                    ELSE '> 54 Tahun'
                END AS label,
                COUNT(*)::int AS total,
                CASE
                    WHEN x.date_of_birth IS NULL THEN 99
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) < 18 THEN 1
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 18 AND 24 THEN 2
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 25 AND 34 THEN 3
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 35 AND 44 THEN 4
                    WHEN DATE_PART('year', AGE(CURRENT_DATE, x.date_of_birth)) BETWEEN 45 AND 54 THEN 5
                    ELSE 6
                END AS sort_order
            ")
            ->groupBy('label', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        $demographicCompletenessRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("'Gender Terisi' AS label, COUNT(*) FILTER (WHERE x.gender IN ('male', 'female'))::int AS total")
            ->unionAll(
                DB::query()
                    ->fromSub(clone $query, 'x')
                    ->selectRaw("'Tanggal Lahir Terisi' AS label, COUNT(*) FILTER (WHERE x.date_of_birth IS NOT NULL)::int AS total")
            )
            ->unionAll(
                DB::query()
                    ->fromSub(clone $query, 'x')
                    ->selectRaw("'Lokasi Terisi' AS label, COUNT(*) FILTER (WHERE x.province_name IS NOT NULL AND x.regency_name IS NOT NULL)::int AS total")
            )
            ->unionAll(
                DB::query()
                    ->fromSub(clone $query, 'x')
                    ->selectRaw("'Kontak Terisi' AS label, COUNT(*) FILTER (WHERE COALESCE(x.wa_number, '') <> '' OR COALESCE(x.email, '') <> '')::int AS total")
            )
            ->get();

        $provinceChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                COALESCE(x.province_name, 'Tidak Diisi') AS label,
                COUNT(*)::int AS total
            ")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $regencyChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                COALESCE(x.regency_name, 'Tidak Diisi') AS label,
                COUNT(*)::int AS total
            ")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $storeChartRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                COALESCE(x.registration_store_name, 'Store Tidak Diketahui') AS label,
                COUNT(*)::int AS total
            ")
            ->where('x.registration_channel', 'store')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $memberDailyRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                TO_CHAR(DATE(x.created_at), 'DD Mon') AS label,
                DATE(x.created_at) AS tanggal,
                COUNT(*)::int AS total
            ")
            ->where('x.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupByRaw('DATE(x.created_at)')
            ->orderBy('tanggal')
            ->get();

        $birthdayRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                x.id,
                x.name,
                x.wa_number,
                x.email,
                x.gender,
                x.date_of_birth,
                x.registration_channel,
                x.registration_source_name,
                x.province_name,
                x.regency_name
            ")
            ->whereNotNull('x.date_of_birth')
            ->whereRaw('EXTRACT(MONTH FROM x.date_of_birth) = EXTRACT(MONTH FROM CURRENT_DATE)')
            ->orderByRaw('EXTRACT(DAY FROM x.date_of_birth)')
            ->limit(20)
            ->get();

        $newMemberRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                x.id,
                x.name,
                x.wa_number,
                x.email,
                x.gender,
                x.created_at,
                x.registration_channel,
                x.registration_source_name,
                x.province_name,
                x.regency_name
            ")
            ->orderByDesc('x.created_at')
            ->limit(10)
            ->get();

        $todayStoreRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                COALESCE(x.registration_source_id::text, 'unknown') AS store_id,
                COALESCE(x.registration_store_name, 'Store Tidak Diketahui') AS store_name,
                COUNT(*)::int AS total,
                MAX(x.created_at) AS latest_created_at
            ")
            ->where('x.registration_channel', 'store')
            ->whereRaw('DATE(x.created_at) = CURRENT_DATE')
            ->groupByRaw("COALESCE(x.registration_source_id::text, 'unknown'), COALESCE(x.registration_store_name, 'Store Tidak Diketahui')")
            ->orderByDesc('total')
            ->orderBy('store_name')
            ->get();

        $todayStoreMemberRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                x.id,
                COALESCE(x.registration_source_id::text, 'unknown') AS store_id,
                COALESCE(x.registration_store_name, 'Store Tidak Diketahui') AS store_name,
                x.name,
                x.wa_number,
                x.email,
                x.gender,
                x.created_at,
                x.province_name,
                x.regency_name
            ")
            ->where('x.registration_channel', 'store')
            ->whereRaw('DATE(x.created_at) = CURRENT_DATE')
            ->orderByDesc('x.created_at')
            ->limit(1000)
            ->get();

        $todayEcommerceRows = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw("
                x.id,
                x.name,
                x.wa_number,
                x.email,
                x.gender,
                x.created_at,
                x.province_name,
                x.regency_name
            ")
            ->where('x.registration_channel', 'ecommerce')
            ->whereRaw('DATE(x.created_at) = CURRENT_DATE')
            ->orderByDesc('x.created_at')
            ->limit(100)
            ->get();

        $customerMapPoints = $this->customerMapPoints(clone $query);

        $rows = $query
            ->orderByDesc('customers.created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $provinces = Province::query()
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        $stores = DB::table('stores')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $selectedRegency = null;
        $selectedDistrict = null;
        $selectedVillage = null;

        if ($filters['regency_code']) {
            $selectedRegency = Regency::query()
                ->select('code', 'name', 'province_code')
                ->where('code', $filters['regency_code'])
                ->first();
        }

        if ($filters['district_code']) {
            $selectedDistrict = District::query()
                ->select('code', 'name', 'regency_code')
                ->where('code', $filters['district_code'])
                ->first();
        }

        if ($filters['village_code']) {
            $selectedVillage = Village::query()
                ->select('code', 'name', 'district_code')
                ->where('code', $filters['village_code'])
                ->first();
        }

        if ($filters['village_code'] && $filters['postal_code'] === '') {
            $filters['postal_code'] = PostalCode::query()
                ->where('village_code', $filters['village_code'])
                ->when($filters['district_code'], fn ($q) => $q->where('district_code', $filters['district_code']))
                ->when($filters['regency_code'], fn ($q) => $q->where('regency_code', $filters['regency_code']))
                ->when($filters['province_code'], fn ($q) => $q->where('province_code', $filters['province_code']))
                ->orderBy('postal_code')
                ->value('postal_code') ?? '';
        }

        return view('content.monitoring.data-customer.index', [
            'rows' => $rows,
            'filters' => $filters,

            'name' => $filters['name'],
            'email' => $filters['email'],
            'waNumber' => $filters['wa_number'],
            'gender' => $filters['gender'],
            'provinceCode' => $filters['province_code'],
            'regencyCode' => $filters['regency_code'],
            'districtCode' => $filters['district_code'],
            'villageCode' => $filters['village_code'],
            'postalCode' => $filters['postal_code'],
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'startBirthDate' => $filters['start_birth_date'],
            'endBirthDate' => $filters['end_birth_date'],
            'birthdayMonth' => $filters['birthday_month'],
            'registrationChannel' => $filters['registration_channel'],
            'registrationStoreId' => $filters['registration_store_id'],
            'perPage' => $filters['per_page'],
            'autoRefresh' => $filters['auto_refresh'],

            'summary' => $summary,
            'channelChartRows' => $channelChartRows,
            'genderChartRows' => $genderChartRows,
            'ageGroupChartRows' => $ageGroupChartRows,
            'demographicCompletenessRows' => $demographicCompletenessRows,
            'provinceChartRows' => $provinceChartRows,
            'regencyChartRows' => $regencyChartRows,
            'storeChartRows' => $storeChartRows,
            'memberDailyRows' => $memberDailyRows,
            'birthdayRows' => $birthdayRows,
            'newMemberRows' => $newMemberRows,
            'todayStoreRows' => $todayStoreRows,
            'todayStoreMemberRows' => $todayStoreMemberRows,
            'todayEcommerceRows' => $todayEcommerceRows,
            'customerMapPoints' => $customerMapPoints,
            'customerMapLocated' => $customerMapPoints->count(),
            'customerMapVillageLocated' => $customerMapPoints
                ->where('accuracy', 'village')
                ->count(),

            'provinces' => $provinces,
            'stores' => $stores,
            'selectedRegency' => $selectedRegency,
            'selectedDistrict' => $selectedDistrict,
            'selectedVillage' => $selectedVillage,

            'genders' => [
                'male' => 'Laki-laki',
                'female' => 'Perempuan',
            ],

            'registrationChannels' => [
                'store' => 'Store',
                'ecommerce' => 'Ecommerce',
                'backoffice' => 'Backoffice',
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'monitoring-data-customer-' . now()->format('Ymd_His') . '.xlsx';

        return app(\App\Services\ReportExportService::class)->enqueue($request, 'customers', $filename);
    }

    private function filters(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $registrationChannel = $request->query('registration_channel');

        if (!in_array($registrationChannel, ['store', 'ecommerce', 'backoffice'], true)) {
            $registrationChannel = null;
        }

        $gender = $request->query('gender');

        if (!in_array($gender, ['male', 'female'], true)) {
            $gender = null;
        }

        $birthdayMonth = $request->query('birthday_month');

        if ($birthdayMonth !== null && $birthdayMonth !== '') {
            $birthdayMonth = (int) $birthdayMonth;

            if ($birthdayMonth < 1 || $birthdayMonth > 12) {
                $birthdayMonth = null;
            }
        } else {
            $birthdayMonth = null;
        }

        return [
            'name' => trim((string) $request->query('name')),
            'email' => trim((string) $request->query('email')),
            'wa_number' => trim((string) $request->query('wa_number')),
            'gender' => $gender,

            'province_code' => $request->query('province_code'),
            'regency_code' => $request->query('regency_code'),
            'district_code' => $request->query('district_code'),
            'village_code' => $request->query('village_code'),
            'postal_code' => trim((string) $request->query('postal_code')),

            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),

            'start_birth_date' => $request->query('start_birth_date'),
            'end_birth_date' => $request->query('end_birth_date'),
            'birthday_month' => $birthdayMonth,

            'registration_channel' => $registrationChannel,
            'registration_store_id' => $request->query('registration_store_id'),

            'per_page' => $perPage,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function validateRegionFilters(array &$filters): void
    {
        if ($filters['province_code'] && $filters['regency_code']) {
            $validRegency = Regency::where('code', $filters['regency_code'])
                ->where('province_code', $filters['province_code'])
                ->exists();

            if (!$validRegency) {
                $filters['regency_code'] = null;
                $filters['district_code'] = null;
                $filters['village_code'] = null;
                $filters['postal_code'] = '';
            }
        }

        if ($filters['regency_code'] && $filters['district_code']) {
            $validDistrict = District::where('code', $filters['district_code'])
                ->where('regency_code', $filters['regency_code'])
                ->exists();

            if (!$validDistrict) {
                $filters['district_code'] = null;
                $filters['village_code'] = null;
                $filters['postal_code'] = '';
            }
        }

        if ($filters['district_code'] && $filters['village_code']) {
            $validVillage = Village::where('code', $filters['village_code'])
                ->where('district_code', $filters['district_code'])
                ->exists();

            if (!$validVillage) {
                $filters['village_code'] = null;
                $filters['postal_code'] = '';
            }
        }
    }

    private function baseQuery(array $filters)
    {
        return Customer::query()
            ->leftJoin('provinces as p', 'p.code', '=', 'customers.province_code')
            ->leftJoin('regencies as r', function ($join) {
                $join->on('r.code', '=', 'customers.regency_code')
                    ->on('r.province_code', '=', 'customers.province_code');
            })
            ->leftJoin('districts as d', function ($join) {
                $join->on('d.code', '=', 'customers.district_code')
                    ->on('d.regency_code', '=', 'customers.regency_code');
            })
            ->leftJoin('villages as v', function ($join) {
                $join->on('v.code', '=', 'customers.village_code')
                    ->on('v.district_code', '=', 'customers.district_code');
            })

            ->leftJoin('stores as rs', function ($join) {
                $join->whereRaw('rs.id::text = customers.registration_source_id')
                    ->where('customers.registration_channel', '=', 'store');
            })

            ->select([
                'customers.*',
                'p.name as province_name',
                'r.name as regency_name',
                'd.name as district_name',
                'v.name as village_name',
                'v.latitude as village_latitude',
                'v.longitude as village_longitude',
                'rs.name as registration_store_name',

                DB::raw("
                    CASE
                        WHEN customers.registration_channel = 'store'
                            THEN COALESCE(rs.name, 'Store Tidak Diketahui')
                        WHEN customers.registration_channel = 'ecommerce'
                            THEN 'Ecommerce'
                        WHEN customers.registration_channel = 'backoffice'
                            THEN 'Backoffice'
                        ELSE '-'
                    END AS registration_source_name
                "),
            ])

            ->whereNull('customers.deleted_at')

            ->whereIn('customers.registration_channel', ['store', 'ecommerce', 'backoffice'])

            ->when($filters['name'] !== '', function ($q) use ($filters) {
                $q->where('customers.name', 'ilike', "%{$filters['name']}%");
            })

            ->when($filters['email'] !== '', function ($q) use ($filters) {
                $q->where('customers.email', 'ilike', "%{$filters['email']}%");
            })

            ->when($filters['wa_number'] !== '', function ($q) use ($filters) {
                $q->where('customers.wa_number', 'ilike', "%{$filters['wa_number']}%");
            })

            ->when($filters['gender'], function ($q) use ($filters) {
                $q->where('customers.gender', $filters['gender']);
            })

            ->when($filters['province_code'], function ($q) use ($filters) {
                $q->where('customers.province_code', $filters['province_code']);
            })

            ->when($filters['regency_code'], function ($q) use ($filters) {
                $q->where('customers.regency_code', $filters['regency_code']);
            })

            ->when($filters['district_code'], function ($q) use ($filters) {
                $q->where('customers.district_code', $filters['district_code']);
            })

            ->when($filters['village_code'], function ($q) use ($filters) {
                $q->where('customers.village_code', $filters['village_code']);
            })

            ->when($filters['postal_code'] !== '', function ($q) use ($filters) {
                $q->where('customers.postal_code', 'ilike', "%{$filters['postal_code']}%");
            })

            ->when($filters['start_date'], function ($q) use ($filters) {
                $q->whereDate('customers.created_at', '>=', $filters['start_date']);
            })

            ->when($filters['end_date'], function ($q) use ($filters) {
                $q->whereDate('customers.created_at', '<=', $filters['end_date']);
            })

            ->when($filters['start_birth_date'], function ($q) use ($filters) {
                $q->whereDate('customers.date_of_birth', '>=', $filters['start_birth_date']);
            })

            ->when($filters['end_birth_date'], function ($q) use ($filters) {
                $q->whereDate('customers.date_of_birth', '<=', $filters['end_birth_date']);
            })

            ->when($filters['birthday_month'], function ($q) use ($filters) {
                $q->whereNotNull('customers.date_of_birth')
                    ->whereRaw('EXTRACT(MONTH FROM customers.date_of_birth) = ?', [
                        $filters['birthday_month'],
                    ]);
            })

            ->when($filters['registration_channel'], function ($q) use ($filters) {
                $q->where('customers.registration_channel', $filters['registration_channel']);
            })

            ->when($filters['registration_store_id'], function ($q) use ($filters) {
                $q->where('customers.registration_channel', 'store')
                    ->where('customers.registration_source_id', (string) $filters['registration_store_id']);
            });
    }

    private function customerMapPoints($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->select([
                'x.id',
                'x.name',
                'x.email',
                'x.wa_number',
                'x.gender',
                'x.date_of_birth',
                'x.address',
                'x.postal_code',
                'x.registration_channel',
                'x.registration_source_name',
                'x.province_code',
                'x.province_name',
                'x.regency_name',
                'x.district_name',
                'x.village_name',
                'x.village_latitude',
                'x.village_longitude',
                'x.created_at',
            ])
            ->whereNotNull('x.province_name')
            ->whereNotNull('x.regency_name')
            ->whereNotNull('x.district_name')
            ->whereNotNull('x.village_name')
            ->whereNotNull('x.village_latitude')
            ->whereNotNull('x.village_longitude')
            ->orderByDesc('x.created_at')
            ->limit(1500)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (string) $row->id,
                    'name' => $row->name ?: 'Customer',
                    'email' => $row->email,
                    'wa_number' => $row->wa_number,
                    'gender' => $row->gender,
                    'date_of_birth' => $row->date_of_birth,
                    'address' => $row->address,
                    'postal_code' => $row->postal_code,
                    'channel' => $row->registration_channel ?: 'unknown',
                    'source' => $row->registration_source_name ?: '-',
                    'province' => $row->province_name ?: '-',
                    'regency' => $row->regency_name ?: '-',
                    'district' => $row->district_name ?: '-',
                    'village' => $row->village_name ?: '-',
                    'latitude' => round((float) $row->village_latitude, 6),
                    'longitude' => round((float) $row->village_longitude, 6),
                    'accuracy' => 'village',
                ];
            })
            ->values();
    }

    public function regencies(Request $request)
    {
        $provinceCode = $request->get('province_code');

        if (!$provinceCode) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('regencies')
            ->select('code as id', 'name as text')
            ->where('province_code', $provinceCode)
            ->orderBy('name')
            ->get();

        return response()->json(['results' => $rows]);
    }

    public function districts(Request $request)
    {
        $regencyCode = $request->get('regency_code');

        if (!$regencyCode) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('districts')
            ->select('code as id', 'name as text')
            ->where('regency_code', $regencyCode)
            ->orderBy('name')
            ->get();

        return response()->json(['results' => $rows]);
    }

    public function villages(Request $request)
    {
        $districtCode = $request->get('district_code');

        if (!$districtCode) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('villages')
            ->select('code as id', 'name as text')
            ->where('district_code', $districtCode)
            ->orderBy('name')
            ->get();

        return response()->json(['results' => $rows]);
    }

    public function postalCode(Request $request)
    {
        $provinceCode = $request->get('province_code');
        $regencyCode = $request->get('regency_code');
        $districtCode = $request->get('district_code');
        $villageCode = $request->get('village_code');

        if (!$villageCode) {
            return response()->json(['postal_code' => null]);
        }

        $postalCode = DB::table('postal_codes')
            ->where('village_code', $villageCode)
            ->when($districtCode, fn ($q) => $q->where('district_code', $districtCode))
            ->when($regencyCode, fn ($q) => $q->where('regency_code', $regencyCode))
            ->when($provinceCode, fn ($q) => $q->where('province_code', $provinceCode))
            ->orderBy('postal_code')
            ->value('postal_code');

        return response()->json(['postal_code' => $postalCode]);
    }
}
