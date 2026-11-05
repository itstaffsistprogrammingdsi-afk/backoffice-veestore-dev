<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductCategoryGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductCategoryGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-product-categories')->only(['index']);
        $this->middleware('permission:create master-data-product-categories')->only(['store']);
        $this->middleware('permission:update master-data-product-categories')->only(['update']);
        $this->middleware('permission:delete master-data-product-categories')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $q = $this->normalizeName($request->get('q'));

        $groups = ProductCategoryGroup::query()
            ->withCount('categories')
            ->when($q !== '', fn ($query) => $query->where('name', 'ILIKE', '%'.$q.'%'))
            ->ordered()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Grup Kategori Produk',
            'eyebrow' => 'Master Produk / Kategori',
            'description' => 'Kelompokkan kategori produk agar katalog lebih terstruktur.',
            'searchPlaceholder' => 'Cari grup kategori...',
            'items' => $groups,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Grup'],
                ['key' => 'sort_order', 'label' => 'Urutan', 'align' => 'end'],
                ['key' => 'categories_count', 'label' => 'Kategori', 'align' => 'end'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Grup', 'required' => true],
                ['key' => 'sort_order', 'label' => 'Urutan', 'type' => 'number'],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.product-category-groups.index'),
                'store' => route('master-data.product-category-groups.store'),
                'update' => route('master-data.product-category-groups.update', ['product_category_group' => '__ID__']),
                'destroy' => route('master-data.product-category-groups.destroy', ['product_category_group' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (ProductCategoryGroup::query()
                            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [(string) $value])
                            ->exists()) {
                            $fail('Nama grup kategori sudah digunakan.');
                        }
                    },
                ],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ], [
                'name.required' => 'Nama grup kategori wajib diisi.',
                'name.string' => 'Nama grup kategori harus berupa teks.',
                'name.max' => 'Nama grup kategori maksimal 255 karakter.',
                'sort_order.integer' => 'Urutan harus berupa angka.',
                'sort_order.min' => 'Urutan tidak boleh negatif.',
            ]);

            ProductCategoryGroup::create([
                'name' => $validated['name'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);

            return redirect()
                ->route('master-data.product-category-groups.index')
                ->with('success', 'Grup kategori berhasil ditambahkan.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Grup kategori gagal disimpan. Silakan coba kembali.');
        }
    }

    public function update(
        Request $request,
        ProductCategoryGroup $product_category_group
    ): RedirectResponse {
        $request->merge([
            'name' => $this->normalizeName($request->input('name')),
        ]);

        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail) use ($product_category_group): void {
                        if (ProductCategoryGroup::query()
                            ->whereKeyNot($product_category_group->getKey())
                            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [(string) $value])
                            ->exists()) {
                            $fail('Nama grup kategori sudah digunakan.');
                        }
                    },
                ],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ], [
                'name.required' => 'Nama grup kategori wajib diisi.',
                'name.string' => 'Nama grup kategori harus berupa teks.',
                'name.max' => 'Nama grup kategori maksimal 255 karakter.',
                'sort_order.integer' => 'Urutan harus berupa angka.',
                'sort_order.min' => 'Urutan tidak boleh negatif.',
            ]);

            $product_category_group->update([
                'name' => $validated['name'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);

            return redirect()
                ->route('master-data.product-category-groups.index')
                ->with('success', 'Grup kategori berhasil diperbarui.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Grup kategori gagal diperbarui. Silakan coba kembali.');
        }
    }

    public function destroy(ProductCategoryGroup $product_category_group): RedirectResponse
    {
        try {
            DB::transaction(function () use ($product_category_group): void {
                $product_category_group->categories()->update(['group_id' => null]);
                $product_category_group->delete();
            });

            return back()->with('success', 'Grup kategori berhasil dihapus.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Grup kategori gagal dihapus karena masih digunakan atau terjadi kesalahan.'
            );
        }
    }

    private function normalizeName(mixed $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
