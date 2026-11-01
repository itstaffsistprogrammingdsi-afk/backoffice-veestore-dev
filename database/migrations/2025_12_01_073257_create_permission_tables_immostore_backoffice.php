<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $connectionName = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connectionName);
        $tables = [
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];
        $existingTables = array_values(array_filter(
            $tables,
            fn (string $table): bool => $schema->hasTable($table)
        ));

        // Migration lama pernah dicatat pada koneksi backoffice, sedangkan
        // `php artisan migrate` membaca riwayat koneksi utama. Jangan membuat
        // ulang tabel permission yang sudah berisi data.
        if (count($existingTables) === count($tables)) {
            return;
        }

        if ($existingTables !== []) {
            throw new RuntimeException(
                'Schema permission backoffice tidak lengkap. Tabel yang sudah ada: '.implode(', ', $existingTables)
            );
        }

        // ==========================
        // PERMISSIONS (UUID)
        // ==========================
        $schema->create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        // ==========================
        // ROLES (UUID)
        // ==========================
        $schema->create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        // ==========================
        // MODEL_HAS_PERMISSIONS (UUID)
        // ==========================
        $schema->create('model_has_permissions', function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->string('model_type');
            $table->uuid('model_id'); // User UUID

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_primary');
        });

        // ==========================
        // MODEL_HAS_ROLES (UUID)
        // ==========================
        $schema->create('model_has_roles', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->string('model_type');
            $table->uuid('model_id'); // User UUID

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_primary');
        });

        // ==========================
        // ROLE_HAS_PERMISSIONS (UUID)
        // ==========================
        $schema->create('role_has_permissions', function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->uuid('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_primary');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connectionName);

        $schema->dropIfExists('role_has_permissions');
        $schema->dropIfExists('model_has_roles');
        $schema->dropIfExists('model_has_permissions');
        $schema->dropIfExists('roles');
        $schema->dropIfExists('permissions');
    }
};
