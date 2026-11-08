<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'immostore_backoffice';

    public function up(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasTable('permissions')) {
            return;
        }

        $connection = DB::connection(self::CONNECTION);

        $duplicates = $connection->table('permissions')
            ->select('name', 'guard_name')
            ->groupBy('name', 'guard_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = $connection->table('permissions')
                ->where('name', $duplicate->name)
                ->where('guard_name', $duplicate->guard_name)
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $canonicalId = $ids->shift();

            foreach ($ids as $duplicateId) {
                $roleIds = $connection->table('role_has_permissions')
                    ->where('permission_id', $duplicateId)
                    ->pluck('role_id');

                foreach ($roleIds as $roleId) {
                    $exists = $connection->table('role_has_permissions')
                        ->where('permission_id', $canonicalId)
                        ->where('role_id', $roleId)
                        ->exists();

                    if (! $exists) {
                        $connection->table('role_has_permissions')->insert([
                            'permission_id' => $canonicalId,
                            'role_id' => $roleId,
                        ]);
                    }
                }

                $connection->table('role_has_permissions')->where('permission_id', $duplicateId)->delete();

                $modelPermissions = $connection->table('model_has_permissions')
                    ->where('permission_id', $duplicateId)
                    ->get(['model_type', 'model_id']);

                foreach ($modelPermissions as $modelPermission) {
                    $exists = $connection->table('model_has_permissions')
                        ->where('permission_id', $canonicalId)
                        ->where('model_type', $modelPermission->model_type)
                        ->where('model_id', $modelPermission->model_id)
                        ->exists();

                    if (! $exists) {
                        $connection->table('model_has_permissions')->insert([
                            'permission_id' => $canonicalId,
                            'model_type' => $modelPermission->model_type,
                            'model_id' => $modelPermission->model_id,
                        ]);
                    }
                }

                $connection->table('model_has_permissions')->where('permission_id', $duplicateId)->delete();
                $connection->table('permissions')->where('id', $duplicateId)->delete();
            }
        }

        $this->createUniqueIndexIfMissing('permissions_name_guard_name_unique', 'permissions', 'name, guard_name');
    }

    public function down(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasTable('permissions')) {
            return;
        }

        DB::connection(self::CONNECTION)->statement('DROP INDEX IF EXISTS permissions_name_guard_name_unique');
    }

    private function createUniqueIndexIfMissing(string $indexName, string $table, string $columns): void
    {
        $connection = DB::connection(self::CONNECTION);

        $exists = $connection->selectOne(
            'SELECT 1 FROM pg_indexes WHERE schemaname = ? AND indexname = ?',
            ['public', $indexName]
        );

        if ($exists) {
            return;
        }

        $connection->statement("CREATE UNIQUE INDEX {$indexName} ON {$table} ({$columns})");
    }
};
