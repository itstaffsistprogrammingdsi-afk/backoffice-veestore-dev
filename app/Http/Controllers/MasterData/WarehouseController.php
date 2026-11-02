<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-warehouses')
            ->only(['index']);

        $this->middleware('permission:create master-data-warehouses')
            ->only(['store']);

        $this->middleware('permission:update master-data-warehouses')
            ->only(['update']);

        $this->middleware('permission:delete master-data-warehouses')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar warehouse dan pencarian.
     */
    public function index(Request $request)
    {
        $q = $this->normalizeText(
            $request->query('q')
        );

        $warehouses = Warehouse::query()
            ->select('warehouses.*')
            ->selectRaw(
                'ST_Y(geom) AS lat, ST_X(geom) AS lng'
            )
            ->when($q !== '', function ($query) use ($q) {
                $driver = DB::connection()->getDriverName();

                $query->where(function ($subQuery) use ($q, $driver) {
                    if ($driver === 'pgsql') {
                        $subQuery
                            ->where('name', 'ILIKE', '%'.$q.'%')
                            ->orWhere('code', 'ILIKE', '%'.$q.'%')
                            ->orWhere('address', 'ILIKE', '%'.$q.'%')
                            ->orWhere('postal_code', 'ILIKE', '%'.$q.'%')
                            ->orWhere('contact_name', 'ILIKE', '%'.$q.'%')
                            ->orWhere('contact_phone', 'ILIKE', '%'.$q.'%')
                            ->orWhere('contact_email', 'ILIKE', '%'.$q.'%');

                        return;
                    }

                    $search =
                        '%'.
                        mb_strtolower($q, 'UTF-8').
                        '%';

                    $subQuery
                        ->whereRaw(
                            'LOWER(name) LIKE ?',
                            [$search]
                        )
                        ->orWhereRaw(
                            'LOWER(code) LIKE ?',
                            [$search]
                        )
                        ->orWhereRaw(
                            'LOWER(address) LIKE ?',
                            [$search]
                        )
                        ->orWhereRaw(
                            'LOWER(postal_code) LIKE ?',
                            [$search]
                        );
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Warehouses',
            'description' => 'Kelola gudang, kontak, dan lokasi operasional.',
            'searchPlaceholder' => 'Cari warehouse...',
            'items' => $warehouses,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'contact_name', 'label' => 'Kontak'],
                ['key' => 'contact_phone', 'label' => 'Telepon'],
                ['key' => 'address', 'label' => 'Alamat'],
                ['key' => 'postal_code', 'label' => 'Kode Pos'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Warehouse', 'required' => true],
                ['key' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['key' => 'postal_code', 'label' => 'Kode Pos'],
                ['key' => 'contact_name', 'label' => 'Nama Kontak', 'required' => true],
                ['key' => 'contact_phone', 'label' => 'Telepon Kontak', 'required' => true],
                ['key' => 'contact_email', 'label' => 'Email Kontak'],
                ['key' => 'location', 'label' => 'Lokasi Warehouse', 'type' => 'map', 'latitudeKey' => 'lat', 'longitudeKey' => 'lng'],
                ['key' => 'lat', 'label' => 'Latitude', 'type' => 'number', 'placeholder' => 'Terisi otomatis dari peta'],
                ['key' => 'lng', 'label' => 'Longitude', 'type' => 'number', 'placeholder' => 'Terisi otomatis dari peta'],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.warehouses.index'),
                'store' => route('master-data.warehouses.store'),
                'update' => route('master-data.warehouses.update', ['warehouse' => '__ID__']),
                'destroy' => route('master-data.warehouses.destroy', ['warehouse' => '__ID__']),
            ],
        ]);
    }

    /**
     * Menyimpan warehouse baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedName = $this->normalizeText(
            $request->input('name')
        );

        $normalizedAddress = $this->normalizeNullableAddress(
            $request->input('address')
        );

        $request->merge([
            'name' => $normalizedName,
            'address' => $normalizedAddress,
            'postal_code' => $this->normalizePostalCode(
                $request->input('postal_code')
            ),
            'contact_name' => $this->normalizeText($request->input('contact_name')),
            'contact_phone' => $this->normalizeText($request->input('contact_phone')),
            'contact_email' => $this->normalizeEmail($request->input('contact_email')),
            '_from' => 'create',
        ]);

        try {
            $data = $request->validateWithBag(
                'createWarehouse',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                    'address' => [
                        'nullable',
                        'string',
                        'max:1000',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ): void {
                            if (
                                $value !== null
                                && $value !== ''
                                && $this->addressExists(
                                    (string) $value
                                )
                            ) {
                                $fail(
                                    'Alamat tersebut sudah digunakan oleh warehouse lain.'
                                );
                            }
                        },
                    ],
                    'postal_code' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                    'contact_name' => ['required', 'string', 'max:255'],
                    'contact_phone' => ['required', 'string', 'max:255'],
                    'contact_email' => ['nullable', 'email', 'max:255'],
                    'lat' => [
                        'nullable',
                        'numeric',
                        'between:-90,90',
                        'required_with:lng',
                    ],
                    'lng' => [
                        'nullable',
                        'numeric',
                        'between:-180,180',
                        'required_with:lat',
                    ],
                ],
                [
                    'name.required' => 'Nama warehouse wajib diisi.',

                    'name.max' => 'Nama warehouse maksimal 255 karakter.',

                    'address.max' => 'Alamat warehouse terlalu panjang.',

                    'postal_code.max' => 'Kode pos maksimal 20 karakter.',

                    'contact_name.required' => 'Nama kontak wajib diisi.',
                    'contact_name.max' => 'Nama kontak maksimal 255 karakter.',
                    'contact_phone.required' => 'Nomor kontak wajib diisi.',
                    'contact_phone.max' => 'Nomor kontak maksimal 255 karakter.',
                    'contact_email.email' => 'Email kontak tidak valid.',
                    'contact_email.max' => 'Email kontak maksimal 255 karakter.',

                    'lat.numeric' => 'Latitude harus berupa angka.',

                    'lat.between' => 'Latitude harus berada antara -90 sampai 90.',

                    'lat.required_with' => 'Latitude wajib diisi ketika longitude tersedia.',

                    'lng.numeric' => 'Longitude harus berupa angka.',

                    'lng.between' => 'Longitude harus berada antara -180 sampai 180.',

                    'lng.required_with' => 'Longitude wajib diisi ketika latitude tersedia.',
                ]
            );

            DB::transaction(function () use ($data): void {
                $normalizedAddress =
                    $this->normalizeNullableAddress(
                        $data['address'] ?? null
                    );

                /*
                 * Jika alamat diisi, kunci berdasarkan alamat
                 * agar dua request bersamaan tidak menyimpan
                 * alamat warehouse yang sama.
                 */
                if ($normalizedAddress !== null) {
                    $this->lockNormalizedAddress(
                        $normalizedAddress
                    );

                    if (
                        $this->addressExists(
                            $normalizedAddress
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'address' => 'Alamat tersebut sudah digunakan oleh warehouse lain.',
                        ]);
                    }
                }

                Warehouse::create(
                    $this->buildPayload($data)
                );
            });

            return $this->redirectIndex()
                ->with(
                    'success',
                    'Warehouse berhasil ditambahkan.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'createWarehouse';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' => 'Warehouse gagal disimpan. Silakan coba kembali.',
                    ],
                    'createWarehouse'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' => 'Warehouse gagal disimpan. Silakan coba kembali.',
                    ],
                    'createWarehouse'
                );
        }
    }

    /**
     * Memperbarui warehouse.
     */
    public function update(
        Request $request,
        Warehouse $warehouse
    ): RedirectResponse {
        $normalizedName = $this->normalizeText(
            $request->input('name')
        );

        $normalizedAddress = $this->normalizeNullableAddress(
            $request->input('address')
        );

        $request->merge([
            'name' => $normalizedName,
            'address' => $normalizedAddress,
            'postal_code' => $this->normalizePostalCode(
                $request->input('postal_code')
            ),
            'contact_name' => $this->normalizeText($request->input('contact_name')),
            'contact_phone' => $this->normalizeText($request->input('contact_phone')),
            'contact_email' => $this->normalizeEmail($request->input('contact_email')),
            '_from' => 'edit',
            'id' => $warehouse->getKey(),
        ]);

        try {
            $data = $request->validateWithBag(
                'editWarehouse',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                    'address' => [
                        'nullable',
                        'string',
                        'max:1000',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($warehouse): void {
                            if (
                                $value !== null
                                && $value !== ''
                                && $this->addressExists(
                                    (string) $value,
                                    (string) $warehouse->getKey()
                                )
                            ) {
                                $fail(
                                    'Alamat tersebut sudah digunakan oleh warehouse lain.'
                                );
                            }
                        },
                    ],
                    'postal_code' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                    'contact_name' => ['required', 'string', 'max:255'],
                    'contact_phone' => ['required', 'string', 'max:255'],
                    'contact_email' => ['nullable', 'email', 'max:255'],
                    'lat' => [
                        'nullable',
                        'numeric',
                        'between:-90,90',
                        'required_with:lng',
                    ],
                    'lng' => [
                        'nullable',
                        'numeric',
                        'between:-180,180',
                        'required_with:lat',
                    ],
                ],
                [
                    'name.required' => 'Nama warehouse wajib diisi.',

                    'name.max' => 'Nama warehouse maksimal 255 karakter.',

                    'address.max' => 'Alamat warehouse terlalu panjang.',

                    'postal_code.max' => 'Kode pos maksimal 20 karakter.',

                    'contact_name.required' => 'Nama kontak wajib diisi.',
                    'contact_name.max' => 'Nama kontak maksimal 255 karakter.',
                    'contact_phone.required' => 'Nomor kontak wajib diisi.',
                    'contact_phone.max' => 'Nomor kontak maksimal 255 karakter.',
                    'contact_email.email' => 'Email kontak tidak valid.',
                    'contact_email.max' => 'Email kontak maksimal 255 karakter.',

                    'lat.numeric' => 'Latitude harus berupa angka.',

                    'lat.between' => 'Latitude harus berada antara -90 sampai 90.',

                    'lat.required_with' => 'Latitude wajib diisi ketika longitude tersedia.',

                    'lng.numeric' => 'Longitude harus berupa angka.',

                    'lng.between' => 'Longitude harus berada antara -180 sampai 180.',

                    'lng.required_with' => 'Longitude wajib diisi ketika latitude tersedia.',
                ]
            );

            DB::transaction(function () use (
                $warehouse,
                $data
            ): void {
                $normalizedAddress =
                    $this->normalizeNullableAddress(
                        $data['address'] ?? null
                    );

                if ($normalizedAddress !== null) {
                    $this->lockNormalizedAddress(
                        $normalizedAddress
                    );
                }

                $lockedWarehouse = Warehouse::query()
                    ->whereKey($warehouse->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $normalizedAddress !== null
                    && $this->addressExists(
                        $normalizedAddress,
                        (string) $lockedWarehouse->getKey()
                    )
                ) {
                    throw ValidationException::withMessages([
                        'address' => 'Alamat tersebut sudah digunakan oleh warehouse lain.',
                    ]);
                }

                /*
                 * Kolom code tidak ikut diperbarui.
                 */
                $lockedWarehouse->update(
                    $this->buildPayload($data)
                );
            });

            return $this->redirectIndex()
                ->with(
                    'success',
                    'Warehouse berhasil diperbarui.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'editWarehouse';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' => 'Warehouse gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editWarehouse'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' => 'Warehouse gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editWarehouse'
                );
        }
    }

    /**
     * Menghapus warehouse.
     */
    public function destroy(
        Warehouse $warehouse
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($warehouse): void {
                $lockedWarehouse = Warehouse::query()
                    ->whereKey($warehouse->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedWarehouse->delete();
            });

            return back()->with(
                'success',
                'Warehouse berhasil dihapus.'
            );
        } catch (QueryException $exception) {
            report($exception);

            return back()->with(
                'error',
                sprintf(
                    'Warehouse "%s" tidak dapat dihapus karena masih digunakan pada data lain.',
                    $warehouse->name
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Warehouse gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Memeriksa alamat warehouse secara case-insensitive
     * dan mengabaikan perbedaan spasi.
     *
     * Contoh alamat yang dianggap sama:
     * - Jalan Sudirman No. 10
     * - jalan sudirman no. 10
     * - " Jalan   Sudirman No. 10 "
     */
    private function addressExists(
        string $address,
        ?string $ignoreId = null
    ): bool {
        $normalizedAddress =
            $this->normalizeNullableAddress($address);

        if ($normalizedAddress === null) {
            return false;
        }

        return Warehouse::query()
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->whereNotNull('address')
            ->whereRaw(
                "LOWER(
                    REGEXP_REPLACE(
                        TRIM(address),
                        '\\s+',
                        ' ',
                        'g'
                    )
                ) = ?",
                [
                    mb_strtolower(
                        $normalizedAddress,
                        'UTF-8'
                    ),
                ]
            )
            ->exists();
    }

    /**
     * Lock alamat selama transaksi PostgreSQL.
     * Tidak mengubah tabel, kolom, index, atau migration.
     */
    private function lockNormalizedAddress(
        string $address
    ): void {
        if (
            DB::connection()->getDriverName()
            !== 'pgsql'
        ) {
            return;
        }

        $normalizedAddress = mb_strtolower(
            $this->normalizeText($address),
            'UTF-8'
        );

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ['warehouse-address:'.$normalizedAddress]
        );
    }

    /**
     * Membentuk payload penyimpanan warehouse.
     */
    private function buildPayload(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'address' => $data['address'] ?: null,
            'postal_code' => $data['postal_code'] ?: null,
            'contact_name' => $data['contact_name'] ?: null,
            'contact_phone' => $data['contact_phone'] ?: null,
            'contact_email' => $data['contact_email'] ?: null,
            'geom' => null,
        ];

        if (
            array_key_exists('lat', $data)
            && array_key_exists('lng', $data)
            && $data['lat'] !== null
            && $data['lat'] !== ''
            && $data['lng'] !== null
            && $data['lng'] !== ''
        ) {
            $lat = (float) $data['lat'];
            $lng = (float) $data['lng'];

            $payload['geom'] = DB::raw(
                sprintf(
                    'ST_SetSRID(ST_MakePoint(%F, %F), 4326)',
                    $lng,
                    $lat
                )
            );
        }

        return $payload;
    }

    private function normalizeText(
        mixed $value
    ): string {
        $value = trim((string) $value);

        return preg_replace(
            '/\s+/u',
            ' ',
            $value
        ) ?? $value;
    }

    private function normalizeNullableAddress(
        mixed $value
    ): ?string {
        $value = $this->normalizeText($value);

        return $value === ''
            ? null
            : $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->normalizeText($value);

        return $email === '' ? null : mb_strtolower($email, 'UTF-8');
    }

    private function normalizePostalCode(
        mixed $value
    ): ?string {
        $postalCode = trim((string) $value);

        if ($postalCode === '') {
            return null;
        }

        return preg_replace(
            '/\s+/u',
            '',
            $postalCode
        ) ?? $postalCode;
    }

    private function redirectIndex(): RedirectResponse
    {
        if (
            app('router')->has(
                'master-data.warehouses.index'
            )
        ) {
            return redirect()->route(
                'master-data.warehouses.index'
            );
        }

        return redirect(
            url('master-data/warehouses')
        );
    }
}
