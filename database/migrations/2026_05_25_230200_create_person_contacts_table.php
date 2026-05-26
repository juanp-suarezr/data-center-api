<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contacts for persons (emails, phones, whatsapp, etc.)
     * Supports multiple contacts per person with verification status.
     */
    public function up(): void
    {
        Schema::create('person_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('person_id');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('cascade');

            $table->enum('type', ['email', 'phone', 'mobile', 'whatsapp', 'telegram', 'other']);
            $table->string('value', 150);                    // email or phone number normalized

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->uuid('verified_by_client_id')->nullable();

            $table->string('country_code', 5)->nullable();   // For phones: +57

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Uniqueness per type + value for a person
            $table->unique(['person_id', 'type', 'value'], 'person_contact_unique');

            $table->index(['person_id', 'is_primary']);
            $table->index('type');
            $table->index('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_contacts');
    }
};
