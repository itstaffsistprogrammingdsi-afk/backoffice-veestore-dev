<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Seluruh operasi ke koneksi backoffice (PostgreSQL)
    protected $connection = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('users')) {
            return;
        }

        // Tambahkan kolom hanya jika belum ada
        if (! $schema->hasColumn('users', 'active')) {
            $schema->table('users', function (Blueprint $table) {
                // varchar(20) dengan default 'active'
                $table->string('active', 20)->default('active')->index();
            });

            // Tambahkan CHECK constraint agar hanya 'active' / 'non_active'
            try {
                DB::connection($this->connection)->statement(
                    "ALTER TABLE users
                     ADD CONSTRAINT users_active_chk
                     CHECK (active IN ('active','non_active'))"
                );
            } catch (\Throwable $e) {
                // Abaikan jika constraint sudah ada
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('users') && $schema->hasColumn('users', 'active')) {
            // Hapus constraint jika ada
            try {
                DB::connection($this->connection)->statement(
                    "ALTER TABLE users DROP CONSTRAINT IF EXISTS users_active_chk"
                );
            } catch (\Throwable $e) {
                // abaikan
            }

            // Hapus kolom
            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};