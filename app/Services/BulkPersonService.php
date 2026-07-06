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

        $chunkSize = 200;
        $jobBatchIds = [];
        $totalValid = count($validRows);

        \Illuminate\Support\Facades\Log::info('BulkPersonService: iniciando inserción de jobs', [
            'upload_batch_id' => $batchId,
            'total_filas' => count($dto->rows),
            'filas_validas' => $totalValid,
            'chunk_size' => $chunkSize,
            'chunks' => (int) ceil($totalValid / $chunkSize),
        ]);

        foreach (array_chunk($validRows, $chunkSize) as $index => $chunk) {
            $jobs = [];
            foreach ($chunk as $row) {
                $jobs[] = new ProcessBulkPersonUploadJob(
                    record: self::normalizeRow($row),
                    options: $options + ['client_id' => $client->id],
                    uploadBatchId: $batchId
                );
            }

            $batch = Bus::batch($jobs)->dispatch();
            $jobBatchIds[] = $batch->id;

            \Illuminate\Support\Facades\Log::info('BulkPersonService: chunk despachado', [
                'upload_batch_id' => $batchId,
                'chunk' => $index + 1,
                'jobs_en_chunk' => count($jobs),
                'job_batch_id' => $batch->id,
            ]);
        }

        BulkUploadBatch::create([
            'id' => $batchId,
            'batch_id' => $batchId,
            'client_id' => $client->id,
            'total_records' => count($dto->rows),
            'valid_records' => $totalValid,
            'invalid_records' => count($dto->getInvalidRows()),
            'job_batch_ids' => $jobBatchIds,
            'status' => 'processing',
        ]);

        return [
            'batch_id' => $batchId,
            'job_batch_ids' => $jobBatchIds,
            'chunks' => count($jobBatchIds),
            'total_records' => count($dto->rows),
            'valid_records' => $totalValid,
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

        $jobBatchIds = $batchRecord->job_batch_ids ?? [];
        if (is_array($jobBatchIds) && count($jobBatchIds) > 0) {
            $pending = 0;
            $processed = 0;
            $failed = 0;
            $totalJobs = 0;

            foreach ($jobBatchIds as $jobBatchId) {
                $sub = Bus::findBatch($jobBatchId);
                if (!$sub) {
                    continue;
                }
                $pending += $sub->pendingJobs;
                $processed += $sub->processedJobs ?: 0;
                $failed += $sub->failedJobs;
                $totalJobs += $sub->totalJobs;
            }

            $status['pending_jobs'] = $pending;
            $status['processed_jobs'] = $processed;
            $status['failed_jobs'] = $failed;
            $status['total_jobs'] = $totalJobs;
            $status['progress'] = $totalJobs > 0 ? round(($processed / $totalJobs) * 100, 2) : 0;
        }

        return $status;
    }

    /**
     * Convierte todos los valores de la fila a UTF-8 válido para que el
     * job pueda serializarse a JSON sin error "Malformed UTF-8 characters".
     */
    private static function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

                if ($encoding && $encoding !== 'UTF-8') {
                    $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                }

                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;
                $normalized[$key] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}