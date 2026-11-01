<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MembershipController extends Controller
{
    public function __construct()
    {
        // Sesuaikan dengan seeder permission
        $this->middleware('permission:view master-data-memberships')->only(['index']);
        $this->middleware('permission:create master-data-memberships')->only(['store']);
        $this->middleware('permission:update master-data-memberships')->only(['update']);
        $this->middleware('permission:delete master-data-memberships')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $memberships = Membership::when($q, function ($query) use ($q) {
            $query->where('name', 'ILIKE', "%{$q}%");
        })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Memberships',
            'description' => 'Kelola tier membership pelanggan.',
            'searchPlaceholder' => 'Cari membership...',
            'items' => $memberships,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Membership', 'required' => true],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.memberships.index'),
                'store' => route('master-data.memberships.store'),
                'update' => route('master-data.memberships.update', ['membership' => '__ID__']),
                'destroy' => route('master-data.memberships.destroy', ['membership' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:memberships,name'],
        ]);

        Membership::create($data);

        return redirect()
            ->route('master-data.memberships.index')
            ->with('success', 'Membership berhasil ditambahkan.');
    }

    public function update(Request $request, Membership $membership)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('memberships', 'name')->ignore($membership->id, 'id'),
            ],
        ]);

        $membership->update($data);

        return redirect()
            ->route('master-data.memberships.index')
            ->with('success', 'Membership berhasil diperbarui.');
    }

    public function destroy(Membership $membership)
    {
        $membership->delete();

        return back()->with('success', 'Membership berhasil dihapus.');
    }
}
