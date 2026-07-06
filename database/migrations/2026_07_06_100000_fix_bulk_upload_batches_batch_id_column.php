<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bulk_upload_batches MODIFY batch_id VARCHAR(255)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bulk_upload_batches MODIFY batch_id BIGINT UNSIGNED');
    }
};
