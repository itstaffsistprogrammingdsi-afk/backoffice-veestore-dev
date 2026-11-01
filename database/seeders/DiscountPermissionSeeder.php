<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class DiscountPermissionSeeder extends Seeder
{
    /**
     * Buat permission untuk menu Discounts dan assign ke role super-admin/Superadmin.
     * Sesuaikan connection bila perlu (contoh: immostore_backoffice).
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-discounts',
                'create master-data-discounts',
                'update master-data-discounts',
                'delete master-data-discounts',
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

            // Assign semua permission Discounts
            $superAdminA->givePermissionTo($perms);
            $superAdminB->givePermissionTo($perms);
        });
    }
}
