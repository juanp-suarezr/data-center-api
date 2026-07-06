<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\BulkPersonUploadDTO;
use App\Jobs\ProcessBulkPersonUploadJob;
use App\Models\ApiClient;
use App\Models\BulkUploadBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class BulkPersonService
{
    public function processUpload(string $content, ApiClient $client, array $options = []): array
    {
        $dto = BulkPersonUploadDTO::fromUploadedFile($content, $options + ['client_id' => $client->id]);
        $batchId = (string) Str::uuid();

        $validRows = $dto->getValidRows();

        if (count($validRows) === 0) {
            $batchId = (string) Str::uuid();

            BulkUploadBatch::create([
                'id' => $batchId,
                'batch_id' => $batchId,
                'client_id' => $client->id,
                'total_records' => count($dto->rows),
                'valid_records' => 0,
                'invalid_records' => count($dto->getInvalidRows()),
                'status' => 'failed',
            ]);

            return [
                'batch_id' => $batchId,
                'job_batch_id' => null,
                'total_records' => count($dto->rows),
                'valid_records' => 0,
                'invalid_records' => count($dto->getInvalidRows()),
                'status' => 'failed',
                'message' => 'No se encontraron registros válidos en el archivo',
            ];
        }

        $jobs = [];
        foreach ($validRows as $index => $row) {
            $jobs[] = new ProcessBulkPersonUploadJob(
                record: $row,
                options: $options + ['client_id' => $client->id],
                uploadBatchId: $batchId
            );
        }

        $batch = Bus::batch($jobs)->dispatch();

        BulkUploadBatch::create([
            'id' => $batchId,
            'batch_id' => $batch->id,
            'client_id' => $client->id,
            'total_records' => count($dto->rows),
            'valid_records' => count($validRows),
            'invalid_records' => count($dto->getInvalidRows()),
            'status' => 'processing',
        ]);

        return [
            'batch_id' => $batchId,
            'job_batch_id' => $batch->id,
            'total_records' => count($dto->rows),
            'valid_records' => count($validRows),
            'invalid_records' => count($dto->getInvalidRows()),
            'message' => 'Procesamiento en cola iniciado',
        ];
    }

    public function getBatchStatus(string $batchId): array
    {
        $batchRecord = BulkUploadBatch::where('id', $batchId)->first();

        if (!$batchRecord) {
            return ['status' => 'not_found'];
        }

        $laravelBatch = Bus::findBatch($batchRecord->batch_id);

        $status = [
            'batch_id' => $batchId,
            'status' => $batchRecord->status,
            'total_records' => $batchRecord->total_records,
            'valid_records' => $batchRecord->valid_records,
            'invalid_records' => $batchRecord->invalid_records,
        ];

        if ($laravelBatch) {
            $status['progress'] = $laravelBatch->progress();
            $status['pending_jobs'] = $laravelBatch->pendingJobs;
            $status['processed_jobs'] = $laravelBatch->processedJobs ?: 0;
            $status['failed_jobs'] = $laravelBatch->failedJobs;
        }

        return $status;
    }
}