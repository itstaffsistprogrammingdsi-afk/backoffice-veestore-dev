<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('immostore_backoffice');

        if (! $schema->hasTable('stock_barrier_alert_states')) {
            $schema->create('stock_barrier_alert_states', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('scope_type', 20);
                $table->uuid('location_id');
                $table->text('location_name');
                $table->text('location_code')->nullable();
                $table->uuid('product_id');
                $table->text('product_code')->nullable();
                $table->text('product_name');
                $table->text('unit_name')->default('');
                $table->integer('on_hand');
                $table->integer('reserved');
                $table->integer('available');
                $table->integer('stock_barrier');
                $table->integer('threshold');
                $table->boolean('is_active')->default(true);
                $table->timestampTz('activated_at');
                $table->timestampTz('last_seen_at');
                $table->timestampTz('resolved_at')->nullable();
                $table->uuid('scan_id')->nullable();
                $table->timestampsTz();

                $table->unique(
                    ['scope_type', 'location_id', 'product_id'],
                    'stock_barrier_state_location_product_unique'
                );
                $table->index(
                    ['scope_type', 'is_active', 'activated_at'],
                    'stock_barrier_state_active_scope_index'
                );
                $table->index('scan_id', 'stock_barrier_state_scan_index');
            });
        }

        if (! $schema->hasTable('stock_barrier_alert_emails')) {
            $schema->create('stock_barrier_alert_emails', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->jsonb('recipients');
                $table->jsonb('payload');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestampTz('sent_at')->nullable();
                $table->timestampsTz();

                $table->index(['sent_at', 'created_at'], 'stock_barrier_email_pending_index');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('immostore_backoffice');
        $schema->dropIfExists('stock_barrier_alert_emails');
        $schema->dropIfExists('stock_barrier_alert_states');
    }
};
