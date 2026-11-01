<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class MembershipPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-memberships',
                'create master-data-memberships',
                'update master-data-memberships',
                'delete master-data-memberships',
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(
                    ['name' => $p, 'guard_name' => 'web']
                );
            }

            $super = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            // Berikan semua permission ke super-admin (atau pilih yang perlu saja)
            $super->syncPermissions(Permission::all());
        });
    }
}
