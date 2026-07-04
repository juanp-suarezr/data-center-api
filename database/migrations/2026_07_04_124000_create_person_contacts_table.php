<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('personas')->cascadeOnDelete();

            $table->string('type'); // phone | mobile | whatsapp | email
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('country_code', 10)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_contacts');
    }
};
