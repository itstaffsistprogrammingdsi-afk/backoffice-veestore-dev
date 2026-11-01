<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// Jika pakai Spatie default, ganti 2 baris di bawah ini:
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class LoyaltyPointMutationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            // Halaman read-only: cukup 1 permission "view"
            $permName = 'view loyalty-customer-loyalty-point-mutations';

            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
                []
            );

            // Beri semua permission ke super-admin (opsional)
            $superAdmin = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            $superAdmin->syncPermissions(Permission::all());
        });
    }
}
