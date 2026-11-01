<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class EcommerceBannerPermissionSeeder extends Seeder
{
    /**
     * Seeder permission untuk halaman Master Data > Ecommerce Banners
     * Koneksi: immostore_backoffice
     */
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {

            // Daftar permission banner
            $permissions = [
                'view master-data-ecommerce-banners',
                'create master-data-ecommerce-banners',
                'update master-data-ecommerce-banners',
                'delete master-data-ecommerce-banners',
            ];

            // Create permission jika belum ada
            foreach ($permissions as $name) {
                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
            }

            // Pastikan role super admin tersedia
            $superAdminA = Role::firstOrCreate([
                'name'       => 'super-admin',
                'guard_name' => 'web',
            ]);

            $superAdminB = Role::firstOrCreate([
                'name'       => 'Superadmin',
                'guard_name' => 'web',
            ]);

            // Assign permission ke role
            $superAdminA->givePermissionTo($permissions);
            $superAdminB->givePermissionTo($permissions);
        });
    }
}
