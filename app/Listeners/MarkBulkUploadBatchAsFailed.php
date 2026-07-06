<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\BulkUploadBatch;
use Illuminate\Bus\Events\BatchFailed;
use Illuminate\Support\Facades\Log;

class MarkBulkUploadBatchAsFailed
{
    public function handle(BatchFailed $event): void
    {
        $batch = $event->batch;

        $uploadBatch = BulkUploadBatch::where('batch_id', $batch->id)->first();
        if (!$uploadBatch) {
            return;
        }

        $uploadBatch->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);

        Log::error('Bulk upload batch failed', [
            'batch_id' => $uploadBatch->id,
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs,
        ]);
    }
}
