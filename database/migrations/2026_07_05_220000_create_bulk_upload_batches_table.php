<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_upload_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('batch_id')->index();
            $table->foreignUuid('client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('valid_records')->default(0);
            $table->unsignedInteger('invalid_records')->default(0);
            $table->string('status')->default('pending');
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_batches');
    }
};
