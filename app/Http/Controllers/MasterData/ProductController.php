<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductSpecification;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class ProductController extends Controller
{
    private const MAX_IMAGE_COUNT = 10;

    public function __construct()
    {
        $this->middleware('permission:view master-data-products')
            ->only('index');

        $this->middleware('permission:create master-data-products')
            ->only('store');

        $this->middleware('permission:update master-data-products')
            ->only('update');

        $this->middleware('permission:delete master-data-products')
            ->only('destroy');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $categoryId = $request->get('category_id');

        $products = Product::with([
            'category',
            'brand',
            'unit',
            'images',
            'specification',
        ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($search) use ($q) {
                    $search
                        ->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('code', 'ILIKE', "%{$q}%")
                        ->orWhereHas('category', function ($category) use ($q) {
                            $category->where('name', 'ILIKE', "%{$q}%");
                        })
                        ->orWhereHas('brand', function ($brand) use ($q) {
                            $brand->where('name', 'ILIKE', "%{$q}%");
                        });
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = ProductCategory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $specifications = ProductSpecification::orderBy('value')->get();
        $brands = Brand::orderBy('name')->get();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Produk',
            'eyebrow' => 'Master Produk',
            'description' => 'Kelola katalog produk.',
            'searchPlaceholder' => 'Cari produk...',
            'items' => $products,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'category.name', 'label' => 'Kategori'],
                ['key' => 'brand.name', 'label' => 'Brand'],
                ['key' => 'unit.name', 'label' => 'Unit'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Produk', 'required' => true],
                ['key' => 'code', 'label' => 'Kode Produk', 'required' => true],
                ['key' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'required' => true, 'options' => $categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])->values()->all()],
                ['key' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'options' => $brands->map(fn ($brand) => ['value' => $brand->id, 'label' => $brand->name])->values()->all()],
                ['key' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'required' => true, 'options' => $units->map(fn ($unit) => ['value' => $unit->id, 'label' => $unit->name])->values()->all()],
                ['key' => 'specification_id', 'label' => 'Spesifikasi', 'type' => 'select', 'options' => $specifications->map(fn ($spec) => ['value' => $spec->id, 'label' => $spec->value])->values()->all()],
                ['key' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['key' => 'weight_g', 'label' => 'Berat (gram)', 'type' => 'number', 'required' => true],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.products.index'),
                'store' => route('master-data.products.store'),
                'update' => route('master-data.products.update', ['product' => '__ID__']),
                'destroy' => route('master-data.products.destroy', ['product' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'weight_g' => $this->normalizeWeight(
                $request->input('weight_g')
            ),
        ]);

        $validated = $request->validate(
            $this->productRules(),
            $this->validationMessages()
        );

        $productData = Arr::except($validated, [
            'images',
            'active_image_ids',
            'delete_image_ids',
            'is_main_image_id',
        ]);

        $productData['name'] = Str::upper(
            trim((string) $productData['name'])
        );

        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $productData,
                &$storedPaths
            ) {
                $product = Product::create($productData);

                $files = $request->file('images', []);

                foreach ($files as $index => $file) {
                    $stored = $this->storeProductImageFile(
                        $file,
                        $product->name
                    );

                    $storedPaths[] = $stored['path'];

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $stored['url'],
                        'status_active' => 'active',
                        'is_main' => $index === 0,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Produk gagal disimpan. Silakan coba kembali.');
        }

        return redirect()
            ->route('master-data.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $request->merge([
            'weight_g' => $this->normalizeWeight(
                $request->input('weight_g')
            ),
        ]);

        $validated = $request->validate(
            $this->productRules($product),
            $this->validationMessages()
        );

        $productData = Arr::except($validated, [
            'images',
            'active_image_ids',
            'delete_image_ids',
            'is_main_image_id',
        ]);

        $productData['name'] = Str::upper(
            trim((string) $productData['name'])
        );

        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $product,
                $productData,
                &$storedPaths
            ) {
                $product->update($productData);

                $deleteIds = array_map(
                    'strval',
                    $request->input('delete_image_ids', [])
                );

                if ($deleteIds !== []) {
                    $imagesToDelete = ProductImage::query()
                        ->where('product_id', $product->id)
                        ->whereIn('id', $deleteIds)
                        ->get();

                    foreach ($imagesToDelete as $image) {
                        $this->deleteImageFromDisk($image->image_url);
                        $image->delete();
                    }
                }

                /*
                 * Status aktif hanya diterapkan pada gambar lama.
                 * Gambar baru otomatis aktif.
                 */
                $activeIds = array_values(array_unique(array_map(
                    'strval',
                    $request->input('active_image_ids', [])
                )));

                ProductImage::query()
                    ->where('product_id', $product->id)
                    ->update(['status_active' => 'non_active']);

                if ($activeIds !== []) {
                    ProductImage::query()
                        ->where('product_id', $product->id)
                        ->whereIn('id', $activeIds)
                        ->update(['status_active' => 'active']);
                }

                /*
                 * Terapkan main untuk gambar lama, selama tidak ikut dihapus.
                 */
                $selectedMainId = $request->input('is_main_image_id');

                if (
                    $selectedMainId &&
                    ! in_array((string) $selectedMainId, $deleteIds, true)
                ) {
                    ProductImage::query()
                        ->where('product_id', $product->id)
                        ->update(['is_main' => false]);

                    ProductImage::query()
                        ->where('product_id', $product->id)
                        ->whereKey($selectedMainId)
                        ->update(['is_main' => true]);
                }

                /*
                 * Simpan semua gambar baru.
                 */
                $files = $request->file('images', []);

                foreach ($files as $file) {
                    $hasMainImage = ProductImage::query()
                        ->where('product_id', $product->id)
                        ->where('is_main', true)
                        ->exists();

                    $stored = $this->storeProductImageFile(
                        $file,
                        $product->name
                    );

                    $storedPaths[] = $stored['path'];

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $stored['url'],
                        'status_active' => 'active',
                        'is_main' => ! $hasMainImage,
                    ]);
                }

                /*
                 * Pastikan selalu hanya ada satu gambar utama.
                 */
                $mainImage = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->where('is_main', true)
                    ->orderBy('created_at')
                    ->first();

                if (! $mainImage) {
                    $fallback = ProductImage::query()
                        ->where('product_id', $product->id)
                        ->orderBy('created_at')
                        ->first();

                    if ($fallback) {
                        ProductImage::query()
                            ->where('product_id', $product->id)
                            ->update(['is_main' => false]);

                        $fallback->update(['is_main' => true]);
                    }
                } else {
                    ProductImage::query()
                        ->where('product_id', $product->id)
                        ->whereKeyNot($mainImage->id)
                        ->update(['is_main' => false]);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Produk gagal diperbarui. Silakan coba kembali.');
        }

        return redirect()
            ->route('master-data.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                ProductPrice::query()
                    ->where('product_id', $product->id)
                    ->delete();

                $images = $product->images()->get();

                foreach ($images as $image) {
                    $this->deleteImageFromDisk($image->image_url);
                    $image->delete();
                }

                $product->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Produk gagal dihapus. Silakan coba kembali.'
            );
        }

        return back()->with(
            'success',
            'Produk berhasil dihapus.'
        );
    }

    private function productRules(?Product $product = null): array
    {
        $productId = $product?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->ignore($productId)
                    ->whereNull('deleted_at'),
            ],
            'unit_id' => [
                'required',
                'string',
                'exists:units,id',
            ],
            'category_id' => [
                'required',
                'string',
                'exists:product_categories,id',
            ],
            'brand_id' => [
                'nullable',
                'string',
                Rule::exists('brands', 'id')
                    ->whereNull('deleted_at'),
            ],
            'specification_id' => [
                'nullable',
                'string',
                Rule::exists('product_specifications', 'id')
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'weight_g' => [
                'required',
                'numeric',
                'gte:0',
                'lt:10000000',
            ],

            'images' => [
                'nullable',
                'array',
                'max:'.self::MAX_IMAGE_COUNT,
            ],
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'active_image_ids' => ['nullable', 'array'],
            'active_image_ids.*' => [
                'string',
                Rule::exists('product_images', 'id')
                    ->where(function ($query) use ($productId) {
                        if ($productId) {
                            $query->where('product_id', $productId);
                        }
                    }),
            ],

            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => [
                'string',
                Rule::exists('product_images', 'id')
                    ->where(function ($query) use ($productId) {
                        if ($productId) {
                            $query->where('product_id', $productId);
                        }
                    }),
            ],

            'is_main_image_id' => [
                'nullable',
                'string',
                Rule::exists('product_images', 'id')
                    ->where(function ($query) use ($productId) {
                        if ($productId) {
                            $query->where('product_id', $productId);
                        }
                    }),
            ],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'weight_g.required' => 'Berat produk wajib diisi.',
            'images.array' => 'Data gambar tidak valid.',
            'images.max' => 'Maksimal 10 gambar dalam satu kali simpan.',
            'images.*.required' => 'File gambar tidak boleh kosong.',
            'images.*.image' => 'Salah satu file bukan gambar yang valid.',
            'images.*.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran setiap gambar maksimal 3MB.',
        ];
    }

    private function storeProductImageFile(
        $file,
        string $productName
    ): array {
        $slug = Str::slug($productName ?: 'produk');
        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $filename = sprintf(
            '%s-%s-%s.%s',
            $slug,
            now()->format('YmdHisv'),
            Str::lower(Str::random(10)),
            $extension
        );

        $path = Storage::disk('public')->putFileAs(
            'products',
            $file,
            $filename
        );

        if (! $path) {
            throw new \RuntimeException(
                'Gagal menyimpan file gambar.'
            );
        }

        return [
            'path' => $path,
            'url' => URL::to(Storage::url($path)),
        ];
    }

    private function normalizeWeight(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        $value = str_replace(["\u{00A0}", ' '], '', $value);

        if (
            str_contains($value, ',') &&
            str_contains($value, '.')
        ) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d+,\d+$/', $value)) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        $weight = round((float) $value, 3);

        if ($weight < 0 || $weight >= 10000000) {
            return null;
        }

        return $weight;
    }

    private function deleteStoredPaths(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteImageFromDisk(string $imagePathOrUrl): void
    {
        try {
            $path = parse_url(
                $imagePathOrUrl,
                PHP_URL_PATH
            ) ?? $imagePathOrUrl;

            $path = ltrim($path, '/');

            if (Str::startsWith($path, 'storage/')) {
                $path = Str::replaceFirst(
                    'storage/',
                    '',
                    $path
                );
            }

            if ($path !== '') {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
