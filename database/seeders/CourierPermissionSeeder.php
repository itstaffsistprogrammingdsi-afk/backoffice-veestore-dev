<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class CourierPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk menu Couriers dan assign ke role super-admin/Superadmin.
     * Jika pakai koneksi lain (contoh: immostore_backoffice), ganti connection sesuai kebutuhan.
     */
    public function run(): void
    {
        // Ganti 'mysql' dengan koneksi yang tepat bila perlu.
        DB::connection('immostore_backoffice')->transaction(function () {
            // daftar permission untuk halaman Couriers
            $perms = [
                'view master-data-couriers',
                'create master-data-couriers',
                'update master-data-couriers',
                'delete master-data-couriers',
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

            // Assign seluruh permission Couriers ke kedua role tersebut
            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
