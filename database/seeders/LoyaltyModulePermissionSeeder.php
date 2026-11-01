<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// Ganti ke model permission/role milikmu sendiri
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class LoyaltyModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            $perms = [
                // Read-only pages
                'view loyalty-customer-loyalty-points',
                'view loyalty-customer-loyalty-point-mutations',
                'view loyalty-customer-visits',
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(
                    ['name' => $p, 'guard_name' => 'web'],
                    []
                );
            }

            // Optional: role super-admin dapat semua permission
            $superAdmin = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            $superAdmin->syncPermissions(Permission::all());
        });
    }
}
