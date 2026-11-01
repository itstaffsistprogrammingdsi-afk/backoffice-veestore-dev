<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class ProductSpecificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan cache permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-product-specifications',
                'create master-data-product-specifications',
                'update master-data-product-specifications',
                'delete master-data-product-specifications',
            ];

            // Buat permissions di koneksi immostore_backoffice
            foreach ($perms as $name) {
                Permission::on('immostore_backoffice')
                    ->firstOrCreate([
                        'name'       => $name,
                        'guard_name' => 'web',
                    ]);
            }

            // Pastikan super-admin ada di koneksi yang sama
            $superAdmin = Role::on('immostore_backoffice')
                ->firstOrCreate([
                    'name'       => 'super-admin',
                    'guard_name' => 'web',
                ]);

            // Ambil semua permission & assign ke super-admin
            $allPerms = Permission::on('immostore_backoffice')->get();
            $superAdmin->syncPermissions($allPerms);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
