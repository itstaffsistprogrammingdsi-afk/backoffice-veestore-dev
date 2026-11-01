<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;                // users (pgsql dev_immostore)
use App\Models\Backoffice\Role;     // roles (immostore_backoffice, id = uuid)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->select('model_id','role_id')
            ->get()
            ->groupBy('model_id');

        $userRoleId = []; // <— single value per user
        foreach ($pivotRows as $uid => $rows) {
            $first = $rows->first();
            if ($first) {
                $userRoleId[$uid] = $first->role_id; // uuid string
            }
        }

        return view('content.management.user-roles.index', [
            'users'       => $users,
            'roles'       => $roles,
            'userRoleId'  => $userRoleId, // <— per user: single role id (or null)
            'q'           => $q,
        ]);
    }

    public function sync(string $userId, Request $request)
    {
        $user = User::query()->findOrFail($userId);

        // role_id boleh null (untuk menghapus role)
        $roleId = $request->input('role_id');

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
                    'role_id'    => $roleId,
                    'model_type' => User::class,
                    'model_id'   => $user->getKey(),
                ]);
            }
        });

        return back()->with('success', 'Role user berhasil diperbarui.');
    }
}
