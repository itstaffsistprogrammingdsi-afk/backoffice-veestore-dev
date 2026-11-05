<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Backoffice\Role;                // users (pgsql dev_immostore)
use App\Models\User;     // roles (immostore_backoffice, id = uuid)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('name', 'ILIKE', "%{$q}%")
                        ->orWhere('email', 'ILIKE', "%{$q}%")
                        ->orWhere('username', 'ILIKE', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get();

        // Ambil 1 role per user (kalau ada). Jika ada lebih dari satu di DB, ambil yang pertama saja.
        $pivotRows = DB::connection('immostore_backoffice')
            ->table(config('permission.table_names.model_has_roles'))
            ->where('model_type', User::class)
            ->select('model_id', 'role_id')
            ->get()
            ->groupBy('model_id');

        $userRoleId = []; // <— single value per user
        foreach ($pivotRows as $uid => $rows) {
            $first = $rows->first();
            if ($first) {
                $userRoleId[$uid] = $first->role_id; // uuid string
            }
        }

        return Inertia::render('Management/UserRoles', [
            'users' => $users,
            'roles' => $roles,
            'userRoleId' => $userRoleId,
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('management.user-roles.index'),
                'sync' => route('management.user-roles.sync', ['user' => '__ID__']),
            ],
        ]);
    }

    public function sync(string $userId, Request $request)
    {
        $user = User::query()->findOrFail($userId);

        // role_id boleh null (untuk menghapus role)
        $roleId = $request->input('role_id') ?: null;

        if ($roleId !== null && ! Role::query()->where('id', $roleId)->exists()) {
            return back()->withErrors(['role_id' => 'Role tidak ditemukan.']);
        }

        DB::connection('immostore_backoffice')->transaction(function () use ($user, $roleId) {
            $table = config('permission.table_names.model_has_roles');

            // Hapus semua assignment lama user ini
            DB::connection('immostore_backoffice')
                ->table($table)
                ->where('model_type', User::class)
                ->where('model_id', $user->getKey())
                ->delete();

            // Jika pilih role (bukan clear), insert satu baris
            if ($roleId) {
                DB::connection('immostore_backoffice')->table($table)->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $user->getKey(),
                ]);
            }
        });

        return back()->with('success', 'Peran pengguna berhasil diperbarui.');
    }
}
