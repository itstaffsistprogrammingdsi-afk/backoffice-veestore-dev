<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Store;            // ← tambahkan
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManageController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::with(['warehouses', 'stores']) // ← load stores juga
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('username', 'ILIKE', "%{$q}%")
                        ->orWhere('email', 'ILIKE', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        $stores     = Store::orderBy('name')->get(['id', 'name']);  // ← ambil daftar store

        return view('content.master-data.users.index', compact('users', 'q', 'warehouses', 'stores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'unique:pgsql.users,username'],
            'email'    => ['required', 'email', 'max:191', 'unique:pgsql.users,email'],
            'password' => ['required', 'string', 'min:6'],

            'wms_access'        => ['nullable', 'boolean'],
            'backoffice_access' => ['nullable', 'boolean'],
            'store_access'      => ['nullable', 'boolean'],

            'warehouse_ids'   => ['nullable', 'array'],
            'warehouse_ids.*' => ['uuid', 'exists:warehouses,id'],

            'store_ids'       => ['nullable', 'array'],                 // ← validasi store
            'store_ids.*'     => ['uuid', 'exists:stores,id'],
        ]);

        // Normalisasi flag
        $data['wms_access']        = $request->boolean('wms_access');
        $data['backoffice_access'] = $request->boolean('backoffice_access');
        $data['store_access']      = $request->boolean('store_access');
        $data += ['ecommerce_access' => false];

        $user = User::create($data);

        // Relasi many-to-many
        $user->warehouses()->sync($data['wms_access']   ? ($request->warehouse_ids ?? []) : []);
        $user->stores()->sync($data['store_access']     ? ($request->store_ids ?? [])     : []); // ← simpan stores

        return redirect()->route('master-data.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', Rule::unique('pgsql.users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:191', Rule::unique('pgsql.users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],

            'wms_access'        => ['nullable', 'boolean'],
            'backoffice_access' => ['nullable', 'boolean'],
            'store_access'      => ['nullable', 'boolean'],

            'warehouse_ids'   => ['nullable', 'array'],
            'warehouse_ids.*' => ['uuid', 'exists:warehouses,id'],

            'store_ids'       => ['nullable', 'array'],                 // ← validasi store
            'store_ids.*'     => ['uuid', 'exists:stores,id'],
        ]);

        if (empty($data['password'])) unset($data['password']);

        $data['wms_access']        = $request->boolean('wms_access');
        $data['backoffice_access'] = $request->boolean('backoffice_access');
        $data['store_access']      = $request->boolean('store_access');

        $user->update($data);

        $user->warehouses()->sync($data['wms_access'] ? ($request->warehouse_ids ?? []) : []);
        $user->stores()->sync($data['store_access']   ? ($request->store_ids ?? [])     : []); // ← update stores

        return redirect()->route('master-data.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        // lepas relasi dulu biar FK aman
        $user->warehouses()->detach();
        $user->stores()->detach(); // ← lepas relasi stores
        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}