<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class SupplierProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            $perms = [
                'view supplier-product',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['name' => $name, 'guard_name' => 'web']
                );
            }

            $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $role->syncPermissions(Permission::all());
        });
    }
}
