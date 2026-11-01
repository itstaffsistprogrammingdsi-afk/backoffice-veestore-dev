<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class SupplierPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk menu Suppliers dan assign ke role super-admin/Superadmin.
     * Ganti connection jika perlu (contoh: immostore_backoffice).
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-suppliers',
                'create master-data-suppliers',
                'update master-data-suppliers',
                'delete master-data-suppliers',
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

            // Assign seluruh permission Suppliers ke kedua role tersebut
            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
