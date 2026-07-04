<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('personas')->cascadeOnDelete();

            $table->string('type')->nullable(); // home | work | etc
            $table->boolean('is_primary')->default(false);

            $table->string('via_principal', 150);
            $table->string('numero_via', 20)->nullable();
            $table->string('complemento', 150)->nullable();
            $table->string('barrio', 150)->nullable();
            $table->string('comuna', 80)->nullable();
            $table->string('municipio', 100);
            $table->string('departamento', 100);
            $table->string('pais', 100)->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->double('latitud', 10, 7)->nullable();
            $table->double('longitud', 10, 7)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'is_primary']);
            $table->index(['municipio', 'departamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_addresses');
    }
};
