<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE:
     * - Migration ini membaca koneksi & nama tabel dari config/permission.php
     * - Pastikan 'connection' di config/permission.php = 'immostore_backoffice'
     * - Roles & Permissions pakai UUID (bukan BIGINT)
     * - model_id juga UUID agar cocok dengan users.id (UUID) di dev_immostore
     */

    public function up(): void
    {
        $connection    = config('permission.connection');            // ex: immostore_backoffice
        $teams         = config('permission.teams');                 // bool
        $tableNames    = config('permission.table_names');           // array
        $columnNames   = config('permission.column_names');          // array
        $pivotRole     = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPerm     = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelKey      = $columnNames['model_morph_key'] ?? 'model_id';
        $teamFk        = $columnNames['team_foreign_key'] ?? 'team_id';

        throw_if(empty($tableNames), new \Exception('config/permission.php tidak terbaca. Jalankan: php artisan config:clear'));

        if ($teams && empty($teamFk)) {
            throw new \Exception('team_foreign_key di config/permission.php kosong. Jalankan: php artisan config:clear');
        }

        // ===== permissions (UUID) =====
        Schema::connection($connection)->create($tableNames['permissions'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // ===== roles (UUID) =====
        Schema::connection($connection)->create($tableNames['roles'], function (Blueprint $table) use ($teams, $teamFk) {
            $table->uuid('id')->primary();
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($teamFk)->nullable();
                $table->index($teamFk, 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            if ($teams || config('permission.testing')) {
                $table->unique([$teamFk, 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        // ===== model_has_permissions (permission_id UUID, model_id UUID) =====
        Schema::connection($connection)->create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $modelKey, $pivotPerm, $teams, $teamFk) {
            $table->uuid($pivotPerm);
            $table->string('model_type');
            $table->uuid($modelKey);
            $table->index([$modelKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPerm)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($teamFk);
                $table->index($teamFk, 'model_has_permissions_team_foreign_key_index');
                $table->primary([$teamFk, $pivotPerm, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPerm, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }
        });

        // ===== model_has_roles (role_id UUID, model_id UUID) =====
        Schema::connection($connection)->create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $modelKey, $pivotRole, $teams, $teamFk) {
            $table->uuid($pivotRole);
            $table->string('model_type');
            $table->uuid($modelKey);
            $table->index([$modelKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($teamFk);
                $table->index($teamFk, 'model_has_roles_team_foreign_key_index');
                $table->primary([$teamFk, $pivotRole, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        // ===== role_has_permissions (keduanya UUID) =====
        Schema::connection($connection)->create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPerm) {
            $table->uuid($pivotPerm);
            $table->uuid($pivotRole);

            $table->foreign($pivotPerm)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPerm, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        // clear cache spatie
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $connection  = config('permission.connection');
        $tableNames  = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('config/permission.php tidak ditemukan. Hapus tabel secara manual.');
        }

        Schema::connection($connection)->dropIfExists($tableNames['role_has_permissions']);
        Schema::connection($connection)->dropIfExists($tableNames['model_has_roles']);
        Schema::connection($connection)->dropIfExists($tableNames['model_has_permissions']);
        Schema::connection($connection)->dropIfExists($tableNames['roles']);
        Schema::connection($connection)->dropIfExists($tableNames['permissions']);
    }
};
