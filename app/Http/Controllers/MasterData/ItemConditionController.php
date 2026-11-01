<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ItemCondition;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ItemConditionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-item-conditions')
            ->only(['index']);

        $this->middleware('permission:create master-data-item-conditions')
            ->only(['store']);

        $this->middleware('permission:update master-data-item-conditions')
            ->only(['update']);

        $this->middleware('permission:delete master-data-item-conditions')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar item condition dan pencarian.
     */
    public function index(Request $request)
    {
        $q = $this->normalizeSearch($request->query('q'));

        $conditions = ItemCondition::query()
            ->when($q !== '', function ($query) use ($q) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $query->where('name', 'ILIKE', '%' . $q . '%');
                } else {
                    $query->whereRaw(
                        'LOWER(TRIM(name)) LIKE ?',
                        ['%' . mb_strtolower($q, 'UTF-8') . '%']
                    );
                }
            })
            ->orderByRaw('LOWER(TRIM(name)) ASC')
            ->paginate(10)
            ->withQueryString();

        $statuses = $this->getAllowedStatuses();

        return view(
            'content.master-data.item-conditions.index',
            compact('conditions', 'statuses', 'q')
        );
    }

    /**
     * Menyimpan item condition baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedStatuses = $this->getAllowedStatuses();

        if (empty($allowedStatuses)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Enum PostgreSQL item_condition_status tidak ditemukan atau belum memiliki nilai.'
                );
        }

        $name = $this->normalizeName($request->input('name'));
        $status = trim((string) $request->input('status', ''));

        if ($status === '') {
            $status = $this->getDefaultStatus($allowedStatuses);
        }

        $request->merge([
            'name' => $name,
            'status' => $status,
            '_from' => 'create',
        ]);

        try {
            $validated = $request->validateWithBag(
                'createCondition',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:150',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ): void {
                            if ($this->conditionNameExists((string) $value)) {
                                $fail('Nama kondisi sudah digunakan.');
                            }
                        },
                    ],
                    'status' => [
                        'required',
                        'string',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($allowedStatuses): void {
                            if (!in_array((string) $value, $allowedStatuses, true)) {
                                $fail('Status tidak sesuai dengan pilihan yang tersedia.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Nama kondisi wajib diisi.',
                    'name.string' => 'Nama kondisi harus berupa teks.',
                    'name.max' => 'Nama kondisi maksimal 150 karakter.',
                    'status.required' => 'Status wajib diisi.',
                    'status.string' => 'Status harus berupa teks.',
                ]
            );

            DB::transaction(function () use ($validated): void {
                $normalizedName = $this->normalizeName($validated['name']);

                /*
                 * Lock berdasarkan nama agar request bersamaan tidak membuat
                 * kondisi dengan nama yang sama.
                 */
                $this->lockNormalizedName($normalizedName);

                if ($this->conditionNameExists($normalizedName)) {
                    throw ValidationException::withMessages([
                        'name' => 'Nama kondisi sudah digunakan.',
                    ]);
                }

                ItemCondition::create([
                    'name' => $normalizedName,
                    'status' => $validated['status'],
                ]);
            });

            return redirect()
                ->route('master-data.item-conditions.index')
                ->with('success', 'Item Condition berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'createCondition';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    ['name' => 'Item Condition gagal disimpan. Silakan coba kembali.'],
                    'createCondition'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    ['name' => 'Item Condition gagal disimpan. Silakan coba kembali.'],
                    'createCondition'
                );
        }
    }

    /**
     * Memperbarui item condition.
     */
    public function update(
        Request $request,
        ItemCondition $item_condition
    ): RedirectResponse {
        if ($this->isDefaultCondition($item_condition)) {
            return back()->with(
                'error',
                'Kondisi "Good" merupakan data default dan tidak dapat diubah.'
            );
        }

        $allowedStatuses = $this->getAllowedStatuses();

        if (empty($allowedStatuses)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Enum PostgreSQL item_condition_status tidak ditemukan atau belum memiliki nilai.'
                );
        }

        $name = $this->normalizeName($request->input('name'));
        $status = trim((string) $request->input('status', ''));

        if ($status === '') {
            $status = !empty($item_condition->status)
                ? (string) $item_condition->status
                : $this->getDefaultStatus($allowedStatuses);
        }

        $request->merge([
            'name' => $name,
            'status' => $status,
            '_from' => 'edit',
            'id' => $item_condition->getKey(),
        ]);

        try {
            $validated = $request->validateWithBag(
                'editCondition',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:150',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($item_condition): void {
                            if (
                                $this->conditionNameExists(
                                    (string) $value,
                                    (string) $item_condition->getKey()
                                )
                            ) {
                                $fail('Nama kondisi sudah digunakan.');
                            }
                        },
                    ],
                    'status' => [
                        'required',
                        'string',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($allowedStatuses): void {
                            if (!in_array((string) $value, $allowedStatuses, true)) {
                                $fail('Status tidak sesuai dengan pilihan yang tersedia.');
                            }
                        },
                    ],
                ],
                [
                    'name.required' => 'Nama kondisi wajib diisi.',
                    'name.string' => 'Nama kondisi harus berupa teks.',
                    'name.max' => 'Nama kondisi maksimal 150 karakter.',
                    'status.required' => 'Status wajib diisi.',
                    'status.string' => 'Status harus berupa teks.',
                ]
            );

            DB::transaction(
                function () use (
                    $item_condition,
                    $validated
                ): void {
                    $normalizedName = $this->normalizeName($validated['name']);

                    $this->lockNormalizedName($normalizedName);

                    $lockedCondition = ItemCondition::query()
                        ->whereKey($item_condition->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($this->isDefaultCondition($lockedCondition)) {
                        throw ValidationException::withMessages([
                            'name' => 'Kondisi "Good" tidak dapat diubah.',
                        ]);
                    }

                    if (
                        $this->conditionNameExists(
                            $normalizedName,
                            (string) $lockedCondition->getKey()
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'name' => 'Nama kondisi sudah digunakan.',
                        ]);
                    }

                    $lockedCondition->update([
                        'name' => $normalizedName,
                        'status' => $validated['status'],
                    ]);
                }
            );

            return redirect()
                ->route('master-data.item-conditions.index')
                ->with('success', 'Item Condition berhasil diperbarui.');
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'editCondition';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    ['name' => 'Item Condition gagal diperbarui. Silakan coba kembali.'],
                    'editCondition'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    ['name' => 'Item Condition gagal diperbarui. Silakan coba kembali.'],
                    'editCondition'
                );
        }
    }

    /**
     * Menghapus item condition.
     */
    public function destroy(
        ItemCondition $item_condition
    ): RedirectResponse {
        if ($this->isDefaultCondition($item_condition)) {
            return back()->with(
                'error',
                'Kondisi "Good" merupakan data default dan tidak dapat dihapus.'
            );
        }

        try {
            DB::transaction(function () use ($item_condition): void {
                $lockedCondition = ItemCondition::query()
                    ->whereKey($item_condition->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->isDefaultCondition($lockedCondition)) {
                    throw new RuntimeException(
                        'Kondisi "Good" tidak dapat dihapus.'
                    );
                }

                $lockedCondition->delete();
            });

            return back()->with(
                'success',
                'Item Condition berhasil dihapus.'
            );
        } catch (QueryException $exception) {
            report($exception);

            if ($this->isForeignKeyViolation($exception)) {
                return back()->with(
                    'error',
                    sprintf(
                        'Item Condition "%s" tidak dapat dihapus karena masih digunakan pada data lain.',
                        $item_condition->name
                    )
                );
            }

            return back()->with(
                'error',
                'Item Condition gagal dihapus karena terjadi kesalahan database.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                $exception->getMessage() === 'Kondisi "Good" tidak dapat dihapus.'
                    ? $exception->getMessage()
                    : 'Item Condition gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Cek nama secara case-insensitive.
     */
    private function conditionNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = $this->normalizeName($name);

        return ItemCondition::query()
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->whereRaw(
                'LOWER(TRIM(name)) = ?',
                [mb_strtolower($normalizedName, 'UTF-8')]
            )
            ->exists();
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    private function normalizeSearch(?string $value): string
    {
        $value = trim((string) $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function isDefaultCondition(ItemCondition $condition): bool
    {
        return mb_strtolower(
            trim((string) $condition->name),
            'UTF-8'
        ) === 'good';
    }

    /**
     * Lock berdasarkan nama tanpa mengubah struktur database.
     */
    private function lockNormalizedName(string $name): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ['item-condition:' . mb_strtolower($name, 'UTF-8')]
        );
    }

    private function isForeignKeyViolation(
        QueryException $exception
    ): bool {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array($sqlState, ['23000', '23503'], true);
    }

    /**
     * Mengambil pilihan enum PostgreSQL item_condition_status.
     */
    private function getAllowedStatuses(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return ['available'];
        }

        return collect(DB::select("
            SELECT enumlabel
            FROM pg_enum
            WHERE enumtypid = (
                SELECT oid
                FROM pg_type
                WHERE typname = 'item_condition_status'
            )
            ORDER BY enumsortorder
        "))
            ->pluck('enumlabel')
            ->values()
            ->all();
    }

    /**
     * Mengambil status default dari database.
     */
    private function getDefaultStatus(
        array $allowedStatuses
    ): string {
        if (empty($allowedStatuses)) {
            throw new RuntimeException(
                'Enum item_condition_status tidak memiliki pilihan nilai.'
            );
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return $allowedStatuses[0];
        }

        $column = DB::selectOne("
            SELECT column_default
            FROM information_schema.columns
            WHERE table_schema = current_schema()
              AND table_name = 'item_conditions'
              AND column_name = 'status'
            LIMIT 1
        ");

        if (!empty($column?->column_default)) {
            if (
                preg_match(
                    "/^'((?:[^']|'')*)'::/",
                    $column->column_default,
                    $matches
                )
            ) {
                $defaultStatus = str_replace(
                    "''",
                    "'",
                    $matches[1]
                );

                if (
                    in_array(
                        $defaultStatus,
                        $allowedStatuses,
                        true
                    )
                ) {
                    return $defaultStatus;
                }
            }
        }

        return $allowedStatuses[0];
    }
}
