<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ini berada di database operasional yang dipakai bersama POS.
     * Koneksi dikunci agar migration tetap benar walau dijalankan dengan
     * opsi --database=immostore_backoffice.
     */
    protected $connection = 'pgsql';

    public function up(): void
    {
        if (! Schema::hasTable('vouchers') || Schema::hasColumn('vouchers', 'voucher_image_path')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->string('voucher_image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'voucher_image_path')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('voucher_image_path');
        });
    }
};
