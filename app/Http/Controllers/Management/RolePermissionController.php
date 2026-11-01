<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Backoffice\Role;
use App\Models\Backoffice\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index()
    {
        // Ambil roles & permissions dari koneksi backoffice
        $roles = Role::on('immostore_backoffice')
            ->with('permissions:id,name') // eager load untuk hindari N+1
            ->orderBy('name')->get();

        $permissions = Permission::on('immostore_backoffice')
            ->orderBy('name')->get();

        $permissions->each(function ($permission) {
            [$action, $resource] = array_pad(
                preg_split('/\s+/', trim($permission->name), 2),
                2,
                ''
            );

            $permission->display_action = $this->actionLabel($action);
            $permission->display_resource = Str::of($resource ?: $permission->name)
                ->replace(['-', '_'], ' ')
                ->title()
                ->toString();
            $permission->display_group = $this->permissionGroup($resource);
        });

        $permissionGroups = $permissions->groupBy('display_group');

        // Buat lookup: role_id => [permName1, permName2, ...]
        $rolePermissions = [];
        foreach ($roles as $r) {
            $rolePermissions[$r->id] = $r->permissions->pluck('name')->all();
        }

        return view('content.management.role-permissions.index', [
            'roles'           => $roles,
            'permissions'     => $permissions,
            'permissionGroups' => $permissionGroups,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function update(Request $request)
    {
        // Input berbentuk: matrix[role_id][] = permission_name
        $matrix = (array) $request->input('matrix', []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::connection('immostore_backoffice')->transaction(function () use ($matrix) {
            // Ambil semua role sekali jalan
            $roles = Role::on('immostore_backoffice')->get()->keyBy('id');

            foreach ($roles as $roleId => $role) {
                $names = array_values(array_unique((array)($matrix[$roleId] ?? [])));

                // Ambil Permission model berdasarkan name pada koneksi yang sama
                $permModels = Permission::on('immostore_backoffice')
                    ->whereIn('name', $names)
                    ->get();

                // Sinkronkan; role yang tidak dicentang permission-nya akan dikosongkan
                $role->syncPermissions($permModels);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permissions berhasil diperbarui.');
    }

    private function actionLabel(string $action): string
    {
        return match (strtolower($action)) {
            'view', 'read', 'show', 'index' => 'Lihat',
            'create', 'store', 'add' => 'Tambah',
            'update', 'edit' => 'Ubah',
            'delete', 'destroy', 'remove' => 'Hapus',
            'approve' => 'Setujui',
            'reject' => 'Tolak',
            'export', 'download' => 'Ekspor',
            'manage' => 'Kelola',
            default => Str::headline($action ?: 'Akses'),
        };
    }

    private function permissionGroup(string $resource): string
    {
        $resource = strtolower($resource);

        return match (true) {
            str_starts_with($resource, 'monitoring') => 'Monitoring',
            str_starts_with($resource, 'master') => 'Master Data',
            str_starts_with($resource, 'loyalty') => 'Loyalty & CRM',
            str_starts_with($resource, 'management'),
            str_contains($resource, 'role'),
            str_contains($resource, 'permission'),
            str_contains($resource, 'user') => 'User & Akses',
            str_starts_with($resource, 'report'),
            str_contains($resource, 'supplier-product') => 'Laporan',
            str_contains($resource, 'branch'),
            str_contains($resource, 'order'),
            str_contains($resource, 'return') => 'Operasional',
            default => 'Lainnya',
        };
    }
}
