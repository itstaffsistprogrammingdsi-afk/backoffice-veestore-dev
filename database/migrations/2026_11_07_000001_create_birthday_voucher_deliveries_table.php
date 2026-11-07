<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('birthday_voucher_deliveries')) {
            return;
        }

        $schema->create('birthday_voucher_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('voucher_id')->index();
            $table->uuid('voucher_code_id')->nullable()->unique();
            $table->uuid('customer_id')->index();
            $table->string('email')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['voucher_id', 'customer_id'], 'birthday_voucher_customer_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('birthday_voucher_deliveries');
    }
};
