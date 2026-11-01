<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CourierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-couriers')
            ->only(['index']);

        $this->middleware('permission:create master-data-couriers')
            ->only(['store']);

        $this->middleware('permission:update master-data-couriers')
            ->only(['update']);

        $this->middleware('permission:delete master-data-couriers')
            ->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $couriers = Courier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('code', 'ILIKE', "%{$q}%")
                        ->orWhere('phone', 'ILIKE', "%{$q}%");
                });
            })
            ->orderByRaw("
                CAST(
                    REGEXP_REPLACE(code, '[^0-9]', '', 'g')
                    AS INTEGER
                ) ASC
            ")
            ->paginate(10)
            ->withQueryString();

        $serviceTypes = ['regular', 'express', 'cargo'];
        $statuses = ['active', 'non_active'];

        return view(
            'content.master-data.couriers.index',
            compact('couriers', 'q', 'serviceTypes', 'statuses')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $name = $this->normalizeCourierName(
            $request->input('name')
        );

        $phone = $this->normalizeIndoPhone(
            $request->input('phone')
        );

        $request->merge([
            'name' => $name,
            'phone' => $phone,
            '_from' => 'create',
        ]);

        try {
            $data = $request->validate(
                $this->rules(),
                $this->messages()
            );

            DB::transaction(function () use ($data): void {
                $name = $this->normalizeCourierName($data['name']);
                $phone = $this->normalizeIndoPhone($data['phone']);

                $this->lockCourierIdentity($name, $phone);

                $errors = [];

                if ($this->courierNameExists($name)) {
                    $errors['name'] =
                        'Nama courier sudah digunakan.';
                }

                if ($this->courierPhoneExists($phone)) {
                    $errors['phone'] =
                        'Nomor telepon courier sudah digunakan.';
                }

                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }

                Courier::create([
                    'code' => $this->generateCourierCode(),
                    'name' => $name,
                    'phone' => $phone,
                    'contact_person' =>
                        $data['contact_person'] ?? null,
                    'email' => $data['email'] ?? null,
                    'service_type' =>
                        $data['service_type'] ?? null,
                    'status' => $data['status'],
                ]);
            });

            return redirect()
                ->route('master-data.couriers.index')
                ->with('success', 'Courier berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'name' =>
                            'Nama atau nomor telepon courier sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' =>
                        'Courier gagal disimpan. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' =>
                        'Courier gagal disimpan. Silakan coba kembali.',
                ]);
        }
    }

    public function update(
        Request $request,
        Courier $courier
    ): RedirectResponse {
        $name = $this->normalizeCourierName(
            $request->input('name')
        );

        $phone = $this->normalizeIndoPhone(
            $request->input('phone')
        );

        $request->merge([
            'name' => $name,
            'phone' => $phone,
            '_from' => 'edit',
            'id' => $courier->getKey(),
            'update_url' => route(
                'master-data.couriers.update',
                $courier->getKey()
            ),
        ]);

        try {
            $data = $request->validate(
                $this->rules($courier),
                $this->messages()
            );

            DB::transaction(
                function () use ($data, $courier): void {
                    $name = $this->normalizeCourierName(
                        $data['name']
                    );

                    $phone = $this->normalizeIndoPhone(
                        $data['phone']
                    );

                    $this->lockCourierIdentity($name, $phone);

                    $lockedCourier = Courier::query()
                        ->whereKey($courier->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $errors = [];

                    if (
                        $this->courierNameExists(
                            $name,
                            (string) $lockedCourier->getKey()
                        )
                    ) {
                        $errors['name'] =
                            'Nama courier sudah digunakan.';
                    }

                    if (
                        $this->courierPhoneExists(
                            $phone,
                            (string) $lockedCourier->getKey()
                        )
                    ) {
                        $errors['phone'] =
                            'Nomor telepon courier sudah digunakan.';
                    }

                    if ($errors !== []) {
                        throw ValidationException::withMessages(
                            $errors
                        );
                    }

                    $lockedCourier->update([
                        'name' => $name,
                        'phone' => $phone,
                        'contact_person' =>
                            $data['contact_person'] ?? null,
                        'email' => $data['email'] ?? null,
                        'service_type' =>
                            $data['service_type'] ?? null,
                        'status' => $data['status'],
                    ]);
                }
            );

            return redirect()
                ->route('master-data.couriers.index')
                ->with('success', 'Courier berhasil diperbarui.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return back()
                    ->withInput($request->except(['_token']))
                    ->withErrors([
                        'name' =>
                            'Nama atau nomor telepon courier sudah digunakan.',
                    ]);
            }

            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' =>
                        'Courier gagal diperbarui. Silakan coba kembali.',
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['_token']))
                ->withErrors([
                    'name' =>
                        'Courier gagal diperbarui. Silakan coba kembali.',
                ]);
        }
    }

    public function destroy(Courier $courier): RedirectResponse
    {
        try {
            $courier->delete();

            return back()->with(
                'success',
                'Courier berhasil dihapus.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Courier gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    private function rules(?Courier $courier = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                function (
                    string $attribute,
                    mixed $value,
                    Closure $fail
                ) use ($courier): void {
                    if (
                        $this->courierNameExists(
                            (string) $value,
                            $courier
                                ? (string) $courier->getKey()
                                : null
                        )
                    ) {
                        $fail('Nama courier sudah digunakan.');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'max:150',
                function (
                    string $attribute,
                    mixed $value,
                    Closure $fail
                ) use ($courier): void {
                    if (
                        $this->courierPhoneExists(
                            (string) $value,
                            $courier
                                ? (string) $courier->getKey()
                                : null
                        )
                    ) {
                        $fail(
                            'Nomor telepon courier sudah digunakan.'
                        );
                    }
                },
            ],
            'contact_person' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'service_type' => [
                'nullable',
                Rule::in(['regular', 'express', 'cargo']),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'non_active']),
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' =>
                'Nama courier wajib diisi.',
            'name.string' =>
                'Nama courier harus berupa teks.',
            'name.max' =>
                'Nama courier maksimal 150 karakter.',
            'phone.required' =>
                'Nomor telepon wajib diisi.',
            'phone.string' =>
                'Nomor telepon harus berupa teks.',
            'phone.max' =>
                'Nomor telepon maksimal 150 karakter.',
            'contact_person.string' =>
                'Contact person harus berupa teks.',
            'contact_person.max' =>
                'Contact person maksimal 100 karakter.',
            'email.email' =>
                'Format email tidak valid.',
            'email.max' =>
                'Email maksimal 150 karakter.',
            'service_type.in' =>
                'Service type tidak valid.',
            'status.required' =>
                'Status wajib dipilih.',
            'status.in' =>
                'Status tidak valid.',
        ];
    }

    protected function courierNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = $this->normalizeCourierName($name);

        return Courier::query()
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->whereRaw(
                "UPPER(
                    REGEXP_REPLACE(
                        TRIM(name),
                        '\\s+',
                        ' ',
                        'g'
                    )
                ) = ?",
                [$normalizedName]
            )
            ->exists();
    }

    protected function courierPhoneExists(
        string $phone,
        ?string $ignoreId = null
    ): bool {
        $normalizedPhone = $this->normalizeIndoPhone($phone);

        if ($normalizedPhone === '') {
            return false;
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $normalizedPhone
        ) ?? '';

        return Courier::query()
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->whereRaw(
                "REGEXP_REPLACE(
                    COALESCE(phone, ''),
                    '[^0-9]',
                    '',
                    'g'
                ) = ?",
                [$digits]
            )
            ->exists();
    }

    protected function lockCourierIdentity(
        string $name,
        string $phone
    ): void {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            [
                'courier:name:' .
                $this->normalizeCourierName($name),
            ]
        );

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            [
                'courier:phone:' .
                $this->normalizeIndoPhone($phone),
            ]
        );
    }

    protected function normalizeCourierName(?string $name): string
    {
        $name = trim((string) $name);

        $name = preg_replace(
            '/\s+/u',
            ' ',
            $name
        ) ?? $name;

        return mb_strtoupper($name, 'UTF-8');
    }

    protected function normalizeIndoPhone(?string $phone): string
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
            ? '+62' . $digits
            : '';
    }

    protected function generateCourierCode(): string
    {
        $maxNumber = Courier::withTrashed()
            ->selectRaw("
                COALESCE(
                    MAX(
                        CAST(
                            REGEXP_REPLACE(
                                code,
                                '[^0-9]',
                                '',
                                'g'
                            ) AS INTEGER
                        )
                    ),
                    0
                ) AS max_number
            ")
            ->value('max_number');

        $nextNumber = (int) $maxNumber + 1;

        return 'CR-' . str_pad(
            (string) $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    protected function isUniqueConstraintViolation(
        QueryException $exception
    ): bool {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array(
            $sqlState,
            ['23000', '23505'],
            true
        );
    }
}
