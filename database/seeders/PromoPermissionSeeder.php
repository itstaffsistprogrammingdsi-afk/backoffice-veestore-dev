<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class PromoPermissionSeeder extends Seeder
{
    /**
     * Membuat permissions untuk menu Promos dan assign ke role super-admin/Superadmin.
     * Ubah connection 'immostore_backoffice' jika projectmu memakai koneksi lain.
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-promos',
                'create master-data-promos',
                'update master-data-promos',
                'delete master-data-promos',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
            }

            // Pastikan role ada
            $superAdminA = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $superAdminB = Role::firstOrCreate(['name' => 'Superadmin',  'guard_name' => 'web']);

            // Berikan semua permission Promos ke kedua role
            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
