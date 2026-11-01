<?php

namespace App\Http\Controllers\MasterData;

use App\Domain\Catalog\Actions\CreateBrand;
use App\Domain\Catalog\Actions\DeleteBrand;
use App\Domain\Catalog\Actions\UpdateBrand;
use App\Domain\Catalog\Exceptions\BrandInUseException;
use App\Domain\Catalog\Queries\BrandListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreBrandRequest;
use App\Http\Requests\MasterData\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Throwable;

class BrandController extends Controller
{
    public function index(Request $request, BrandListQuery $query)
    {
        $this->authorize('viewAny', Brand::class);

        $q = $query->normalizeSearch($request->get('q'));
        $brands = $query->paginate($q);

        return view('content.master-data.brands.index', compact('brands', 'q'));
    }

    public function store(StoreBrandRequest $request, CreateBrand $action)
    {
        try {
            $action->handle(
                $request->validated('name'),
                $request->file('logo')
            );

            return redirect()
                ->route('master-data.brands.index')
                ->with('success', 'Brand berhasil ditambahkan.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Brand gagal disimpan. Silakan coba kembali.');
        }
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrand $action)
    {
        try {
            $action->handle(
                $brand,
                $request->validated('name'),
                $request->file('logo')
            );

            return redirect()
                ->route('master-data.brands.index')
                ->with('success', 'Brand berhasil diperbarui.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Brand gagal diperbarui. Silakan coba kembali.');
        }
    }

    public function destroy(Brand $brand, DeleteBrand $action)
    {
        $this->authorize('delete', $brand);

        try {
            $action->handle($brand);

            return back()->with('success', 'Brand berhasil dihapus.');
        } catch (BrandInUseException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Brand gagal dihapus karena masih digunakan atau terjadi kesalahan.'
            );
        }
    }
}
