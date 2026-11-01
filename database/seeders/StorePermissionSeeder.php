<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class StorePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-stores',
                'create master-data-stores',
                'update master-data-stores',
                'delete master-data-stores',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['name' => $name, 'guard_name' => 'web']
                );
            }

            // Pastikan role super-admin ada, dan beri semua permission
            $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $superAdmin->syncPermissions(Permission::all());
        });
    }
}
