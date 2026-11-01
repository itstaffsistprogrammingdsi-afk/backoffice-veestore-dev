<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
// ⬇️ gunakan model turunan (sudah diarahkan ke koneksi immostore_backoffice)
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class ProductPricePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // bersihkan cache permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::connection('immostore_backoffice')->transaction(function () {
            // CRUD permissions utk Product Prices (sesuai controller/blade)
            $perms = [
                'view master-data-product-prices',
                'create master-data-product-prices',
                'update master-data-product-prices',
                'delete master-data-product-prices',
            ];

            foreach ($perms as $name) {
                Permission::on('immostore_backoffice')
                    ->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }

            // pastikan super-admin ada & pegang semuanya
            $superAdmin = Role::on('immostore_backoffice')
                ->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

            $all = Permission::on('immostore_backoffice')->get();
            $superAdmin->syncPermissions($all);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
