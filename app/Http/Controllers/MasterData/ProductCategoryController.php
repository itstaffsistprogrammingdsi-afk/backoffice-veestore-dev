<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class ProductCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-product-categories')->only(['index']);
        $this->middleware('permission:create master-data-product-categories')->only(['store']);
        $this->middleware('permission:update master-data-product-categories')->only(['update']);
        $this->middleware('permission:delete master-data-product-categories')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = $this->normalizeName($request->get('q'));

        $categories = ProductCategory::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'ILIKE', '%'.$q.'%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Product Categories',
            'description' => 'Kelola kategori produk dan gambar kategori.',
            'searchPlaceholder' => 'Cari kategori...',
            'items' => $categories,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Kategori', 'required' => true],
                ['key' => 'image', 'label' => 'Gambar', 'type' => 'file'],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.product-categories.index'),
                'store' => route('master-data.product-categories.store'),
                'update' => route('master-data.product-categories.update', ['product_category' => '__ID__']),
                'destroy' => route('master-data.product-categories.destroy', ['product_category' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['name' => $this->normalizeName($request->input('name'))]);

        try {
            $validated = $request->validateWithBag('createCategory', [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) {
                        if (ProductCategory::query()
                            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$value])
                            ->exists()) {
                            $fail('Nama kategori sudah digunakan.');
                        }
                    },
                ],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ], [
                'name.required' => 'Nama kategori wajib diisi.',
                'name.string' => 'Nama kategori harus berupa teks.',
                'name.max' => 'Nama kategori maksimal 100 karakter.',
                'image.image' => 'File harus berupa gambar.',
                'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
                'image.max' => 'Ukuran gambar maksimal 2 MB.',
            ]);

            DB::transaction(function () use ($request, $validated) {
                ProductCategory::create([
                    'name' => $validated['name'],
                    'image_url' => $request->hasFile('image')
                        ? $this->storeImageAndReturnUrl($request->file('image'))
                        : null,
                ]);
            });

            return redirect()
                ->route('master-data.product-categories.index')
                ->with('success', 'Kategori berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Kategori gagal disimpan. Silakan coba kembali.');
        }
    }

    public function update(Request $request, ProductCategory $product_category)
    {
        $request->merge(['name' => $this->normalizeName($request->input('name'))]);

        try {
            $validated = $request->validateWithBag('editCategory', [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($product_category) {
                        if (ProductCategory::query()
                            ->whereKeyNot($product_category->getKey())
                            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$value])
                            ->exists()) {
                            $fail('Nama kategori sudah digunakan.');
                        }
                    },
                ],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'id' => ['nullable', 'uuid'],
                'update_url' => ['nullable', 'string'],
                'current_image_url' => ['nullable', 'string'],
            ], [
                'name.required' => 'Nama kategori wajib diisi.',
                'name.string' => 'Nama kategori harus berupa teks.',
                'name.max' => 'Nama kategori maksimal 100 karakter.',
                'image.image' => 'File harus berupa gambar.',
                'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
                'image.max' => 'Ukuran gambar maksimal 2 MB.',
            ]);

            DB::transaction(function () use ($request, $product_category, $validated) {
                $imageUrl = $product_category->image_url;

                if ($request->hasFile('image')) {
                    $newImageUrl = $this->storeImageAndReturnUrl($request->file('image'));
                    $this->deleteImageFromUrl($product_category->image_url);
                    $imageUrl = $newImageUrl;
                }

                $product_category->update([
                    'name' => $validated['name'],
                    'image_url' => $imageUrl,
                ]);
            });

            return redirect()
                ->route('master-data.product-categories.index')
                ->with('success', 'Kategori berhasil diperbarui.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Kategori gagal diperbarui. Silakan coba kembali.');
        }
    }

    public function destroy(ProductCategory $product_category)
    {
        try {
            DB::transaction(function () use ($product_category) {
                $imageUrl = $product_category->image_url;
                $product_category->delete();
                $this->deleteImageFromUrl($imageUrl);
            });

            return back()->with('success', 'Kategori berhasil dihapus.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Kategori gagal dihapus karena masih digunakan atau terjadi kesalahan.'
            );
        }
    }

    private function storeImageAndReturnUrl(UploadedFile $file): string
    {
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        $fileName = (string) Str::uuid().'.'.$extension;

        $relativeDirectory = 'uploads/product-categories';
        $absoluteDirectory = public_path($relativeDirectory);

        if (! is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }

        $file->move($absoluteDirectory, $fileName);

        /*
         * request()->root() mengikuti alamat aplikasi yang sedang dibuka.
         * Contoh lokal:
         * http://localhost/nama-project/public
         *
         * Nilai database:
         * http://localhost/nama-project/public/uploads/product-categories/uuid.png
         */
        return rtrim(request()->root(), '/')
            .'/'
            .$relativeDirectory
            .'/'
            .$fileName;
    }

    private function deleteImageFromUrl(?string $imageUrl): void
    {
        if (empty($imageUrl)) {
            return;
        }

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);

        if (! $urlPath) {
            return;
        }

        $marker = '/uploads/product-categories/';
        $position = strpos($urlPath, $marker);

        if ($position === false) {
            return;
        }

        $fileName = basename($urlPath);
        $absolutePath = public_path(
            'uploads/product-categories/'.$fileName
        );

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
