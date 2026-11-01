<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q'));

        $items = Partner::query()
            ->when($q !== '', function ($w) use ($q) {
                $needle = mb_strtolower($q);
                $w->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

return view('content.master-data.partners.index', compact('items', 'q'));

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
            'name' => ['required', 'string', 'max:255', 'unique:partners,name,' . $partner->getKey()],
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
