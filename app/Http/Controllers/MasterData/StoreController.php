<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-stores')
            ->only(['index']);

        $this->middleware('permission:create master-data-stores')
            ->only(['store']);

        $this->middleware('permission:update master-data-stores')
            ->only(['update']);

        $this->middleware('permission:delete master-data-stores')
            ->only(['destroy']);
    }

    /**
     * Menampilkan daftar store dan pencarian.
     */
    public function index(Request $request)
    {
        $q = $this->normalizeText(
            $request->query('q')
        );

        $stores = Store::query()
            ->when($q !== '', function ($query) use ($q) {
                $driver = DB::connection()->getDriverName();

                $query->where(function ($subQuery) use ($q, $driver) {
                    if ($driver === 'pgsql') {
                        $subQuery
                            ->where('name', 'ILIKE', '%' . $q . '%')
                            ->orWhere('code', 'ILIKE', '%' . $q . '%')
                            ->orWhere('address', 'ILIKE', '%' . $q . '%')
                            ->orWhere('postal_code', 'ILIKE', '%' . $q . '%')
                            ->orWhere('phone', 'ILIKE', '%' . $q . '%')
                            ->orWhere('contact_name', 'ILIKE', '%' . $q . '%')
                            ->orWhere('contact_phone', 'ILIKE', '%' . $q . '%')
                            ->orWhere('contact_email', 'ILIKE', '%' . $q . '%');

                        return;
                    }

                    $search =
                        '%' .
                        mb_strtolower($q, 'UTF-8') .
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
            ->orderByRaw('LOWER(name) ASC')
            ->paginate(10)
            ->withQueryString();

        return view(
            'content.master-data.stores.index',
            compact('stores', 'q')
        );
    }

    /**
     * Menyimpan store baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedName = $this->normalizeText(
            $request->input('name')
        );

        $normalizedAddress = $this->normalizeAddress(
            $request->input('address')
        );

        $request->merge([
            'name' => $normalizedName,
            'address' => $normalizedAddress,
            'postal_code' => $this->normalizePostalCode(
                $request->input('postal_code')
            ),
            'phone' => $this->normalizeText(
                $request->input('phone')
            ),
            'contact_name' => $this->normalizeText($request->input('contact_name')),
            'contact_phone' => $this->normalizeText($request->input('contact_phone')),
            'contact_email' => $this->normalizeEmail($request->input('contact_email')),
            '_from' => 'create',
        ]);

        try {
            $data = $request->validateWithBag(
                'createStore',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:150',
                    ],
                    'address' => [
                        'required',
                        'string',
                        'max:1000',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ): void {
                            if (
                                $this->addressExists(
                                    (string) $value
                                )
                            ) {
                                $fail(
                                    'Alamat tersebut sudah digunakan oleh store lain.'
                                );
                            }
                        },
                    ],
                    'postal_code' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                    'phone' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                    'contact_name' => ['required', 'string', 'max:255'],
                    'contact_phone' => ['required', 'string', 'max:255'],
                    'contact_email' => ['nullable', 'email', 'max:255'],
                    'image' => [
                        'nullable',
                        'image',
                        'mimes:jpg,jpeg,png,webp',
                        'max:2048',
                    ],
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
                    'name.required' =>
                        'Nama store wajib diisi.',

                    'name.max' =>
                        'Nama store maksimal 150 karakter.',

                    'address.required' =>
                        'Alamat store wajib diisi.',

                    'address.max' =>
                        'Alamat store terlalu panjang.',

                    'postal_code.max' =>
                        'Kode pos maksimal 20 karakter.',

                    'phone.max' =>
                        'Nomor telepon maksimal 255 karakter.',

                    'contact_name.required' => 'Nama kontak wajib diisi.',
                    'contact_name.max' => 'Nama kontak maksimal 255 karakter.',
                    'contact_phone.required' => 'Nomor kontak wajib diisi.',
                    'contact_phone.max' => 'Nomor kontak maksimal 255 karakter.',
                    'contact_email.email' => 'Email kontak tidak valid.',
                    'contact_email.max' => 'Email kontak maksimal 255 karakter.',

                    'image.image' =>
                        'File harus berupa gambar.',

                    'image.mimes' =>
                        'Format gambar harus JPG, JPEG, PNG, atau WEBP.',

                    'image.max' =>
                        'Ukuran gambar maksimal 2 MB.',

                    'lat.numeric' =>
                        'Latitude harus berupa angka.',

                    'lat.between' =>
                        'Latitude harus berada antara -90 sampai 90.',

                    'lat.required_with' =>
                        'Latitude wajib diisi ketika longitude tersedia.',

                    'lng.numeric' =>
                        'Longitude harus berupa angka.',

                    'lng.between' =>
                        'Longitude harus berada antara -180 sampai 180.',

                    'lng.required_with' =>
                        'Longitude wajib diisi ketika latitude tersedia.',
                ]
            );

            DB::transaction(function () use ($request, $data): void {
                $normalizedAddress = $this->normalizeAddress(
                    $data['address']
                );

                /*
                 * Mencegah dua request bersamaan menyimpan alamat sama.
                 * Tidak perlu mengubah struktur database.
                 */
                $this->lockNormalizedAddress(
                    $normalizedAddress
                );

                /*
                 * Periksa ulang setelah lock diperoleh.
                 */
                if (
                    $this->addressExists(
                        $normalizedAddress
                    )
                ) {
                    throw ValidationException::withMessages([
                        'address' =>
                            'Alamat tersebut sudah digunakan oleh store lain.',
                    ]);
                }

                $payload = $this->buildPayload($data);

                if ($request->hasFile('image')) {
                    $payload['image_url'] = $this->storeImageAndReturnUrl(
                        $request->file('image')
                    );
                }

                Store::create($payload);
            });

            return redirect()
                ->route('master-data.stores.index')
                ->with(
                    'success',
                    'Store berhasil ditambahkan.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'createStore';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' =>
                            'Store gagal disimpan. Silakan coba kembali.',
                    ],
                    'createStore'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' =>
                            'Store gagal disimpan. Silakan coba kembali.',
                    ],
                    'createStore'
                );
        }
    }

    /**
     * Memperbarui store.
     */
    public function update(
        Request $request,
        Store $store
    ): RedirectResponse {
        $normalizedName = $this->normalizeText(
            $request->input('name')
        );

        $normalizedAddress = $this->normalizeAddress(
            $request->input('address')
        );

        $request->merge([
            'name' => $normalizedName,
            'address' => $normalizedAddress,
            'postal_code' => $this->normalizePostalCode(
                $request->input('postal_code')
            ),
            'phone' => $this->normalizeText(
                $request->input('phone')
            ),
            'contact_name' => $this->normalizeText($request->input('contact_name')),
            'contact_phone' => $this->normalizeText($request->input('contact_phone')),
            'contact_email' => $this->normalizeEmail($request->input('contact_email')),
            '_from' => 'edit',
            'id' => $store->getKey(),
        ]);

        try {
            $data = $request->validateWithBag(
                'editStore',
                [
                    'name' => [
                        'required',
                        'string',
                        'max:150',
                    ],
                    'address' => [
                        'required',
                        'string',
                        'max:1000',
                        function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($store): void {
                            if (
                                $this->addressExists(
                                    (string) $value,
                                    (string) $store->getKey()
                                )
                            ) {
                                $fail(
                                    'Alamat tersebut sudah digunakan oleh store lain.'
                                );
                            }
                        },
                    ],
                    'postal_code' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                    'phone' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                    'contact_name' => ['required', 'string', 'max:255'],
                    'contact_phone' => ['required', 'string', 'max:255'],
                    'contact_email' => ['nullable', 'email', 'max:255'],
                    'image' => [
                        'nullable',
                        'image',
                        'mimes:jpg,jpeg,png,webp',
                        'max:2048',
                    ],
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
                    'name.required' =>
                        'Nama store wajib diisi.',

                    'name.max' =>
                        'Nama store maksimal 150 karakter.',

                    'address.required' =>
                        'Alamat store wajib diisi.',

                    'address.max' =>
                        'Alamat store terlalu panjang.',

                    'postal_code.max' =>
                        'Kode pos maksimal 20 karakter.',

                    'phone.max' =>
                        'Nomor telepon maksimal 255 karakter.',

                    'contact_name.required' => 'Nama kontak wajib diisi.',
                    'contact_name.max' => 'Nama kontak maksimal 255 karakter.',
                    'contact_phone.required' => 'Nomor kontak wajib diisi.',
                    'contact_phone.max' => 'Nomor kontak maksimal 255 karakter.',
                    'contact_email.email' => 'Email kontak tidak valid.',
                    'contact_email.max' => 'Email kontak maksimal 255 karakter.',

                    'image.image' =>
                        'File harus berupa gambar.',

                    'image.mimes' =>
                        'Format gambar harus JPG, JPEG, PNG, atau WEBP.',

                    'image.max' =>
                        'Ukuran gambar maksimal 2 MB.',

                    'lat.numeric' =>
                        'Latitude harus berupa angka.',

                    'lat.between' =>
                        'Latitude harus berada antara -90 sampai 90.',

                    'lat.required_with' =>
                        'Latitude wajib diisi ketika longitude tersedia.',

                    'lng.numeric' =>
                        'Longitude harus berupa angka.',

                    'lng.between' =>
                        'Longitude harus berada antara -180 sampai 180.',

                    'lng.required_with' =>
                        'Longitude wajib diisi ketika latitude tersedia.',
                ]
            );

            DB::transaction(function () use ($request, $store, $data): void {
                $normalizedAddress = $this->normalizeAddress(
                    $data['address']
                );

                $this->lockNormalizedAddress(
                    $normalizedAddress
                );

                $lockedStore = Store::query()
                    ->whereKey($store->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $this->addressExists(
                        $normalizedAddress,
                        (string) $lockedStore->getKey()
                    )
                ) {
                    throw ValidationException::withMessages([
                        'address' =>
                            'Alamat tersebut sudah digunakan oleh store lain.',
                    ]);
                }

                /*
                 * Kolom code tidak ikut diperbarui.
                 */
                $payload = $this->buildPayload($data);

                if ($request->hasFile('image')) {
                    $newImageUrl = $this->storeImageAndReturnUrl(
                        $request->file('image')
                    );

                    $oldImageUrl = $lockedStore->image_url;
                    $payload['image_url'] = $newImageUrl;

                    $lockedStore->update($payload);

                    $this->deleteImageFromUrl($oldImageUrl);
                    return;
                }

                $lockedStore->update($payload);
            });

            return redirect()
                ->route('master-data.stores.index')
                ->with(
                    'success',
                    'Store berhasil diperbarui.'
                );
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'editStore';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' =>
                            'Store gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editStore'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(
                    [
                        'address' =>
                            'Store gagal diperbarui. Silakan coba kembali.',
                    ],
                    'editStore'
                );
        }
    }

    /**
     * Menghapus store.
     */
    public function destroy(
        Store $store
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($store): void {
                $lockedStore = Store::query()
                    ->whereKey($store->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $imageUrl = $lockedStore->image_url;

                $lockedStore->delete();

                $this->deleteImageFromUrl($imageUrl);
            });

            return back()->with(
                'success',
                'Store berhasil dihapus.'
            );
        } catch (QueryException $exception) {
            report($exception);

            return back()->with(
                'error',
                sprintf(
                    'Store "%s" tidak dapat dihapus karena masih digunakan pada data lain.',
                    $store->name
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Store gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    /**
     * Memeriksa alamat secara case-insensitive dan mengabaikan
     * perbedaan spasi.
     *
     * Contoh alamat berikut dianggap sama:
     * - Jalan Sudirman No. 10
     * - jalan sudirman no. 10
     * - " Jalan   Sudirman No. 10 "
     */
    private function addressExists(
        string $address,
        ?string $ignoreId = null
    ): bool {
        $normalizedAddress = $this->normalizeAddress(
            $address
        );

        return Store::query()
            ->when(
                $ignoreId !== null,
                fn ($query) =>
                    $query->whereKeyNot($ignoreId)
            )
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
     * Tidak membuat migration, index, kolom, atau tabel baru.
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
            $this->normalizeAddress($address),
            'UTF-8'
        );

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ['store-address:' . $normalizedAddress]
        );
    }

    /**
     * Membentuk payload penyimpanan store.
     */
    private function buildPayload(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'address' => $data['address'],
            'postal_code' =>
                $data['postal_code'] ?: null,
            'phone' =>
                $data['phone'] ?: null,
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

    private function storeImageAndReturnUrl(
        UploadedFile $file
    ): string {
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        $fileName = (string) Str::uuid() . '.' . $extension;
        $relativeDirectory = 'uploads/stores';
        $absoluteDirectory = public_path($relativeDirectory);

        if (!is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }

        $file->move($absoluteDirectory, $fileName);

        return rtrim(request()->root(), '/')
            . '/'
            . $relativeDirectory
            . '/'
            . $fileName;
    }

    private function deleteImageFromUrl(
        ?string $imageUrl
    ): void {
        if (empty($imageUrl)) {
            return;
        }

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);

        if (!$urlPath || strpos($urlPath, '/uploads/stores/') === false) {
            return;
        }

        $fileName = basename($urlPath);
        $absolutePath = public_path(
            'uploads/stores/' . $fileName
        );

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
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

    private function normalizeAddress(
        mixed $value
    ): string {
        return $this->normalizeText($value);
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
}
