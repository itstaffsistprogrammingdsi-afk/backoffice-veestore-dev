<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class VoucherPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk modul Vouchers dan assign ke role super-admin/Superadmin.
     * Gunakan koneksi 'immostore_backoffice' (sesuaikan jika berbeda).
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-vouchers',
                'create master-data-vouchers',
                'update master-data-vouchers',
                'delete master-data-vouchers',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
            }

            $superAdminA = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $superAdminB = Role::firstOrCreate(['name' => 'Superadmin',  'guard_name' => 'web']);

            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
