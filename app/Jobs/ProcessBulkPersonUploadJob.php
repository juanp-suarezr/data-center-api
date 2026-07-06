<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\PersonData;
use App\Enums\DocumentType;
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
            return;
        }

        $cleanedRecord = $this->cleanRecord($this->record);

        if (!$this->validateRecord($cleanedRecord)) {
            $this->incrementCounter($batchRecord, 'error');
            return;
        }

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
    }

    private function incrementCounter(BulkUploadBatch $batchRecord, string $type): void
    {
        $field = $type . '_count';
        if (in_array($field, ['processed_count', 'created_count', 'updated_count', 'error_count'])) {
            $batchRecord->increment($field);
        }
    }

    private function cleanRecord(array $record): array
    {
        $cleaned = [];
        $fieldMappings = [
            'nombres' => ['nombres', 'first_name', 'firstname', 'primer_nombre'],
            'apellidos' => ['apellidos', 'last_name', 'lastname', 'segundo_nombre'],
            'tipo_documento' => ['tipo_documento', 'document_type', 'tipo', 'type'],
            'numero_documento' => ['numero_documento', 'document_number', 'numero', 'number'],
            'edad' => ['edad', 'age'],
            'fecha_nacimiento' => ['fecha_nacimiento', 'birth_date', 'nacimiento'],
            'genero' => ['genero', 'gender', 'sexo'],
            'correo' => ['correo', 'email'],
            'telefono' => ['telefono', 'phone', 'celular'],
            'direccion' => ['direccion', 'address'],
            'sector' => ['sector'],
            'barrio' => ['barrio', 'neighborhood'],
            'comuna' => ['comuna', 'commune'],
            'condicion' => ['condicion', 'condition'],
            'etnia' => ['etnia', 'ethnicity'],
            'nivel_estudio' => ['nivel_estudio', 'education'],
            'dignatario' => ['dignatario', 'is_public', 'public_figure'],
        ];

        foreach ($fieldMappings as $target => $sources) {
            foreach ($sources as $source) {
                if (isset($record[$source]) && $record[$source] !== '') {
                    $cleaned[$target] = trim((string) $record[$source]);
                    break;
                }
            }
        }

        if (!isset($cleaned['nombres']) && !isset($cleaned['apellidos'])) {
            if (isset($record['nombre_completo']) || isset($record['fullname'])) {
                $nombreCompleto = $record['nombre_completo'] ?? $record['fullname'] ?? '';
                $parts = explode(' ', trim($nombreCompleto));
                $cleaned['nombres'] = array_shift($parts) ?? '';
                $cleaned['apellidos'] = implode(' ', $parts);
            }
        }

        if (isset($cleaned['dignatario'])) {
            $cleaned['dignatario'] = in_array(strtolower($cleaned['dignatario']), ['1', 'true', 'yes', 'si', 'sí'], true);
        }

        return $cleaned;
    }

    private function validateRecord(array $record): bool
    {
        $documentTypes = DocumentType::values();
        $tipoDoc = strtoupper($record['tipo_documento'] ?? '');
        $numDoc = $record['numero_documento'] ?? '';

        return in_array($tipoDoc, $documentTypes, true) && !empty($numDoc) && strlen($numDoc) >= 4;
    }
}