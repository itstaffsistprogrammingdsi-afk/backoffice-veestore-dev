<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class BrandPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk menu Brands dan assign ke role super-admin/Superadmin.
     * Koneksi: immostore_backoffice
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-brands',
                'create master-data-brands',
                'update master-data-brands',
                'delete master-data-brands',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            $superAdminA = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $superAdminB = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);

            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
