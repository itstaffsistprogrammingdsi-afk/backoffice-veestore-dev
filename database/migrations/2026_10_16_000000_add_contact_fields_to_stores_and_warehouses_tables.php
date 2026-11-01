<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['stores', 'warehouses'] as $table) {
            foreach (['contact_name', 'contact_phone', 'contact_email'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->string($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Kolom mungkin sudah tersedia sebelum migrasi ini dicatat.
        // Jangan menghapus data kontak yang bukan dibuat oleh migrasi ini.
    }
};
