<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla 'personas' con campos en español.
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Obligatorios
            $table->string('nombre_completo', 300);
            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 100);

            // Opcionales
            $table->unsignedSmallInteger('edad')->nullable();
            $table->date('nacimiento')->nullable();
            $table->string('genero', 10)->nullable();

            $table->string('direccion', 500)->nullable();
            $table->string('sector', 150)->nullable();
            $table->string('barrio', 150)->nullable();
            $table->string('comuna', 80)->nullable();

            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('condicion', 150)->nullable();
            $table->string('etnia', 150)->nullable();
            $table->string('nivel_estudio', 150)->nullable();
            $table->boolean('dignatario')->default(false);

            // Provenance & quality
            $table->unsignedTinyInteger('data_quality_score')->default(50);
            $table->string('source_project', 100)->nullable();
            $table->timestamp('last_verified_at')->nullable();

            $table->uuid('created_by_client_id')->nullable();
            $table->uuid('updated_by_client_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->unique(['tipo_documento', 'numero_documento'], 'personas_documento_unico');

            // Indexes
            $table->index(['nombre_completo']);
            $table->index(['comuna']);
            $table->index(['barrio']);
            $table->index(['data_quality_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
