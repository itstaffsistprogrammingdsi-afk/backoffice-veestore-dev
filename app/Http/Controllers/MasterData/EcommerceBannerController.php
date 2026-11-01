<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\EcommerceBanner;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EcommerceBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-ecommerce-banners')
            ->only(['index']);

        $this->middleware('permission:create master-data-ecommerce-banners')
            ->only(['store']);

        $this->middleware('permission:update master-data-ecommerce-banners')
            ->only(['update']);

        $this->middleware('permission:delete master-data-ecommerce-banners')
            ->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $banners = EcommerceBanner::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery
                        ->where('name', 'ILIKE', '%' . $q . '%')
                        ->orWhere('description', 'ILIKE', '%' . $q . '%')
                        ->orWhere('placement', 'ILIKE', '%' . $q . '%')
                        ->orWhere('alt_text', 'ILIKE', '%' . $q . '%')
                        ->orWhere('cta_label', 'ILIKE', '%' . $q . '%');
                });
            })
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $statusValues = $this->getEnumValues(
            'banner_status',
            ['active', 'disabled']
        );

        $sizeValues = $this->getEnumValues(
            'banner_size',
            ['small', 'medium', 'large']
        );

        $statuses = $this->labelsFromValues($statusValues);
        $sizes = $this->labelsFromValues($sizeValues);

        return view(
            'content.master-data.ecommerce-banners.index',
            compact(
                'banners',
                'q',
                'statuses',
                'sizes'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            '_from' => 'create',
            'name' => $this->normalizeText($request->input('name')),
            'description' => $this->normalizeNullableText(
                $request->input('description')
            ),
            'alt_text' => $this->normalizeNullableText(
                $request->input('alt_text')
            ),
            'placement' => $this->normalizePlacement(
                $request->input('placement')
            ),
            'cta_label' => $this->normalizeNullableText(
                $request->input('cta_label')
            ),
            'target_url' => $this->normalizeNullableText(
                $request->input('target_url')
            ),
        ]);

        try {
            $data = $this->validateBanner($request, null, 'createBanner');

            DB::transaction(function () use ($request, $data): void {
                $payload = $this->buildPayload($data);

                $payload['image_url'] =
                    $this->storeImageAndReturnUrl(
                        $request->file('image'),
                        'desktop'
                    );

                if ($request->hasFile('mobile_image')) {
                    $payload['mobile_image_url'] =
                        $this->storeImageAndReturnUrl(
                            $request->file('mobile_image'),
                            'mobile'
                        );
                }

                EcommerceBanner::create($payload);
            });

            return redirect()
                ->route('master-data.ecommerce-banners.index')
                ->with('success', 'Banner berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'createBanner';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    $this->databaseErrorMessage($exception)
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Banner gagal disimpan. Silakan coba kembali.');
        }
    }

    public function update(
        Request $request,
        EcommerceBanner $ecommerce_banner
    ): RedirectResponse {
        $request->merge([
            '_from' => 'edit',
            'id' => $ecommerce_banner->getKey(),
            'name' => $this->normalizeText($request->input('name')),
            'description' => $this->normalizeNullableText(
                $request->input('description')
            ),
            'alt_text' => $this->normalizeNullableText(
                $request->input('alt_text')
            ),
            'placement' => $this->normalizePlacement(
                $request->input('placement')
            ),
            'cta_label' => $this->normalizeNullableText(
                $request->input('cta_label')
            ),
            'target_url' => $this->normalizeNullableText(
                $request->input('target_url')
            ),
            'update_url' => route(
                'master-data.ecommerce-banners.update',
                $ecommerce_banner->getKey()
            ),
            'current_image_url' => $ecommerce_banner->image_url,
            'current_mobile_image_url' =>
                $ecommerce_banner->mobile_image_url,
        ]);

        try {
            $data = $this->validateBanner(
                $request,
                $ecommerce_banner,
                'editBanner'
            );

            DB::transaction(function () use (
                $request,
                $data,
                $ecommerce_banner
            ): void {
                $lockedBanner = EcommerceBanner::query()
                    ->whereKey($ecommerce_banner->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $payload = $this->buildPayload($data);

                if ($request->hasFile('image')) {
                    $newImageUrl = $this->storeImageAndReturnUrl(
                        $request->file('image'),
                        'desktop'
                    );

                    $this->deleteImageFromUrl(
                        $lockedBanner->image_url
                    );

                    $payload['image_url'] = $newImageUrl;
                }

                if ($request->hasFile('mobile_image')) {
                    $newMobileImageUrl = $this->storeImageAndReturnUrl(
                        $request->file('mobile_image'),
                        'mobile'
                    );

                    $this->deleteImageFromUrl(
                        $lockedBanner->mobile_image_url
                    );

                    $payload['mobile_image_url'] =
                        $newMobileImageUrl;
                }

                if ($request->boolean('remove_mobile_image')) {
                    $this->deleteImageFromUrl(
                        $lockedBanner->mobile_image_url
                    );

                    $payload['mobile_image_url'] = null;
                }

                $lockedBanner->update($payload);
            });

            return redirect()
                ->route('master-data.ecommerce-banners.index')
                ->with('success', 'Banner berhasil diperbarui.');
        } catch (ValidationException $exception) {
            if ($exception->errorBag === 'default') {
                $exception->errorBag = 'editBanner';
            }

            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    $this->databaseErrorMessage($exception)
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Banner gagal diperbarui. Silakan coba kembali.');
        }
    }

    public function destroy(
        EcommerceBanner $ecommerce_banner
    ): RedirectResponse {
        try {
            DB::transaction(function () use (
                $ecommerce_banner
            ): void {
                $lockedBanner = EcommerceBanner::query()
                    ->whereKey($ecommerce_banner->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $desktopUrl = $lockedBanner->image_url;
                $mobileUrl = $lockedBanner->mobile_image_url;

                $lockedBanner->delete();

                $this->deleteImageFromUrl($desktopUrl);
                $this->deleteImageFromUrl($mobileUrl);
            });

            return back()->with(
                'success',
                'Banner berhasil dihapus.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Banner gagal dihapus. Silakan coba kembali.'
            );
        }
    }

    private function validateBanner(
        Request $request,
        ?EcommerceBanner $banner,
        string $errorBag
    ): array {
        $statusValues = $this->getEnumValues(
            'banner_status',
            ['active', 'disabled']
        );

        $sizeValues = $this->getEnumValues(
            'banner_size',
            ['small', 'medium', 'large']
        );

        $imageRules = $banner
            ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']
            : ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];

        $validator = validator(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', Rule::in($statusValues)],
                'size' => ['required', Rule::in($sizeValues)],
                'placement' => ['required', 'string', 'max:100'],
                'display_order' => ['required', 'integer', 'min:0'],
                'cta_label' => ['nullable', 'string', 'max:100'],
                'target_url' => [
                    'nullable',
                    'url',
                    'max:2048',
                ],
                'alt_text' => ['nullable', 'string', 'max:255'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => [
                    'nullable',
                    'date',
                    'after:starts_at',
                ],
                'image' => $imageRules,
                'mobile_image' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:3072',
                ],
                'remove_mobile_image' => ['nullable', 'boolean'],
                '_from' => ['nullable', Rule::in(['create', 'edit'])],
                'id' => ['nullable', 'uuid'],
                'update_url' => ['nullable', 'string'],
                'current_image_url' => ['nullable', 'string'],
                'current_mobile_image_url' => ['nullable', 'string'],
            ],
            [
                'name.required' => 'Nama banner wajib diisi.',
                'name.max' => 'Nama banner maksimal 255 karakter.',
                'status.required' => 'Status wajib dipilih.',
                'status.in' => 'Status banner tidak valid.',
                'size.required' => 'Ukuran banner wajib dipilih.',
                'size.in' => 'Ukuran banner tidak valid.',
                'placement.max' =>
                    'Placement maksimal 100 karakter.',
                'placement.required' =>
                    'Placement wajib diisi.',
                'display_order.required' =>
                    'Urutan tampil wajib diisi.',
                'display_order.integer' =>
                    'Urutan tampil harus berupa angka.',
                'display_order.min' =>
                    'Urutan tampil minimal 0.',
                'cta_label.max' =>
                    'Label tombol maksimal 100 karakter.',
                'target_reference_id.uuid' =>
                    'Target reference ID harus berupa UUID.',
                'target_url.url' =>
                    'Target URL harus berupa URL yang valid.',
                'alt_text.max' =>
                    'Alt text maksimal 255 karakter.',
                'ends_at.after' =>
                    'Tanggal selesai harus lebih besar dari tanggal mulai.',
                'image.required' =>
                    'Gambar desktop banner wajib diunggah.',
                'image.image' =>
                    'File gambar desktop harus berupa gambar.',
                'image.mimes' =>
                    'Format gambar desktop harus JPG, JPEG, PNG, atau WEBP.',
                'image.max' =>
                    'Ukuran gambar desktop maksimal 3 MB.',
                'mobile_image.image' =>
                    'File gambar mobile harus berupa gambar.',
                'mobile_image.mimes' =>
                    'Format gambar mobile harus JPG, JPEG, PNG, atau WEBP.',
                'mobile_image.max' =>
                    'Ukuran gambar mobile maksimal 3 MB.',
            ]
        );

        $validator->after(function ($validator) use ($request): void {
            $targetUrl = $request->input('target_url');

            if (
                $targetUrl !== null
                && $targetUrl !== ''
                && !filter_var($targetUrl, FILTER_VALIDATE_URL)
            ) {
                $validator->errors()->add(
                    'target_url',
                    'Target URL harus berupa URL yang valid.'
                );
            }
        });

        if ($validator->fails()) {
            throw new ValidationException(
                $validator,
                null,
                $errorBag
            );
        }

        return $validator->validated();
    }

    private function resolveActionType(
        ?string $targetUrl
    ): string {
        $allowed = $this->getEnumValues(
            'banner_action_type',
            ['none', 'url']
        );

        if (!empty($targetUrl)) {
            foreach (
                ['url', 'link', 'external', 'external_url', 'web']
                as $candidate
            ) {
                if (in_array($candidate, $allowed, true)) {
                    return $candidate;
                }
            }
        }

        foreach (
            ['none', 'no_action', 'disabled']
            as $candidate
        ) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $allowed[0];
    }

    private function buildPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'size' => $data['size'],
            'placement' => $data['placement'],
            'display_order' => (int) $data['display_order'],
            'action_type' => $this->resolveActionType(
                $data['target_url'] ?? null
            ),
            'cta_label' => $data['cta_label'] ?? null,
            'target_reference_id' => null,
            'target_url' => $data['target_url'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];
    }

    private function storeImageAndReturnUrl(
        UploadedFile $file,
        string $variant
    ): string {
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        $fileName = implode('-', [
            $variant,
            (string) Str::uuid(),
        ]) . '.' . $extension;

        $relativeDirectory = 'uploads/ecommerce-banners';
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

        if (
            !$urlPath ||
            strpos(
                $urlPath,
                '/uploads/ecommerce-banners/'
            ) === false
        ) {
            return;
        }

        $fileName = basename($urlPath);

        $absolutePath = public_path(
            'uploads/ecommerce-banners/' . $fileName
        );

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function databaseErrorMessage(
        QueryException $exception
    ): string {
        $message = $exception->getMessage();

        if (
            str_contains(
                $message,
                'ecommerce_banners_action_target_check'
            )
        ) {
            return 'Target banner tidak sesuai dengan aturan database. '
                . 'Kosongkan Target URL jika banner tidak memiliki tautan.';
        }

        if (
            str_contains(
                $message,
                'ecommerce_banners_schedule_check'
            )
        ) {
            return 'Jadwal banner tidak valid. '
                . 'Tanggal selesai harus lebih besar dari tanggal mulai.';
        }

        if ((string) $exception->getCode() === '22P02') {
            return 'Target Reference ID harus menggunakan format UUID yang valid.';
        }

        return 'Banner gagal disimpan karena data tidak sesuai dengan aturan database.';
    }

    private function getEnumValues(
        string $enumName,
        array $fallback
    ): array {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return $fallback;
        }

        $rows = DB::select(
            '
                SELECT e.enumlabel AS value
                FROM pg_type t
                INNER JOIN pg_enum e
                    ON t.oid = e.enumtypid
                WHERE t.typname = ?
                ORDER BY e.enumsortorder
            ',
            [$enumName]
        );

        $values = array_map(
            fn ($row) => (string) $row->value,
            $rows
        );

        return $values !== [] ? $values : $fallback;
    }

    private function labelsFromValues(array $values): array
    {
        $labels = [];

        foreach ($values as $value) {
            $labels[$value] = ucwords(
                str_replace('_', ' ', $value)
            );
        }

        return $labels;
    }

    private function normalizeText(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = $this->normalizeText($value);

        return $value === '' ? null : $value;
    }

    private function normalizePlacement(mixed $value): string
    {
        return $this->normalizeNullableText($value) ?? 'home';
    }
}
