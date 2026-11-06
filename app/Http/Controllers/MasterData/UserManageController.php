<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;            // ← tambahkan
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
        $stores = Store::orderBy('name')->get(['id', 'name']);  // ← ambil daftar store

        $users->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'wms_access' => $user->wms_access ? '1' : '0',
            'backoffice_access' => $user->backoffice_access ? '1' : '0',
            'store_access' => $user->store_access ? '1' : '0',
            'warehouse_ids' => $user->warehouses->pluck('id')->all(),
            'store_ids' => $user->stores->pluck('id')->all(),
        ]);

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Pengguna',
            'description' => 'Kelola user backoffice dan aksesnya.',
            'searchPlaceholder' => 'Cari user...',
            'items' => $users,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'username', 'label' => 'Username'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'backoffice_access', 'label' => 'Master Data & Report'],
                ['key' => 'wms_access', 'label' => 'WMS'],
                ['key' => 'store_access', 'label' => 'Store'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama', 'required' => true],
                ['key' => 'username', 'label' => 'Username', 'required' => true],
                ['key' => 'email', 'label' => 'Email', 'required' => true],
                ['key' => 'password', 'label' => 'Password', 'type' => 'text', 'placeholder' => 'Kosongkan jika tidak diubah'],
                ['key' => 'backoffice_access', 'label' => 'Akses Master Data & Report', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
                ['key' => 'wms_access', 'label' => 'Akses WMS', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
                ['key' => 'store_access', 'label' => 'Akses Store', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
                ['key' => 'warehouse_ids', 'label' => 'Warehouses', 'type' => 'multiselect', 'options' => $warehouses->map(fn ($warehouse) => ['value' => $warehouse->id, 'label' => $warehouse->name])->values()->all()],
                ['key' => 'store_ids', 'label' => 'Stores', 'type' => 'multiselect', 'options' => $stores->map(fn ($store) => ['value' => $store->id, 'label' => $store->name])->values()->all()],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.users.index'),
                'store' => route('master-data.users.store'),
                'update' => route('master-data.users.update', ['user' => '__ID__']),
                'destroy' => route('master-data.users.destroy', ['user' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'unique:pgsql.users,username'],
            'email' => ['required', 'email', 'max:191', 'unique:pgsql.users,email'],
            'password' => ['required', 'string', 'min:6'],

            'wms_access' => ['nullable', 'boolean'],
            'backoffice_access' => ['nullable', 'boolean'],
            'store_access' => ['nullable', 'boolean'],

            'warehouse_ids' => ['nullable', 'array'],
            'warehouse_ids.*' => ['uuid', 'exists:warehouses,id'],

            'store_ids' => ['nullable', 'array'],                 // ← validasi store
            'store_ids.*' => ['uuid', 'exists:stores,id'],
        ]);

        // Normalisasi flag
        $data['wms_access'] = $request->boolean('wms_access');
        $data['backoffice_access'] = $request->boolean('backoffice_access');
        $data['store_access'] = $request->boolean('store_access');
        $data += ['ecommerce_access' => false];

        $user = User::create($data);

        // Relasi many-to-many
        $user->warehouses()->sync($data['wms_access'] ? ($request->warehouse_ids ?? []) : []);
        $user->stores()->sync($data['store_access'] ? ($request->store_ids ?? []) : []); // ← simpan stores

        return redirect()->route('master-data.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', Rule::unique('pgsql.users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:191', Rule::unique('pgsql.users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],

            'wms_access' => ['nullable', 'boolean'],
            'backoffice_access' => ['nullable', 'boolean'],
            'store_access' => ['nullable', 'boolean'],

            'warehouse_ids' => ['nullable', 'array'],
            'warehouse_ids.*' => ['uuid', 'exists:warehouses,id'],

            'store_ids' => ['nullable', 'array'],                 // ← validasi store
            'store_ids.*' => ['uuid', 'exists:stores,id'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['wms_access'] = $request->boolean('wms_access');
        $data['backoffice_access'] = $request->boolean('backoffice_access');
        $data['store_access'] = $request->boolean('store_access');

        $user->update($data);

        $user->warehouses()->sync($data['wms_access'] ? ($request->warehouse_ids ?? []) : []);
        $user->stores()->sync($data['store_access'] ? ($request->store_ids ?? []) : []); // ← update stores

        return redirect()->route('master-data.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        // lepas relasi dulu biar FK aman
        $user->warehouses()->detach();
        $user->stores()->detach(); // ← lepas relasi stores
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
