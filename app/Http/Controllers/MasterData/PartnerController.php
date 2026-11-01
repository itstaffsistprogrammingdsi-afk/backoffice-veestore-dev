<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = Partner::query()
            ->when($q !== '', function ($w) use ($q) {
                $needle = mb_strtolower($q);
                $w->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Partners',
            'description' => 'Kelola data partner.',
            'searchPlaceholder' => 'Cari partner...',
            'items' => $items,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'created_at', 'label' => 'Dibuat'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Partner', 'required' => true],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.partners.index'),
                'store' => route('master-data.partners.store'),
                'update' => route('master-data.partners.update', ['partner' => '__ID__']),
                'destroy' => route('master-data.partners.destroy', ['partner' => '__ID__']),
            ],
        ]);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:partners,name'],
        ]);

        Partner::create($validated);

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Partner created.');
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:partners,name,'.$partner->getKey()],
        ]);

        $partner->update($validated);

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Partner updated.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Partner deleted.');
    }
}
