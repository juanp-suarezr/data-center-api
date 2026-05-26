<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Data - API Clients registry
     * Each external project (Vive Digital, Votaciones, etc.) registers here.
     * Tokens are issued via Sanctum on this model.
     */
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 150);                    // Human readable: "Vive Digital Platform"
            $table->string('slug', 100)->unique();          // e.g. "vive-digital"
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_trusted')->default(false);  // Higher trust = higher quality scores on sync

            // Security & rate limiting
            $table->json('allowed_ips')->nullable();        // Whitelist of IPs or CIDR
            $table->unsignedInteger('rate_limit_per_minute')->default(60);

            $table->string('contact_email', 150)->nullable();
            $table->string('webhook_url', 255)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
