<?php

namespace Database\Seeders;

use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class StoreSchedulePermissionSeeder extends Seeder
{
    private const VIEW = 'view branch-store-schedule';

    private const APPROVE = 'approve branch-store-schedule';

    public function run(): void
    {
        DB::connection('immostore_backoffice')->transaction(function (): void {
            foreach ([self::VIEW, self::APPROVE] as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            $view = Permission::where('name', self::VIEW)->firstOrFail();
            $approve = Permission::where('name', self::APPROVE)->firstOrFail();

            $role = fn (string $name): ?Role => Role::where('name', $name)->first();

            $role('Store Operations')?->givePermissionTo($view);
            $role('HRD')?->givePermissionTo($view);
            $role('Management')?->givePermissionTo([$view, $approve]);

            foreach (['Superadmin', 'super-admin'] as $name) {
                $role($name)?->givePermissionTo([$view, $approve]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
