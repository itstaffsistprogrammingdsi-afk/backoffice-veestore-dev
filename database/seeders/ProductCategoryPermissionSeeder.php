<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;
use Illuminate\Support\Facades\DB;

class ProductCategoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            // Daftar permission untuk halaman Product Category
            $permissions = [
                'view master-data-product-categories',
                'create master-data-product-categories',
                'update master-data-product-categories',
                'delete master-data-product-categories',
            ];

            foreach ($permissions as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm, 'guard_name' => 'web']
                );
            }

            // Role Super Admin
            $superAdmin = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            // Beri semua permission ke super admin
            $superAdmin->syncPermissions(Permission::all());
        });
    }
}
