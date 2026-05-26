<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multiple addresses per person (residencia, correspondencia, trabajo, etc.)
     * Colombian administrative divisions included for government use cases.
     */
    public function up(): void
    {
        Schema::create('person_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('person_id');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('cascade');

            $table->enum('type', ['residencia', 'correspondencia', 'trabajo', 'otro'])->default('residencia');
            $table->boolean('is_primary')->default(false);

            // Colombian address structure
            $table->string('via_principal', 100);           // Calle, Carrera, Avenida, etc.
            $table->string('numero_via', 20);               // 123
            $table->string('complemento', 100)->nullable(); // Apto 101, Torre B
            $table->string('barrio', 100)->nullable();
            $table->string('comuna', 50)->nullable();       // Comuna 1, Comuna 2...
            $table->string('municipio', 100);
            $table->string('departamento', 100);
            $table->string('pais', 100)->default('Colombia');
            $table->string('codigo_postal', 20)->nullable();

            // Geolocation for future GIS integration
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'is_primary']);
            $table->index(['municipio', 'departamento']);
            $table->index('barrio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_addresses');
    }
};
