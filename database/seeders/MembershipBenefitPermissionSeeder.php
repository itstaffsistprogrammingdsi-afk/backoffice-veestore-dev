<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// SESUAIKAN: kalau kamu pakai Spatie default, ganti 2 baris di bawah:
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class MembershipBenefitPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-membership-benefits',
                'create master-data-membership-benefits',
                'update master-data-membership-benefits',
                'delete master-data-membership-benefits',
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(
                    ['name' => $p, 'guard_name' => 'web'],
                    []
                );
            }

            $superAdmin = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            $superAdmin->syncPermissions(Permission::all());
        });
    }
}
