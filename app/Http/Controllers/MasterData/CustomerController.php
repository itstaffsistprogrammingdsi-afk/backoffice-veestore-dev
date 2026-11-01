<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Store;
use App\Models\Village;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-customer')
            ->only(['index', 'regencies', 'districts', 'villages']);

        $this->middleware('permission:create master-customer')
            ->only(['store']);

        $this->middleware('permission:update master-customer')
            ->only(['update']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $customers = Customer::query()
            ->with([
                'province:code,name',
                'regency:code,name',
                'district:code,name',
                'village:code,name',
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('email', 'ILIKE', "%{$q}%")
                        ->orWhere('wa_number', 'ILIKE', "%{$q}%")
                        ->orWhere('referral_code', 'ILIKE', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        /*
         * Data lama dapat memiliki registration_source_id non-UUID,
         * misalnya "web_1.1". PostgreSQL akan error bila nilai tersebut
         * langsung dipakai pada WHERE IN kolom stores.id bertipe UUID.
         *
         * Karena itu hanya UUID valid yang digunakan untuk mengambil Store.
         */
        $registrationSourceIds = $customers
            ->getCollection()
            ->pluck('registration_source_id')
            ->filter(
                static fn ($value) => is_string($value)
                    && preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                        $value
                    )
            )
            ->unique()
            ->values()
            ->all();

        $registrationSourceMap = Store::query()
            ->whereIn('id', $registrationSourceIds)
            ->get(['id', 'name', 'code'])
            ->keyBy(
                static fn (Store $store) => (string) $store->getKey()
            );

        $stores = Store::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $provinces = Province::query()
            ->orderBy('name')
            ->get(['code', 'name']);

        $genders = [
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
        ];

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Customers',
            'description' => 'Kelola data pelanggan.',
            'searchPlaceholder' => 'Cari customer...',
            'items' => $customers,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'wa_number', 'label' => 'WhatsApp'],
                ['key' => 'gender', 'label' => 'Gender'],
                ['key' => 'address', 'label' => 'Alamat'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Customer', 'required' => true],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'wa_number', 'label' => 'Nomor WhatsApp', 'required' => true],
                ['key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => [['value' => 'male', 'label' => 'Laki-laki'], ['value' => 'female', 'label' => 'Perempuan']]],
                ['key' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['key' => 'date_of_birth', 'label' => 'Tanggal Lahir', 'type' => 'date'],
                ['key' => 'postal_code', 'label' => 'Kode Pos'],
                ['key' => 'registration_source_id', 'label' => 'Store Registrasi', 'type' => 'select', 'required' => true, 'options' => $stores->map(fn ($store) => ['value' => $store->id, 'label' => $store->name])->values()->all()],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.customers.index'),
                'store' => route('master-data.customers.store'),
                'update' => route('master-data.customers.update', ['customer' => '__ID__']),
                'destroy' => route('master-data.customers.destroy', ['customer' => '__ID__']),
            ],
        ]);
    }

    public function regencies(Request $request): JsonResponse
    {
        $provinceCode = trim((string) $request->query('province_code'));

        if ($provinceCode === '') {
            return response()->json(['results' => []]);
        }

        $results = Regency::query()
            ->where('province_code', $provinceCode)
            ->orderBy('name')
            ->get(['code as id', 'name as text']);

        return response()->json(compact('results'));
    }

    public function districts(Request $request): JsonResponse
    {
        $regencyCode = trim((string) $request->query('regency_code'));

        if ($regencyCode === '') {
            return response()->json(['results' => []]);
        }

        $results = District::query()
            ->where('regency_code', $regencyCode)
            ->orderBy('name')
            ->get(['code as id', 'name as text']);

        return response()->json(compact('results'));
    }

    public function villages(Request $request): JsonResponse
    {
        $districtCode = trim((string) $request->query('district_code'));

        if ($districtCode === '') {
            return response()->json(['results' => []]);
        }

        $results = Village::query()
            ->where('district_code', $districtCode)
            ->orderBy('name')
            ->get(['code as id', 'name as text']);

        return response()->json(compact('results'));
    }

    public function store(Request $request): RedirectResponse
    {
        $registrationChannel = 'backoffice';

        $normalizedName = $this->normalizeCustomerName(
            $request->input('name')
        );

        $isGuest = $request->boolean('is_guest')
            || $this->isGuestName($normalizedName);

        $request->merge([
            '_from' => 'create',
            'name' => $isGuest ? 'Guest' : $normalizedName,
            'registration_channel' => $registrationChannel,
            'wa_number' => $this->normalizePhone(
                $request->input('wa_number')
            ),
            'is_guest' => $isGuest,
        ]);

        $data = $request->validate(
            $this->rules(null, $request),
            $this->messages(),
            $this->attributes()
        );

        try {
            Customer::create([
                'name' => trim($data['name']),
                'address' => $this->nullableTrim(
                    $data['address'] ?? null
                ),
                'email' => $this->nullableTrim(
                    $data['email'] ?? null
                ),
                'wa_number' => $data['wa_number'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'province_code' => $data['province_code'] ?? null,
                'regency_code' => $data['regency_code'] ?? null,
                'district_code' => $data['district_code'] ?? null,
                'village_code' => $data['village_code'] ?? null,
                'postal_code' => $this->nullableTrim(
                    $data['postal_code'] ?? null
                ),
                'registration_channel' => $registrationChannel,
                'registration_source_id' => $data['registration_source_id'],
                'is_guest' => (bool) ($data['is_guest'] ?? false),
            ]);

            return redirect()
                ->route('master-data.customers.index')
                ->with('success', 'Customer berhasil ditambahkan.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Customer gagal disimpan. Silakan coba kembali.',
                ]);
        }
    }

    public function update(
        Request $request,
        Customer $customer
    ): RedirectResponse {
        $registrationChannel = 'backoffice';

        $normalizedName = $this->normalizeCustomerName(
            $request->input('name')
        );

        $isGuest = $request->boolean('is_guest')
            || $this->isGuestName($normalizedName);

        $request->merge([
            '_from' => 'edit',
            'name' => $isGuest ? 'Guest' : $normalizedName,
            'id' => $customer->getKey(),
            'update_url' => route(
                'master-data.customers.update',
                $customer->getKey()
            ),
            'registration_channel' => $registrationChannel,
            'wa_number' => $this->normalizePhone(
                $request->input('wa_number')
            ),
            'is_guest' => $isGuest,
        ]);

        $data = $request->validate(
            $this->rules($customer, $request),
            $this->messages(),
            $this->attributes()
        );

        try {
            $customer->update([
                'name' => trim($data['name']),
                'address' => $this->nullableTrim(
                    $data['address'] ?? null
                ),
                'email' => $this->nullableTrim(
                    $data['email'] ?? null
                ),
                'wa_number' => $data['wa_number'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'province_code' => $data['province_code'] ?? null,
                'regency_code' => $data['regency_code'] ?? null,
                'district_code' => $data['district_code'] ?? null,
                'village_code' => $data['village_code'] ?? null,
                'postal_code' => $this->nullableTrim(
                    $data['postal_code'] ?? null
                ),
                'registration_channel' => $registrationChannel,
                'registration_source_id' => $data['registration_source_id'],
                'is_guest' => (bool) ($data['is_guest'] ?? false),
            ]);

            return redirect()
                ->route('master-data.customers.index')
                ->with('success', 'Customer berhasil diperbarui.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Customer gagal diperbarui. Silakan coba kembali.',
                ]);
        }
    }

    private function rules(
        ?Customer $customer = null,
        ?Request $request = null
    ): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->ignore($customer?->getKey()),
            ],
            'wa_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'wa_number')
                    ->ignore($customer?->getKey()),
            ],
            'gender' => [
                'required',
                Rule::in(['male', 'female']),
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'province_code' => [
                'nullable',
                'string',
                Rule::exists('provinces', 'code'),
            ],
            'regency_code' => [
                'nullable',
                'string',
                Rule::exists('regencies', 'code')->where(
                    fn ($query) => $query->where(
                        'province_code',
                        (string) $request?->input('province_code')
                    )
                ),
            ],
            'district_code' => [
                'nullable',
                'string',
                Rule::exists('districts', 'code')->where(
                    fn ($query) => $query->where(
                        'regency_code',
                        (string) $request?->input('regency_code')
                    )
                ),
            ],
            'village_code' => [
                'nullable',
                'string',
                Rule::exists('villages', 'code')->where(
                    fn ($query) => $query->where(
                        'district_code',
                        (string) $request?->input('district_code')
                    )
                ),
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'registration_channel' => [
                'required',
                'string',
                'max:100',
            ],
            'registration_source_id' => [
                'required',
                'uuid',
                'exists:stores,id',
                function (
                    string $attribute,
                    mixed $value,
                    Closure $fail
                ) use ($customer, $request): void {
                    $name = $this->normalizeCustomerName(
                        $request?->input('name')
                    );

                    $isGuest = $request?->boolean('is_guest')
                        || $this->isGuestName($name);

                    if (! $isGuest) {
                        return;
                    }

                    $exists = Customer::query()
                        ->where('registration_source_id', (string) $value)
                        ->where(function ($query) {
                            $query->where('is_guest', true)
                                ->orWhereRaw(
                                    'LOWER(TRIM(name)) = ?',
                                    ['guest']
                                );
                        })
                        ->when(
                            $customer !== null,
                            fn ($query) => $query->whereKeyNot($customer->getKey())
                        )
                        ->exists();

                    if ($exists) {
                        $fail(
                            'Data guest untuk cabang ini sudah tersedia. '.
                            'Setiap cabang hanya boleh memiliki 1 data guest.'
                        );
                    }
                },
            ],
            'is_guest' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama customer wajib diisi.',
            'name.max' => 'Nama customer maksimal 255 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'wa_number.required' => 'Nomor WhatsApp wajib diisi.',
            'wa_number.unique' => 'Nomor WhatsApp sudah digunakan.',
            'gender.required' => 'Gender wajib dipilih.',
            'gender.in' => 'Gender tidak valid.',
            'date_of_birth.date' => 'Tanggal lahir tidak valid.',
            'date_of_birth.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'province_code.required' => 'Provinsi wajib dipilih.',
            'province_code.exists' => 'Provinsi tidak valid.',
            'regency_code.required' => 'Kabupaten/Kota wajib dipilih.',
            'regency_code.exists' => 'Kabupaten/Kota tidak sesuai dengan provinsi.',
            'district_code.required' => 'Kecamatan wajib dipilih.',
            'district_code.exists' => 'Kecamatan tidak sesuai dengan kabupaten/kota.',
            'village_code.required' => 'Kelurahan/Desa wajib dipilih.',
            'village_code.exists' => 'Kelurahan/Desa tidak sesuai dengan kecamatan.',
            'postal_code.max' => 'Kode pos maksimal 50 karakter.',
            'registration_source_id.required' => 'Store sumber registrasi wajib dipilih.',
            'registration_source_id.uuid' => 'Format ID store tidak valid.',
            'registration_source_id.exists' => 'Store yang dipilih tidak ditemukan.',
            'is_guest.boolean' => 'Status guest tidak valid.',
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'nama',
            'address' => 'alamat',
            'email' => 'email',
            'wa_number' => 'nomor WhatsApp',
            'gender' => 'gender',
            'date_of_birth' => 'tanggal lahir',
            'province_code' => 'provinsi',
            'regency_code' => 'kabupaten/kota',
            'district_code' => 'kecamatan',
            'village_code' => 'kelurahan/desa',
            'postal_code' => 'kode pos',
            'registration_source_id' => 'store',
        ];
    }

    private function normalizeCustomerName(mixed $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    private function isGuestName(mixed $name): bool
    {
        return mb_strtolower(
            $this->normalizeCustomerName($name),
            'UTF-8'
        ) === 'guest';
    }

    private function normalizePhone(mixed $phone): string
    {
        $digits = preg_replace(
            '/\D+/',
            '',
            (string) $phone
        ) ?? '';

        while (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        while (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits !== ''
            ? '+62'.$digits
            : '';
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
