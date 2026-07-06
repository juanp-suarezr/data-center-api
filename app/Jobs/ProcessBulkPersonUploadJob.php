<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\PersonData;
use App\Models\ApiClient;
use App\Models\BulkUploadBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBulkPersonUploadJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly array $record,
        public readonly array $options,
        public readonly string $uploadBatchId,
    ) {}

    public function handle(): void
    {
        $batchRecord = BulkUploadBatch::where('id', $this->uploadBatchId)->first();
        if (!$batchRecord) {
            return;
        }

        $client = ApiClient::find($this->options['client_id']);
        if (!$client) {
            $this->incrementCounter($batchRecord, 'error');
            return;
        }

        try {
            $cleaner = new \App\DTOs\BulkPersonUploadDTO(
                rows: [$this->record],
                sourceProject: '',
                clientId: ''
            );

            $cleanedRecord = $cleaner->cleanRow($this->record);

            $dto = PersonData::fromArray($cleanedRecord + [
                'client_id' => $client->id,
                'source_project' => $this->options['source_project'] ?? $client->slug,
            ]);

            $repository = app(\App\Interfaces\Repositories\PersonRepositoryInterface::class);

            DB::transaction(function () use ($dto, $client, $repository, $batchRecord) {
                $existing = $repository->findByDocument($dto->tipoDocumento, $dto->numeroDocumento);

                if ($existing && ($this->options['update_existing'] ?? true)) {
                    $repository->update($existing, $dto->toArray() + [
                        'updated_by_client_id' => $client->id,
                        'source_project' => $dto->sourceProject ?? $client->slug,
                    ]);
                    $repository->attachProjectRelation($existing, $client->slug, [
                        'last_action' => 'bulk_update',
                        'data_quality_score' => $existing->data_quality_score,
                    ]);
                    $this->incrementCounter($batchRecord, 'updated');
                } else {
                    $person = $repository->create($dto->toArray() + [
                        'created_by_client_id' => $client->id,
                        'source_project' => $dto->sourceProject ?? $client->slug,
                    ]);
                    $repository->attachProjectRelation($person, $client->slug, [
                        'last_action' => 'bulk_create',
                        'trust_level' => 'medium',
                        'data_quality_score' => $person->data_quality_score,
                    ]);
                    $this->incrementCounter($batchRecord, 'created');
                }

                $this->incrementCounter($batchRecord, 'processed');
            });
        } catch (\Throwable $e) {
            $this->incrementCounter($batchRecord, 'error');

            \Illuminate\Support\Facades\Log::error('Bulk person job failed', [
                'batch_id' => $this->uploadBatchId,
                'error' => $e->getMessage(),
                'record' => $this->record,
            ]);

            throw $e;
        }
    }

    private function incrementCounter(BulkUploadBatch $batchRecord, string $type): void
    {
        $field = $type . '_count';
        if (in_array($field, ['processed_count', 'created_count', 'updated_count', 'error_count'])) {
            $batchRecord->increment($field);
        }
    }
}