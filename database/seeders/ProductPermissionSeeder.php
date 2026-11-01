<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
// ⬇️ gunakan model turunan (sudah diarahkan ke koneksi immostore_backoffice)
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class ProductPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan cache permission sebelum & sesudah seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-products',
                'create master-data-products',
                'update master-data-products',
                'delete master-data-products',
            ];

            // Pastikan create/read terjadi di koneksi immostore_backoffice
            foreach ($perms as $name) {
                Permission::on('immostore_backoffice')
                    ->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }

            // Pastikan role juga dibuat di koneksi yang sama
            $superAdmin = Role::on('immostore_backoffice')
                ->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

            // Ambil semua permission dari koneksi immostore_backoffice & assign
            $allPerms = Permission::on('immostore_backoffice')->get();
            $superAdmin->syncPermissions($allPerms);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
