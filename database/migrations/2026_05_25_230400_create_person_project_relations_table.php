<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Junction table tracking every project's interaction with a person.
     * This is the heart of the MDM traceability system.
     * One row per (person, client/project) pair.
     */
    public function up(): void
    {
        Schema::create('person_project_relations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('person_id');
            $table->foreign('person_id')->references('id')->on('personas')->onDelete('cascade');

            $table->uuid('api_client_id');
            $table->foreign('api_client_id')->references('id')->on('api_clients')->onDelete('cascade');

            // Provenance
            $table->timestamp('first_seen_at');
            $table->timestamp('last_synced_at')->useCurrent();
            $table->timestamp('last_verified_at')->nullable();

            // Quality & trust per source
            $table->unsignedTinyInteger('data_quality_score')->default(60);
            $table->enum('trust_level', ['low', 'medium', 'high', 'official'])->default('medium');

            // What this project contributed/updated
            $table->json('contributed_fields')->nullable(); // e.g. ["correo", "direccion", "telefono"]

            // Optional: last action performed by this client on this person
            $table->string('last_action', 50)->nullable(); // 'create', 'update', 'verify', 'search'

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Critical uniqueness
            $table->unique(['person_id', 'api_client_id'], 'person_client_unique');

            $table->index(['api_client_id', 'last_synced_at']);
            $table->index('trust_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_project_relations');
    }
};
