<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        if ($schema->hasTable('business_audit_logs')) {
            return;
        }

        $schema->create('business_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('app_type', 40)->default('backoffice')->index();
            $table->string('module')->nullable()->index();
            $table->string('action', 20);
            $table->string('route_name')->nullable()->index();
            $table->text('url');
            $table->string('method', 10);
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->jsonb('request_data')->nullable();
            $table->jsonb('context')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->timestampsTz();

            $table->index(['action', 'occurred_at'], 'audit_action_occurred_at_index');
        });
    }

    public function down(): void
    {
        Schema::connection('immostore_backoffice')->dropIfExists('business_audit_logs');
    }
};
