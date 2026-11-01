<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class ItemConditionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            // Permission sesuai controller & blade
            $perms = [
                'view master-data-item-conditions',
                'create master-data-item-conditions',
                'update master-data-item-conditions',
                'delete master-data-item-conditions',
            ];

            foreach ($perms as $name) {
                Permission::firstOrCreate(
                    [
                        'name'       => $name,
                        'guard_name' => 'web',
                    ],
                    [
                        'name'       => $name,
                        'guard_name' => 'web',
                    ]
                );
            }

            // Attach ke super-admin
            $role = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            // Biasanya super-admin dapat semua permission
            $role->syncPermissions(Permission::all());
        });
    }
}
