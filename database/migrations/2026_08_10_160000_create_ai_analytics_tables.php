<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Database connection untuk IMMO Store Backoffice.
     */
    protected $connection = 'immostore_backoffice';

    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        /*
        |--------------------------------------------------------------------------
        | analytics_model_runs
        |--------------------------------------------------------------------------
        */
        if (! $schema->hasTable('analytics_model_runs')) {
            $schema->create('analytics_model_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('model_name', 120)->index();
                $table->string('model_type', 40)->index();
                $table->string('model_version', 40);
                $table->string('status', 30)->index();

                $table->date('training_from')->nullable();
                $table->date('training_to')->nullable();

                $table->jsonb('parameters')->nullable();
                $table->jsonb('metrics')->nullable();

                $table->text('error_message')->nullable();

                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('completed_at')->nullable();

                $table->timestampsTz();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | analytics_sales_forecasts
        |--------------------------------------------------------------------------
        */
        if (! $schema->hasTable('analytics_sales_forecasts')) {
            $schema->create('analytics_sales_forecasts', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('model_run_id');

                $table->date('forecast_date')->index();
                $table->string('channel', 20)->index();

                $table->uuid('store_id')
                    ->nullable()
                    ->index();

                $table->uuid('warehouse_id')
                    ->nullable()
                    ->index();

                $table->uuid('product_id')->index();

                $table->unsignedSmallInteger('horizon_days')
                    ->default(7);

                $table->decimal('predicted_qty', 18, 4)
                    ->default(0);

                $table->decimal('lower_qty', 18, 4)
                    ->default(0);

                $table->decimal('upper_qty', 18, 4)
                    ->default(0);

                $table->decimal('predicted_revenue', 18, 2)
                    ->default(0);

                $table->decimal('confidence', 6, 4)
                    ->nullable();

                $table->timestampTz('generated_at')
                    ->index();

                $table->timestampsTz();

                $table->foreign('model_run_id')
                    ->references('id')
                    ->on('analytics_model_runs')
                    ->cascadeOnDelete();

                $table->index(
                    ['model_run_id', 'channel', 'product_id'],
                    'analytics_sales_forecast_model_channel_product_idx'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | analytics_stock_recommendations
        |--------------------------------------------------------------------------
        */
        if (! $schema->hasTable('analytics_stock_recommendations')) {
            $schema->create('analytics_stock_recommendations', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('model_run_id');

                $table->uuid('store_id')->index();
                $table->uuid('product_id')->index();

                $table->integer('current_stock')
                    ->default(0);

                $table->integer('reserved_stock')
                    ->default(0);

                $table->decimal('forecast_qty_7d', 18, 4)
                    ->default(0);

                $table->decimal('average_daily_demand', 18, 4)
                    ->default(0);

                $table->integer('safety_stock')
                    ->default(0);

                $table->integer('recommended_qty')
                    ->default(0);

                $table->string('risk_level', 20)
                    ->index();

                $table->string('status', 30)
                    ->default('recommended')
                    ->index();

                $table->uuid('reviewed_by')
                    ->nullable();

                $table->timestampTz('reviewed_at')
                    ->nullable();

                $table->text('review_notes')
                    ->nullable();

                $table->timestampTz('generated_at')
                    ->index();

                $table->timestampsTz();

                $table->foreign('model_run_id')
                    ->references('id')
                    ->on('analytics_model_runs')
                    ->cascadeOnDelete();

                $table->unique(
                    ['model_run_id', 'store_id', 'product_id'],
                    'analytics_stock_rec_run_store_product_unique'
                );
            });
        }
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('analytics_stock_recommendations');
        $schema->dropIfExists('analytics_sales_forecasts');
        $schema->dropIfExists('analytics_model_runs');
    }
};