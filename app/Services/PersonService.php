<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PersonData;
use App\Interfaces\Repositories\PersonRepositoryInterface;
use App\Models\ApiClient;
use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonService
{
    public function __construct(
        private readonly PersonRepositoryInterface $repository
    ) {}

    /**
     * Search for existing person - primary entry point for all clients.
     * Uses caching for hot paths.
     */
    public function searchPerson(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'person_search_'.md5(json_encode($filters).$perPage);

        return Cache::remember($cacheKey, 60, function () use ($filters, $perPage) {
            return $this->repository->search($filters, $perPage);
        });
    }

    public function findByDocument(string $tipo, string $numero, ?string $sourceProject = null): ?Persona
    {
        $person = $this->repository->findByDocument($tipo, $numero, $sourceProject);

        
        return $person;
    }

    /**
     * Create or update based on document uniqueness (idempotent sync).
     * This is the core "sync" endpoint logic.
     */
    public function syncPerson(PersonData $dto, ApiClient $client): array
    {
        
        
        return DB::transaction(function () use ($dto, $client) {
            $existing = $this->repository->findByDocument($dto->tipoDocumento, $dto->numeroDocumento, null);
            

            if ($existing) {
                $old = $existing->toArray();

                $updateData = $dto->toArray();
                unset($updateData['data_quality_score']);

                // La persona puede provenir de varios proyectos; se acumulan
                // (ej: creada en "vive-digital" y luego registrada en "votaciones").
                $updateData['source_project'] = $this->mergeSourceProjects(
                    $existing->source_project,
                    $dto->sourceProject ?: [$client->slug]
                );

                $updated = $this->repository->update($existing, $updateData + [
                    'updated_by_client_id' => $client->id,
                ]);

                 $this->repository->attachProjectRelation($updated, $client->slug, [
                     'last_action' => 'update',
                     'data_quality_score' => $updated->data_quality_score,
                 ]);

                $this->repository->recordAudit($updated, 'update', $old, $updated->toArray(), $client->id);

                return [
                    'action' => 'updated',
                    'person' => $updated->load(['contacts', 'addresses']),
                    'message' => 'Persona actualizada exitosamente',
                ];
            }

             // Create new
             $createData = $dto->toArray();
             $createData['source_project'] = $dto->sourceProject ?: [$client->slug];

             $person = $this->repository->create($createData + [
                 'created_by_client_id' => $client->id,
             ]);

             $this->repository->attachProjectRelation($person, $client->slug, [
                 'trust_level' => 'medium',
                 'data_quality_score' => $person->data_quality_score,
                 'last_action' => 'create',
             ]);

             $this->repository->recordAudit($person, 'create', null, $person->toArray(), $client->id);

            return [
                'action' => 'created',
                'person' => $person->load(['contacts', 'addresses']),
                'message' => 'Persona registrada exitosamente en el Data Center',
            ];
        });
    }

    public function getPerson(string $uuid): ?Persona
    {
        return $this->repository->findByUuid($uuid);
    }

    /**
     * Combina los proyectos origen existentes con los nuevos, sin duplicados.
     * Acepta valores en formato array o string (compatibilidad con datos previos).
     */
    private function mergeSourceProjects(array|string|null $existing, array|string|null $incoming): array
    {
        $normalize = static function (array|string|null $value): array {
            if ($value === null || $value === '') {
                return [];
            }
            $items = is_array($value) ? $value : [$value];
            $items = array_map(static fn ($item) => trim((string) $item), $items);

            return array_filter($items, static fn ($item) => $item !== '');
        };

        return array_values(array_unique(array_merge(
            $normalize($existing),
            $normalize($incoming)
        )));
    }
}
