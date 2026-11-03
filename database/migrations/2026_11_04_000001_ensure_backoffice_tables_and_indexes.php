<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        if (! $schema->hasTable('business_audit_logs')) {
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
            });
        }

        if (! $schema->hasTable('notification_reads')) {
            $schema->create('notification_reads', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->index();
                $table->string('notification_key', 100);
                $table->string('fingerprint', 64);
                $table->timestampTz('read_at');
                $table->timestampsTz();
                $table->unique(['user_id', 'notification_key']);
            });
        }

        if ($schema->hasTable('report_exports')) {
            $schema->table('report_exports', function (Blueprint $table) use ($schema) {
                if (! $schema->hasIndex('report_exports', ['user_id', 'created_at'])) {
                    $table->index(['user_id', 'created_at'], 'report_exports_user_created_index');
                }

                if (! $schema->hasIndex('report_exports', ['user_id', 'status'])) {
                    $table->index(['user_id', 'status'], 'report_exports_user_status_index');
                }
            });
        }

        if ($schema->hasTable('report_export_jobs') && ! $schema->hasIndex('report_export_jobs', ['queue', 'reserved_at', 'available_at'])) {
            $schema->table('report_export_jobs', function (Blueprint $table) {
                $table->index(['queue', 'reserved_at', 'available_at'], 'report_jobs_queue_available_index');
            });
        }

        if ($schema->hasTable('business_audit_logs') && ! $schema->hasIndex('business_audit_logs', ['action', 'occurred_at'])) {
            $schema->table('business_audit_logs', function (Blueprint $table) {
                $table->index(['action', 'occurred_at'], 'audit_action_occurred_at_index');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        if ($schema->hasTable('report_exports')) {
            $schema->table('report_exports', function (Blueprint $table) use ($schema) {
                if ($schema->hasIndex('report_exports', 'report_exports_user_created_index')) {
                    $table->dropIndex('report_exports_user_created_index');
                }

                if ($schema->hasIndex('report_exports', 'report_exports_user_status_index')) {
                    $table->dropIndex('report_exports_user_status_index');
                }
            });
        }

        if ($schema->hasTable('report_export_jobs') && $schema->hasIndex('report_export_jobs', 'report_jobs_queue_available_index')) {
            $schema->table('report_export_jobs', fn (Blueprint $table) => $table->dropIndex('report_jobs_queue_available_index'));
        }

        if ($schema->hasTable('business_audit_logs') && $schema->hasIndex('business_audit_logs', 'audit_action_occurred_at_index')) {
            $schema->table('business_audit_logs', fn (Blueprint $table) => $table->dropIndex('audit_action_occurred_at_index'));
        }
    }
};
