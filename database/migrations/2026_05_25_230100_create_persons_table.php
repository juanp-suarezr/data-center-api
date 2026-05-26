<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central Master Data Management - Persons table
     * Uses UUIDs exclusively. No incremental IDs exposed.
     * Unique constraint on (tipo_documento, numero_documento) for identity resolution.
     */
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Core identification - critical for MDM deduplication
            $table->string('tipo_documento', 10); // CC, TI, CE, NIT, PP, RC, etc.
            $table->string('numero_documento', 50);

            // Personal information
            $table->string('nombres', 150);
            $table->string('apellidos', 150);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['M', 'F', 'O', 'N'])->nullable(); // Masculino, Femenino, Otro, No especificado
            $table->string('estado_civil', 30)->nullable();
            $table->string('ocupacion', 150)->nullable();
            $table->string('nacionalidad', 100)->default('COLOMBIANA');

            // Data provenance & quality (MDM critical fields)
            $table->uuid('created_by_client_id')->nullable();
            $table->uuid('updated_by_client_id')->nullable();
            $table->string('source_project', 100)->nullable(); // e.g. 'vive-digital', 'votaciones'
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedTinyInteger('data_quality_score')->default(50); // 0-100 confidence

            // Flexible metadata for future extensions without schema changes
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Critical unique constraint for Master Data
            $table->unique(['tipo_documento', 'numero_documento'], 'persons_document_unique');

            // Performance indexes for high-volume queries
            $table->index(['nombres', 'apellidos']);
            $table->index('created_at');
            $table->index('data_quality_score');
            $table->index('source_project');
        });

        // NOTE: Foreign keys to api_clients are intentionally omitted for horizontal scaling.
        // Integrity is enforced at application/repository level + data quality rules.
        // Add FKs in production only if using single DB instance without sharding plans.
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
