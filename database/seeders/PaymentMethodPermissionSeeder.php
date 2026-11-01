<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;

class PaymentMethodPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function () {
            $perms = [
                'view master-data-payment-methods',
                'create master-data-payment-methods',
                'update master-data-payment-methods',
                'delete master-data-payment-methods',
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(
                    ['name' => $p, 'guard_name' => 'web']
                );
            }

            $super = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            // Kasih semua permission ke super-admin
            $super->syncPermissions(Permission::all());
        });
    }
}
