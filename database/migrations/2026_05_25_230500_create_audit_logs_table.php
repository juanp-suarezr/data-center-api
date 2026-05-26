<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comprehensive immutable audit trail for all data changes.
     * Critical for government / compliance requirements (traceability, non-repudiation).
     * Stores before/after as JSON for complete diff capability.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic subject
            $table->string('auditable_type', 100);   // App\Models\Person, App\Models\PersonContact, etc.
            $table->uuid('auditable_id');

            // Actor
            $table->uuid('api_client_id')->nullable();
            $table->foreign('api_client_id')->references('id')->on('api_clients')->onDelete('set null');

            $table->string('action', 50);            // create, update, delete, verify, search, sync, merge
            $table->string('field_changed', 100)->nullable(); // for granular updates

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context for forensics
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('request_id', 36)->nullable(); // correlation id for distributed tracing
            $table->string('endpoint', 150)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Query performance for compliance reports
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['api_client_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
