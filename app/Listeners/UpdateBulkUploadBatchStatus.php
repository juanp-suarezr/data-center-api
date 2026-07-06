<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\BulkUploadBatch;
use Illuminate\Bus\Events\BatchCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateBulkUploadBatchStatus
{
    public function handle(BatchCompleted $event): void
    {
        $batch = $event->batch;

        $uploadBatch = BulkUploadBatch::where('batch_id', $batch->id)->first();
        if (!$uploadBatch) {
            return;
        }

        $uploadBatch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Bulk upload batch completed', [
            'batch_id' => $uploadBatch->id,
            'total_records' => $uploadBatch->total_records,
            'processed' => $uploadBatch->processed_count,
        ]);
    }
}