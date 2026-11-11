<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        if (! $schema->hasTable('report_exports')) {
            $schema->create('report_exports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->index();
                $table->string('type');
                $table->string('status')->default('queued');
                $table->text('filters');
                $table->string('filename');
                $table->string('path')->nullable();
                $table->text('error')->nullable();
                $table->timestampsTz();
            });
        }

        if (! $schema->hasTable('report_export_jobs')) {
            $schema->create('report_export_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        $schema->dropIfExists('report_export_jobs');
        $schema->dropIfExists('report_exports');
    }
};
