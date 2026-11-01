<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class LocationWarehousePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-locations-warehouse',
                'create master-data-locations-warehouse',
                'update master-data-locations-warehouse',
                'delete master-data-locations-warehouse',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['name' => $name, 'guard_name' => 'web']
                );
            }

            $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $role->syncPermissions(Permission::all()); // berikan semua permission ke super-admin
        });
    }
}
