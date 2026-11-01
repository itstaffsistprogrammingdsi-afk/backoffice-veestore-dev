<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Pastikan semua operasi ke DB backoffice
    protected $connection = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // 1) Jika ada 'role_id' dan belum ada 'id_role', rename kolomnya (tanpa doctrine/dbal)
        if ($schema->hasTable('users')) {
            $hasRoleId = $schema->hasColumn('users', 'role_id');
            $hasIdRole = $schema->hasColumn('users', 'id_role');

            if ($hasRoleId && ! $hasIdRole) {
                DB::connection($this->connection)
                    ->statement('ALTER TABLE users RENAME COLUMN role_id TO id_role;');
                $hasIdRole = true; // sekarang sudah ada
            }

            // 2) Jika 'id_role' belum ada sama sekali, tambahkan kolom
            if (! $hasIdRole) {
                $schema->table('users', function (Blueprint $table) {
                    // tambahkan setelah password (opsional, untuk kerapian)
                    $table->uuid('id_role')->nullable()->index()->after('password');
                });
            }

            // 3) Tambahkan FK ke role(id) jika memungkinkan (abaikan error jika constraint sudah ada)
            if ($schema->hasTable('role')) {
                try {
                    DB::connection($this->connection)->statement(
                        'ALTER TABLE users
                         ADD CONSTRAINT users_id_role_foreign
                         FOREIGN KEY (id_role) REFERENCES role(id)
                         ON DELETE SET NULL;'
                    );
                } catch (\Throwable $e) {
                    // kemungkinan constraint sudah ada → aman untuk diabaikan
                }
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('users') && $schema->hasColumn('users', 'id_role')) {
            // hapus FK kalau ada
            try {
                DB::connection($this->connection)
                    ->statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_id_role_foreign;');
            } catch (\Throwable $e) {
                // abaikan
            }

            // drop kolom
            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn('id_role');
            });
        }

        // (Opsional) kalau kamu ingin mengembalikan nama kolom lama:
        // DB::connection($this->connection)->statement('ALTER TABLE users RENAME COLUMN id_role TO role_id;');
        // (Komentar karena tidak semua project menginginkannya)
    }
};