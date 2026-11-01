<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Store;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class PromoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-promos')
            ->only(['index']);

        $this->middleware('permission:create master-data-promos')
            ->only(['store']);

        $this->middleware('permission:update master-data-promos')
            ->only(['update']);

        $this->middleware('permission:delete master-data-promos')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar promo.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $promos = Promo::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('code', 'ILIKE', "%{$q}%");
                });
            })
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        $allStoreIds = [];

        foreach ($promos as $promo) {
            if (! empty($promo->all_store)) {
                continue;
            }

            foreach (($promo->store_id ?? []) as $storeId) {
                $allStoreIds[$storeId] = true;
            }
        }

        $storeMap = [];

        if ($allStoreIds !== []) {
            $storeMap = Store::query()
                ->whereIn('id', array_keys($allStoreIds))
                ->pluck('name', 'id')
                ->toArray();
        }

        $stores = Store::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Promos',
            'description' => 'Kelola promo dan cabang yang berlaku.',
            'searchPlaceholder' => 'Cari promo...',
            'items' => $promos,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'start_date', 'label' => 'Mulai'],
                ['key' => 'end_date', 'label' => 'Selesai'],
                ['key' => 'all_store', 'label' => 'Semua Store'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Promo', 'required' => true],
                ['key' => 'code', 'label' => 'Kode Promo', 'required' => true],
                ['key' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['key' => 'start_date', 'label' => 'Mulai', 'type' => 'date', 'required' => true],
                ['key' => 'end_date', 'label' => 'Selesai', 'type' => 'date', 'required' => true],
                ['key' => 'all_store', 'label' => 'Semua Store', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
                ['key' => 'store_id', 'label' => 'Stores', 'type' => 'multiselect', 'options' => $stores->map(fn ($store) => ['value' => $store->id, 'label' => $store->name])->values()->all()],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.promos.index'),
                'store' => route('master-data.promos.store'),
                'update' => route('master-data.promos.update', ['promo' => '__ID__']),
                'destroy' => route('master-data.promos.destroy', ['promo' => '__ID__']),
            ],
        ]);
    }

    /**
     * Menyimpan promo baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedName = $this->normalizeName(
            $request->input('name')
        );

        $normalizedCode = $this->normalizeCode(
            $request->input('code')
        );

        $request->merge([
            'name' => $normalizedName,
            'code' => $normalizedCode,
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
                        if ($this->promoNameExists((string) $value)) {
                            $fail('Nama promo sudah digunakan.');
                        }
                    },
                ],
                'code' => [
                    'required',
                    'string',
                    'max:100',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ): void {
                        if ($this->promoCodeExists((string) $value)) {
                            $fail('Kode promo sudah digunakan.');
                        }
                    },
                ],
                'start_date' => ['required', 'date'],
                'end_date' => [
                    'required',
                    'date',
                    'after_or_equal:start_date',
                ],
                'description' => ['nullable', 'string'],
                'all_store' => ['nullable', 'boolean'],
                'store_id' => ['nullable', 'array'],
                'store_id.*' => ['uuid', 'exists:stores,id'],
            ], [
                'name.required' => 'Nama promo wajib diisi.',
                'name.string' => 'Nama promo harus berupa teks.',
                'name.max' => 'Nama promo maksimal 255 karakter.',
                'code.required' => 'Kode promo wajib diisi.',
                'code.string' => 'Kode promo harus berupa teks.',
                'code.max' => 'Kode promo maksimal 100 karakter.',
                'start_date.required' => 'Start date wajib diisi.',
                'start_date.date' => 'Format start date tidak valid.',
                'end_date.required' => 'End date wajib diisi.',
                'end_date.date' => 'Format end date tidak valid.',
                'end_date.after_or_equal' => 'End date tidak boleh sebelum start date.',
                'description.string' => 'Deskripsi harus berupa teks.',
                'store_id.array' => 'Pilihan toko harus berupa daftar.',
                'store_id.*.uuid' => 'Format ID toko tidak valid.',
                'store_id.*.exists' => 'Toko yang dipilih tidak ditemukan.',
            ]);

            $allStore = $request->boolean('all_store');

            $storeIds = $this->sanitizeStoreIds(
                $request->input('store_id', [])
            );

            DB::transaction(
                function () use (
                    $data,
                    $allStore,
                    $storeIds
                ): void {
                    $name = $this->normalizeName($data['name']);
                    $code = $this->normalizeCode($data['code']);

                    $this->lockPromoIdentity($name, $code);

                    if ($this->promoNameExists($name)) {
                        throw ValidationException::withMessages([
                            'name' => 'Nama promo sudah digunakan.',
                        ]);
                    }

                    if ($this->promoCodeExists($code)) {
                        throw ValidationException::withMessages([
                            'code' => 'Kode promo sudah digunakan.',
                        ]);
                    }

                    Promo::create([
                        'name' => $name,
                        'code' => $code,
                        /*
                         * Kolom description pada database menggunakan
                         * NOT NULL. Jika form dikosongkan, simpan string
                         * kosong agar tidak melanggar constraint.
                         */
                        'description' => trim(
                            (string) ($data['description'] ?? '')
                        ),
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'all_store' => $allStore,
                        /*
                         * Jika Semua Cabang aktif, store_id disimpan null.
                         * Jika toko tidak dipilih, simpan array kosong agar
                         * kolom array/JSON PostgreSQL tidak gagal menerima null.
                         */
                        'store_id' => $allStore
                                ? null
                                : $storeIds,
                    ]);
                }
            );

            return redirect()
                ->route('master-data.promos.index')
                ->with('success', 'Promo berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'code' => 'Nama atau kode promo sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Promo gagal disimpan. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Promo gagal disimpan. Silakan coba kembali.',
                ]);
        }
    }

    /**
     * Memperbarui promo.
     */
    public function update(
        Request $request,
        Promo $promo
    ): RedirectResponse {
        $normalizedName = $this->normalizeName(
            $request->input('name')
        );

        $normalizedCode = $this->normalizeCode(
            $request->input('code')
        );

        $request->merge([
            'name' => $normalizedName,
            'code' => $normalizedCode,
            '_from' => 'edit',
            'id' => $promo->getKey(),
            'update_url' => route(
                'master-data.promos.update',
                $promo->getKey()
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
                    ) use ($promo): void {
                        if (
                            $this->promoNameExists(
                                (string) $value,
                                (string) $promo->getKey()
                            )
                        ) {
                            $fail('Nama promo sudah digunakan.');
                        }
                    },
                ],
                'code' => [
                    'required',
                    'string',
                    'max:100',
                    function (
                        string $attribute,
                        mixed $value,
                        Closure $fail
                    ) use ($promo): void {
                        if (
                            $this->promoCodeExists(
                                (string) $value,
                                (string) $promo->getKey()
                            )
                        ) {
                            $fail('Kode promo sudah digunakan.');
                        }
                    },
                ],
                'start_date' => ['required', 'date'],
                'end_date' => [
                    'required',
                    'date',
                    'after_or_equal:start_date',
                ],
                'description' => ['nullable', 'string'],
                'all_store' => ['nullable', 'boolean'],
                'store_id' => ['nullable', 'array'],
                'store_id.*' => ['uuid', 'exists:stores,id'],
            ], [
                'name.required' => 'Nama promo wajib diisi.',
                'name.string' => 'Nama promo harus berupa teks.',
                'name.max' => 'Nama promo maksimal 255 karakter.',
                'code.required' => 'Kode promo wajib diisi.',
                'code.string' => 'Kode promo harus berupa teks.',
                'code.max' => 'Kode promo maksimal 100 karakter.',
                'start_date.required' => 'Start date wajib diisi.',
                'start_date.date' => 'Format start date tidak valid.',
                'end_date.required' => 'End date wajib diisi.',
                'end_date.date' => 'Format end date tidak valid.',
                'end_date.after_or_equal' => 'End date tidak boleh sebelum start date.',
                'description.string' => 'Deskripsi harus berupa teks.',
                'store_id.array' => 'Pilihan toko harus berupa daftar.',
                'store_id.*.uuid' => 'Format ID toko tidak valid.',
                'store_id.*.exists' => 'Toko yang dipilih tidak ditemukan.',
            ]);

            $allStore = $request->boolean('all_store');

            $storeIds = $this->sanitizeStoreIds(
                $request->input('store_id', [])
            );

            DB::transaction(
                function () use (
                    $data,
                    $allStore,
                    $storeIds,
                    $promo
                ): void {
                    $name = $this->normalizeName($data['name']);
                    $code = $this->normalizeCode($data['code']);

                    $this->lockPromoIdentity($name, $code);

                    $lockedPromo = Promo::query()
                        ->whereKey($promo->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $this->promoNameExists(
                            $name,
                            (string) $lockedPromo->getKey()
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'name' => 'Nama promo sudah digunakan.',
                        ]);
                    }

                    if (
                        $this->promoCodeExists(
                            $code,
                            (string) $lockedPromo->getKey()
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'code' => 'Kode promo sudah digunakan.',
                        ]);
                    }

                    $lockedPromo->update([
                        'name' => $name,
                        'code' => $code,
                        /*
                         * Kolom description pada database menggunakan
                         * NOT NULL. Jika form dikosongkan, simpan string
                         * kosong agar tidak melanggar constraint.
                         */
                        'description' => trim(
                            (string) ($data['description'] ?? '')
                        ),
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'all_store' => $allStore,
                        /*
                         * Jika Semua Cabang aktif, store_id disimpan null.
                         * Jika toko tidak dipilih, simpan array kosong agar
                         * kolom array/JSON PostgreSQL tidak gagal menerima null.
                         */
                        'store_id' => $allStore
                                ? null
                                : $storeIds,
                    ]);
                }
            );

            return redirect()
                ->route('master-data.promos.index')
                ->with('success', 'Promo berhasil diperbarui.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'code' => 'Nama atau kode promo sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Promo gagal diperbarui. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' => 'Promo gagal diperbarui. Silakan coba kembali.',
                ]);
        }
    }

    /**
     * Menghapus promo.
     */
    public function destroy(Promo $promo): RedirectResponse
    {
        try {
            $promo->delete();

            return back()->with(
                'success',
                'Promo berhasil dihapus.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Promo gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Memeriksa nama promo tanpa membedakan kapital dan spasi.
     */
    private function promoNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = $this->normalizeName($name);

        return Promo::query()
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
     * Memeriksa kode promo tanpa membedakan kapital dan spasi.
     */
    private function promoCodeExists(
        string $code,
        ?string $ignoreId = null
    ): bool {
        $normalizedCode = $this->normalizeCode($code);

        return Promo::query()
            ->when(
                $ignoreId !== null,
                function ($query) use ($ignoreId) {
                    $query->whereKeyNot($ignoreId);
                }
            )
            ->whereRaw(
                'UPPER(TRIM(code)) = ?',
                [$normalizedCode]
            )
            ->exists();
    }

    /**
     * Mengunci kombinasi nama dan kode promo selama transaksi.
     */
    private function lockPromoIdentity(
        string $name,
        string $code
    ): void {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            [
                'promo:'.
                $this->normalizeName($name).
                ':'.
                $this->normalizeCode($code),
            ]
        );
    }

    /**
     * Normalisasi nama promo.
     */
    private function normalizeName(?string $name): string
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
     * Normalisasi kode promo.
     */
    private function normalizeCode(?string $code): string
    {
        $code = trim((string) $code);

        $code = preg_replace(
            '/\s+/u',
            '',
            $code
        ) ?? $code;

        return mb_strtoupper($code, 'UTF-8');
    }

    /**
     * Membersihkan daftar ID toko.
     */
    private function sanitizeStoreIds(mixed $storeIds): array
    {
        if (! is_array($storeIds)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ($value) => trim((string) $value),
                        $storeIds
                    ),
                    static fn ($value) => $value !== ''
                )
            )
        );
    }

    /**
     * Memeriksa unique constraint database.
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
