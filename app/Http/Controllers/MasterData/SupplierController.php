<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Supplier;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-suppliers')
            ->only(['index']);

        $this->middleware('permission:create master-data-suppliers')
            ->only(['store']);

        $this->middleware('permission:update master-data-suppliers')
            ->only(['update']);

        $this->middleware('permission:delete master-data-suppliers')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar supplier.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $suppliers = Supplier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('phone', 'ILIKE', "%{$q}%")
                        ->orWhere('country', 'ILIKE', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Data negara untuk dropdown Create dan Edit Supplier.
        $countries = Country::query()
            ->select(['id', 'country_name', 'iso2', 'iso3', 'phone_code'])
            ->orderBy('country_name')
            ->get();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Suppliers',
            'description' => 'Kelola data supplier dan kontak.',
            'searchPlaceholder' => 'Cari supplier...',
            'items' => $suppliers,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'country', 'label' => 'Negara'],
                ['key' => 'phone', 'label' => 'Telepon'],
                ['key' => 'address', 'label' => 'Alamat'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Supplier', 'required' => true],
                ['key' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['key' => 'country', 'label' => 'Negara', 'type' => 'select', 'required' => true, 'options' => $countries->map(fn ($country) => ['value' => $country->country_name, 'label' => $country->country_name])->values()->all()],
                ['key' => 'phone', 'label' => 'Telepon'],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.suppliers.index'),
                'store' => route('master-data.suppliers.store'),
                'update' => route('master-data.suppliers.update', ['supplier' => '__ID__']),
                'destroy' => route('master-data.suppliers.destroy', ['supplier' => '__ID__']),
            ],
        ]);
    }

    /**
     * Menyimpan supplier baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedName = $this->normalizeSupplierName(
            $request->input('name')
        );

        $request->merge([
            'name' => $normalizedName,
            'phone' => $this->normalizePhoneLocalID(
                $request->input('phone')
            ),
            '_from' => 'create',
        ]);

        try {
            $data = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ): void {
                        if ($this->supplierNameExists((string) $value)) {
                            $fail('Data Supplier sudah digunakan.');
                        }
                    },
                ],
                'address' => ['nullable', 'string'],
                'country' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::exists('countries', 'country_name'),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:30',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ): void {
                        if (
                            $value !== null &&
                            $this->supplierPhoneExists((string) $value)
                        ) {
                            $fail('Nomor telepon sudah digunakan.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Nama supplier wajib diisi.',
                'name.string' => 'Nama supplier harus berupa teks.',
                'name.max' => 'Nama supplier maksimal 255 karakter.',
                'address.string' => 'Alamat harus berupa teks.',
                'country.required' => 'Negara wajib diisi.',
                'country.string' => 'Negara harus berupa teks.',
                'country.max' => 'Negara maksimal 100 karakter.',
                'country.exists' => 'Negara yang dipilih tidak tersedia.',
                'phone.string' => 'Nomor telepon harus berupa teks.',
                'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            ]);

            DB::transaction(function () use ($data): void {
                $name = $this->normalizeSupplierName($data['name']);

                $phone = $this->normalizePhoneLocalID(
                    $data['phone'] ?? null
                );

                $this->lockNormalizedValue('name', $name);

                if ($phone !== null) {
                    $this->lockNormalizedValue('phone', $phone);
                }

                if ($this->supplierNameExists($name)) {
                    throw ValidationException::withMessages([
                        'name' => 'Data Supplier sudah digunakan.',
                    ]);
                }

                if (
                    $phone !== null &&
                    $this->supplierPhoneExists($phone)
                ) {
                    throw ValidationException::withMessages([
                        'phone' => 'Nomor telepon sudah digunakan.',
                    ]);
                }

                $data['name'] = $name;
                $data['phone'] = $phone;
                $data['country'] = $this->normalizeCountry(
                    $data['country']
                );

                Supplier::create($data);
            });

            return $this->redirectIndex()
                ->with('success', 'Supplier berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'name' => 'Nama supplier atau nomor telepon sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Supplier gagal disimpan. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Supplier gagal disimpan. Silakan coba kembali.',
                ]);
        }
    }

    /**
     * Memperbarui supplier.
     */
    public function update(
        Request $request,
        Supplier $supplier
    ): RedirectResponse {
        $normalizedName = $this->normalizeSupplierName(
            $request->input('name')
        );

        $request->merge([
            'name' => $normalizedName,
            'phone' => $this->normalizePhoneLocalID(
                $request->input('phone')
            ),
            '_from' => 'edit',
            'id' => $supplier->getKey(),
            'update_url' => route(
                'master-data.suppliers.update',
                $supplier->getKey()
            ),
        ]);

        try {
            $data = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ) use ($supplier): void {
                        if (
                            $this->supplierNameExists(
                                (string) $value,
                                (string) $supplier->getKey()
                            )
                        ) {
                            $fail('Data Supplier sudah digunakan.');
                        }
                    },
                ],
                'address' => ['nullable', 'string'],
                'country' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::exists('countries', 'country_name'),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:30',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ) use ($supplier): void {
                        if (
                            $value !== null &&
                            $this->supplierPhoneExists(
                                (string) $value,
                                (string) $supplier->getKey()
                            )
                        ) {
                            $fail('Nomor telepon sudah digunakan.');
                        }
                    },
                ],
            ], [
                'name.required' => 'Nama supplier wajib diisi.',
                'name.string' => 'Nama supplier harus berupa teks.',
                'name.max' => 'Nama supplier maksimal 255 karakter.',
                'address.string' => 'Alamat harus berupa teks.',
                'country.required' => 'Negara wajib diisi.',
                'country.string' => 'Negara harus berupa teks.',
                'country.max' => 'Negara maksimal 100 karakter.',
                'country.exists' => 'Negara yang dipilih tidak tersedia.',
                'phone.string' => 'Nomor telepon harus berupa teks.',
                'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            ]);

            DB::transaction(function () use ($data, $supplier): void {
                $name = $this->normalizeSupplierName($data['name']);

                $phone = $this->normalizePhoneLocalID(
                    $data['phone'] ?? null
                );

                $this->lockNormalizedValue('name', $name);

                if ($phone !== null) {
                    $this->lockNormalizedValue('phone', $phone);
                }

                $lockedSupplier = Supplier::query()
                    ->whereKey($supplier->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $this->supplierNameExists(
                        $name,
                        (string) $lockedSupplier->getKey()
                    )
                ) {
                    throw ValidationException::withMessages([
                        'name' => 'Data Supplier sudah digunakan.',
                    ]);
                }

                if (
                    $phone !== null &&
                    $this->supplierPhoneExists(
                        $phone,
                        (string) $lockedSupplier->getKey()
                    )
                ) {
                    throw ValidationException::withMessages([
                        'phone' => 'Nomor telepon sudah digunakan.',
                    ]);
                }

                $data['name'] = $name;
                $data['phone'] = $phone;
                $data['country'] = $this->normalizeCountry(
                    $data['country']
                );

                $lockedSupplier->update($data);
            });

            return $this->redirectIndex()
                ->with('success', 'Supplier berhasil diperbarui.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'name' => 'Nama supplier atau nomor telepon sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Supplier gagal diperbarui. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Supplier gagal diperbarui. Silakan coba kembali.',
                ]);
        }
    }

    /**
     * Menghapus supplier.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $supplier->delete();

            return back()->with(
                'success',
                'Supplier berhasil dihapus.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Supplier gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Redirect ke halaman index supplier.
     */
    private function redirectIndex(): RedirectResponse
    {
        if (app('router')->has('suppliers.index')) {
            return redirect()->route('suppliers.index');
        }

        if (app('router')->has('master-data.suppliers.index')) {
            return redirect()->route('master-data.suppliers.index');
        }

        return redirect(url('master-data/suppliers'));
    }

    /**
     * Memeriksa nama supplier tanpa membedakan kapital
     * serta mengabaikan spasi berlebih.
     */
    private function supplierNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = $this->normalizeSupplierName($name);

        return Supplier::query()
            ->when(
                $ignoreId !== null,
                function ($query) use ($ignoreId) {
                    $query->whereKeyNot($ignoreId);
                }
            )
            ->whereRaw(
                "UPPER(REGEXP_REPLACE(TRIM(name), '\\s+', ' ', 'g')) = ?",
                [$normalizedName]
            )
            ->exists();
    }

    /**
     * Memeriksa nomor telepon supplier setelah dinormalisasi.
     * Format 08..., 62..., dan +62... dianggap sebagai nomor yang sama.
     */
    private function supplierPhoneExists(
        string $phone,
        ?string $ignoreId = null
    ): bool {
        $normalizedPhone = $this->normalizePhoneLocalID($phone);

        if ($normalizedPhone === null) {
            return false;
        }

        return Supplier::query()
            ->when(
                $ignoreId !== null,
                function ($query) use ($ignoreId) {
                    $query->whereKeyNot($ignoreId);
                }
            )
            ->whereRaw(
                "REGEXP_REPLACE(phone, '[^0-9]', '', 'g') IN (?, ?, ?)",
                [
                    $normalizedPhone,
                    '0'.$normalizedPhone,
                    '62'.$normalizedPhone,
                ]
            )
            ->exists();
    }

    /**
     * Mengunci nilai unik selama transaksi untuk mencegah duplikasi
     * dari dua request yang masuk secara bersamaan pada PostgreSQL.
     */
    private function lockNormalizedValue(
        string $field,
        string $value
    ): void {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ['supplier:'.$field.':'.$value]
        );
    }

    /**
     * Normalisasi nama supplier.
     */
    private function normalizeSupplierName(?string $name): string
    {
        $name = trim((string) $name);

        $name = preg_replace(
            '/\s+/u',
            ' ',
            $name
        ) ?? $name;

        return mb_strtoupper($name, 'UTF-8');
    }

    /**
     * Normalisasi negara.
     */
    private function normalizeCountry(?string $country): string
    {
        $country = trim((string) $country);

        return preg_replace(
            '/\s+/u',
            ' ',
            $country
        ) ?? $country;
    }

    /**
     * Normalisasi local part Indonesia:
     * 08123, +628123, 62 8123, 021234
     * menjadi 8123, 8123, 8123, 21234.
     */
    private function normalizePhoneLocalID(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        while (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        while (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits === ''
            ? null
            : $digits;
    }

    /**
     * Memeriksa pelanggaran unique constraint database.
     */
    private function isUniqueConstraintViolation(
        QueryException $exception
    ): bool {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array(
            $sqlState,
            [
                '23000',
                '23505',
            ],
            true
        );
    }
}
