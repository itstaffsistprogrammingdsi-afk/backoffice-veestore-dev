<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // pastikan ke DB backoffice
    protected $connection = 'immostore_backoffice';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // jika tabel sudah ada, jangan create lagi
        if (! $schema->hasTable('personal_access_tokens')) {
            $schema->create('personal_access_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');

                // CATATAN: jika users.id kamu UUID, ganti ke $table->uuidMorphs('tokenable');
                $table->morphs('tokenable'); // tokenable_type & tokenable_id (+index)

                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('personal_access_tokens');
    }
};