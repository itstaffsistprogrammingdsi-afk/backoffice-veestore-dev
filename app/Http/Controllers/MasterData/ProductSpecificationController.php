<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductSpecification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductSpecificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-product-specifications')->only('index');
        $this->middleware('permission:create master-data-product-specifications')->only('store');
        $this->middleware('permission:update master-data-product-specifications')->only('update');
        $this->middleware('permission:delete master-data-product-specifications')->only('destroy');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $specs = ProductSpecification::when($q, function ($query) use ($q) {
                $query->where('value', 'ILIKE', "%{$q}%");
            })
            ->orderBy('value', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('content.master-data.product-specifications.index', compact('specs', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                // unique + respect soft delete
                Rule::unique('product_specifications', 'value')
                    ->whereNull('deleted_at'),
            ],
        ]);

        ProductSpecification::create($data);

        return redirect()
            ->route('master-data.product-specifications.index')
            ->with('success', 'Product specification berhasil ditambahkan.');
    }

    public function update(Request $request, ProductSpecification $productSpecification)
    {
        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_specifications', 'value')
                    ->ignore($productSpecification->id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $productSpecification->update($data);

        return redirect()
            ->route('master-data.product-specifications.index')
            ->with('success', 'Product specification berhasil diperbarui.');
    }

    public function destroy(ProductSpecification $productSpecification)
    {
        $productSpecification->delete(); // kalau model pakai SoftDeletes, ini soft delete

        return back()->with('success', 'Product specification berhasil dihapus.');
    }
}
