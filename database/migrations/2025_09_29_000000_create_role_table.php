<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Semua operasi migration diarahkan ke database veestore_bo_dev.
     */
    protected $connection = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $database = DB::connection($this->connection);

        if (!$schema->hasTable('role')) {
            $schema->create('role', function (Blueprint $table) {
                $table
                    ->uuid('id')
                    ->primary()
                    ->default(DB::raw('gen_random_uuid()'));

                $table->string('name_role', 100);

                $table->timestampsTz();
                $table->softDeletesTz();
            });
        }

        /*
         * Membuat unique index case-insensitive.
         *
         * IF NOT EXISTS mencegah error apabila index sudah tersedia.
         * Index hanya berlaku untuk data yang belum di-soft delete.
         */
        $database->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS role_name_role_unique
             ON role (LOWER(name_role))
             WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        $database = DB::connection($this->connection);

        $database->statement(
            'DROP INDEX IF EXISTS role_name_role_unique'
        );

        Schema::connection($this->connection)
            ->dropIfExists('role');
    }
};