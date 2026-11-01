<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class UnitPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk menu Units dan assign ke role super-admin/Superadmin.
     * Koneksi: immostore_backoffice
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            // daftar permission untuk halaman Units
            $perms = [
                'view master-data-units',
                'create master-data-units',
                'update master-data-units',
                'delete master-data-units',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
            }

            // Pastikan role ada (kedua nama ini sering dipakai di projectmu)
            $superAdminA = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $superAdminB = Role::firstOrCreate(['name' => 'Superadmin',  'guard_name' => 'web']);

            // Assign seluruh permission Units ke kedua role tersebut
            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
