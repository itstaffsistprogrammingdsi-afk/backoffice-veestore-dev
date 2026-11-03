<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Unit;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-units')
            ->only(['index']);

        $this->middleware('permission:create master-data-units')
            ->only(['store']);

        $this->middleware('permission:update master-data-units')
            ->only(['update']);

        $this->middleware('permission:delete master-data-units')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar unit.
     */
    public function index(Request $request)
    {
        $q = $this->normalizeSearch(
            $request->query('q')
        );

        $units = Unit::query()
            ->when($q !== '', function ($query) use ($q) {
                $driver = DB::connection()->getDriverName();

                if ($driver === 'pgsql') {
                    $query->where(
                        'name',
                        'ILIKE',
                        '%'.$q.'%'
                    );
                } else {
                    $query->whereRaw(
                        'LOWER(TRIM(name)) LIKE ?',
                        [
                            '%'.
                            mb_strtolower($q, 'UTF-8').
                            '%',
                        ]
                    );
                }
            })
            ->orderByRaw('LOWER(TRIM(name)) ASC')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Units',
            'eyebrow' => 'Master Produk / Unit',
            'description' => 'Kelola satuan produk.',
            'searchPlaceholder' => 'Cari unit...',
            'items' => $units,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Unit', 'required' => true],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.units.index'),
                'store' => route('master-data.units.store'),
                'update' => route('master-data.units.update', ['unit' => '__ID__']),
                'destroy' => route('master-data.units.destroy', ['unit' => '__ID__']),
            ],
        ]);
    }

    /**
     * Menyimpan unit baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedName = $this->normalizeName(
            $request->input('name')
        );

        $request->merge([
            'name' => $normalizedName,
            '_from' => 'create',
        ]);

        try {
            $validated = $request->validateWithBag(
                'createUnit',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:50',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ): void {
                            if (
                                $this->unitNameExists(
                                    (string) $value
                                )
                            ) {
                                $fail(
                                    'Nama unit sudah digunakan.'
                                );
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Nama unit wajib diisi.',

                    'name.string' => 'Nama unit harus berupa teks.',

                    'name.max' => 'Nama unit maksimal 50 karakter.',
                ]
            );

            DB::transaction(function () use ($validated): void {
                $name = $this->normalizeName(
                    $validated['name']
                );

                $this->lockNormalizedName($name);

                if ($this->unitNameExists($name)) {
                    throw ValidationException::withMessages([
                        'name' => 'Nama unit sudah digunakan.',
                    ]);
                }

                Unit::create([
                    'name' => $name,
                ]);
            });

            return redirect()
                ->route('master-data.units.index')
                ->with(
                    'success',
                    'Unit berhasil ditambahkan.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'createUnit';
            }

            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput([
                        'name' => $normalizedName,
                        '_from' => 'create',
                    ])
                    ->withErrors(
                        [
                            'name' => 'Nama unit sudah digunakan.',
                        ],
                        'createUnit'
                    );
            }

            report($exception);

            return back()
                ->withInput([
                    'name' => $normalizedName,
                    '_from' => 'create',
                ])
                ->withErrors(
                    [
                        'name' => 'Unit gagal disimpan. Silakan coba kembali.',
                    ],
                    'createUnit'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput([
                    'name' => $normalizedName,
                    '_from' => 'create',
                ])
                ->withErrors(
                    [
                        'name' => 'Unit gagal disimpan. Silakan coba kembali.',
                    ],
                    'createUnit'
                );
        }
    }

    /**
     * Memperbarui unit.
     */
    public function update(
        Request $request,
        Unit $unit
    ): RedirectResponse {
        $normalizedName = $this->normalizeName(
            $request->input('name')
        );

        $request->merge([
            'name' => $normalizedName,
            '_from' => 'edit',
            'id' => $unit->getKey(),
        ]);

        try {
            $validated = $request->validateWithBag(
                'editUnit',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:50',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($unit): void {
                            if (
                                $this->unitNameExists(
                                    (string) $value,
                                    (string) $unit->getKey()
                                )
                            ) {
                                $fail(
                                    'Nama unit sudah digunakan.'
                                );
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Nama unit wajib diisi.',

                    'name.string' => 'Nama unit harus berupa teks.',

                    'name.max' => 'Nama unit maksimal 50 karakter.',
                ]
            );

            DB::transaction(
                function () use ($unit, $validated): void {
                    $name = $this->normalizeName(
                        $validated['name']
                    );

                    $this->lockNormalizedName($name);

                    $lockedUnit = Unit::query()
                        ->whereKey($unit->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $this->unitNameExists(
                            $name,
                            (string) $lockedUnit->getKey()
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'name' => 'Nama unit sudah digunakan.',
                        ]);
                    }

                    $lockedUnit->update([
                        'name' => $name,
                    ]);
                }
            );

            return redirect()
                ->route('master-data.units.index')
                ->with(
                    'success',
                    'Unit berhasil diperbarui.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'editUnit';
            }

            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput([
                        'name' => $normalizedName,
                        '_from' => 'edit',
                        'id' => $unit->getKey(),
                        'update_url' => route(
                            'master-data.units.update',
                            $unit->getKey()
                        ),
                    ])
                    ->withErrors(
                        [
                            'name' => 'Nama unit sudah digunakan.',
                        ],
                        'editUnit'
                    );
            }

            report($exception);

            return back()
                ->withInput([
                    'name' => $normalizedName,
                    '_from' => 'edit',
                    'id' => $unit->getKey(),
                    'update_url' => route(
                        'master-data.units.update',
                        $unit->getKey()
                    ),
                ])
                ->withErrors(
                    [
                        'name' => 'Unit gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editUnit'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput([
                    'name' => $normalizedName,
                    '_from' => 'edit',
                    'id' => $unit->getKey(),
                    'update_url' => route(
                        'master-data.units.update',
                        $unit->getKey()
                    ),
                ])
                ->withErrors(
                    [
                        'name' => 'Unit gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editUnit'
                );
        }
    }

    /**
     * Menghapus unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        try {
            /*
             * Periksa dahulu apakah unit digunakan pada produk.
             */
            $usedProductCount = Product::query()
                ->where('unit_id', $unit->getKey())
                ->count();

            if ($usedProductCount > 0) {
                return back()->with(
                    'error',
                    sprintf(
                        'Unit "%s" tidak dapat dihapus karena masih digunakan oleh %d produk.',
                        $unit->name,
                        $usedProductCount
                    )
                );
            }

            DB::transaction(function () use ($unit): void {
                $lockedUnit = Unit::query()
                    ->whereKey($unit->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Periksa ulang setelah unit dikunci.
                 */
                $isUsed = Product::query()
                    ->where('unit_id', $lockedUnit->getKey())
                    ->exists();

                if ($isUsed) {
                    throw ValidationException::withMessages([
                        'delete' => 'Unit masih digunakan oleh produk dan tidak dapat dihapus.',
                    ]);
                }

                $lockedUnit->delete();
            });

            return back()->with(
                'success',
                'Unit berhasil dihapus.'
            );
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                $exception->validator
                    ->errors()
                    ->first('delete')
                    ?: 'Unit masih digunakan dan tidak dapat dihapus.'
            );
        } catch (QueryException $exception) {
            report($exception);

            return back()->with(
                'error',
                sprintf(
                    'Unit "%s" tidak dapat dihapus karena masih digunakan pada data lain.',
                    $unit->name
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Unit gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Memeriksa duplikasi nama unit tanpa membedakan kapital.
     */
    private function unitNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = $this->normalizeName($name);

        return Unit::query()
            ->when(
                $ignoreId !== null,
                function ($query) use ($ignoreId) {
                    $query->whereKeyNot($ignoreId);
                }
            )
            ->whereRaw(
                'LOWER(TRIM(name)) = ?',
                [$normalizedName]
            )
            ->exists();
    }

    /**
     * Mengunci nama unit pada PostgreSQL selama transaksi.
     */
    private function lockNormalizedName(string $name): void
    {
        $normalizedName = $this->normalizeName($name);
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                ['unit:'.$normalizedName]
            );
        }
    }

    /**
     * Normalisasi nama unit.
     */
    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        $name = preg_replace(
            '/\s+/u',
            ' ',
            $name
        ) ?? $name;

        return mb_strtolower(
            $name,
            'UTF-8'
        );
    }

    /**
     * Memeriksa apakah QueryException berasal dari unique constraint.
     */
    private function isUniqueConstraintViolation(
        QueryException $exception
    ): bool {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array(
            $sqlState,
            [
                '23000', // MySQL/MariaDB integrity constraint
                '23505', // PostgreSQL unique violation
            ],
            true
        );
    }

    private function normalizeSearch(?string $value): string
    {
        $value = trim((string) $value);

        return preg_replace(
            '/\s+/u',
            ' ',
            $value
        ) ?? $value;
    }
}
