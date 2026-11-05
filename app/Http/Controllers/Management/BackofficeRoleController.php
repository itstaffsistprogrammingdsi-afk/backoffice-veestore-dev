<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Backoffice\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BackofficeRoleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $roles = Role::when($q !== '', function ($w) use ($q) {
            $w->where('name', 'ILIKE', "%{$q}%")
                ->orWhere('guard_name', 'ILIKE', "%{$q}%");
        })
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Management/Roles', [
            'roles' => $roles,
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('management.roles-backoffice.index'),
                'store' => route('management.roles-backoffice.store'),
                'update' => route('management.roles-backoffice.update', ['roles_backoffice' => '__ID__']),
                'destroy' => route('management.roles-backoffice.destroy', ['roles_backoffice' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:190',
                Rule::unique('immostore_backoffice.roles', 'name'),
            ],
        ]);

        Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Peran berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:190',
                Rule::unique('immostore_backoffice.roles', 'name')->ignore($role->getKey(), 'id'),
            ],
        ]);

        $role->update(['name' => $data['name']]);

        return back()->with('success', 'Peran berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Peran berhasil dihapus.');
    }
}
