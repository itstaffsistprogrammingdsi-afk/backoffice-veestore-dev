<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('coordinate_source', 50)->nullable();
            $table->timestampTz('geocoded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'coordinate_source',
                'geocoded_at',
            ]);
        });
    }
};
